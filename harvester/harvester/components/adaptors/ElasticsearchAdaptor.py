import os
import logging
import json
import time
from elasticsearch import Elasticsearch, helpers
from elasticsearch.exceptions import NotFoundError, TransportError, ElasticsearchException


class ElasticsearchAdaptor  ():
    """Logger as set by the add_logging() method."""
    logger = None

    """Log level for logging module."""
    log_level = logging.INFO

    """Formatter for logging messages."""
    log_formatter = logging.Formatter(
        '%(asctime)s - %(name)s - %(levelname)s - %(message)s')

    records_processed = 0

    records_failed = 0

    """
    The adaptor will attempt to retrieve only the
    following defined fields from the input data.
    """
    schema_fields = [
        'asset_id',
        'date_recorded',
        'description',
        'duration',
        'in_playlists',
        'playlists',
        'program_series',
        'quote',
        'speakers',
        'thumbnailId',
        'thumbnail_url',
        'title' ,
        'title_slug' ,
        'transcription',
        'transcription_txt',
        'tags',
        'topics',
        'video_url',
    ]

    def __init__(self, data_path, es_domain, port='443', scheme='https', alias='videos'):
        self.input_data_path = data_path

        # @todo Move to a config file
        self.timestamp = int(time.time())
        self.alias = alias
        self.index_prefix = 'video_'

        self.client = Elasticsearch(
            es_domain,
            scheme=scheme,
            port=port
        )

        self.index_name = self.create_index_name(self.index_prefix, self.timestamp)

    def add_logger(self, log_directory, log_file, log_name='elasticsearch-adaptor'):
        """
        Add a basic logger, with a file and stream handler.
        """
        logger = logging.getLogger(log_name)
        logger.setLevel(self.log_level)

        # Only add the handlers once
        if not logger.hasHandlers():
            log_path = os.path.join(log_directory, log_file)
            ch1 = logging.FileHandler(log_path)
            ch1.setLevel(self.log_level)
            ch1.setFormatter(self.log_formatter)
            logger.addHandler(ch1)

            ch2 = logging.StreamHandler()
            ch2.setLevel(self.log_level)
            ch2.setFormatter(self.log_formatter)
            logger.addHandler(ch2)

        self.logger = logger


    def pre_process(self):
        """
        Index and alias creation
        """
        index_name = self.index_name
        self.create_index(index_name)
        self.logger.info("Using index %s", index_name)
        # Create alias if required.
        alias = self.alias
        self.create_alias(alias)
        self.logger.info("Using alias: %s", alias)


    def process(self):
        """
        Submit documents for indexing.
        """
        self.logger.info('Started processing at %s', time.ctime())

        self.submit()

        self.logger.info('%i documents indexed' % self.records_processed)
        self.logger.info('%i documents failed' % self.records_failed)

        self.logger.info('Finished processing at %s', time.ctime())


    def post_process(self):
        """
        Promote the new index to
        the configured alias.
        """
        # Switch alias to newly created index
        alias = self.alias
        if self.update_alias(alias, self.index_name):
            print('Updated {} alias to point to {}.'.format(
                alias, self.index_name))


    def load(self):
        paths = [path for path in os.listdir(self.input_data_path) if path.endswith('.json')]
        for path in paths:
            with open(os.path.join(self.input_data_path, path), 'r') as file:
                data = self.select_fields(json.load(file))
                yield {
                    "_index": self.index_name,
                    "_source": data,
                }

    def select_fields(self, data):
        return { k:v for k,v in data.items() if k in self.schema_fields }

    def submit(self):
        try:
            success, errors = helpers.bulk(self.client, self.load(), chunk_size=2, request_timeout=30)
            if errors:
                for error in errors:
                    self.logger.log_error('Document failed %s', error)
                    self.records_failed += 1
                    self.records_processed += 1

            self.records_processed += success
            self.logger.info(
                "Successfully indexed: %i documents", success)

        except Exception as e:
            self.logger.error('ERROR: %s', e)


    def create_index_name(self, prefix, timestamp):
        """
        Create a new index for each run.
        """
        return "{}{}".format(prefix, timestamp)


    def create_index(self, index_name):
        """
        Adds a new index to Elasticsearch.
        :param index_name: The new index name
        """
        try:
            self.client.indices.create(index=index_name)
            self.logger.info('Created index %s', index_name)
        except Exception as err:
            self.logger.error('ERROR - Could not create index %s', err)


    def create_alias(self, alias):
        """
        Adds a new alias to Elasticsearch.
        :param alias: The new alias name
        """
        # Add the defined alias if it doesn't already exist on the cluster.
        try:
            if not self.client.indices.exists_alias(name=alias):
                self.logger.info(
                    'Alias not found, adding new alias %s', alias)
                self.client.indices.put_alias(
                    name=alias, index="{}*".format(self.index_prefix))
        except ElasticsearchException as e:
            self.logger.error(
                'ERROR - Could not create alias %s', e)


    def update_alias(self, alias, new_index_name):
        """
        Removes indexes from an alias and adds new indexes.
        :param alias: The alias to be updated
        :param new_index_name: The name of the index to be added to the alias
        """
        # Switch old index with the newly generated index.
        try:
            # Get indices attached to the alias
            alias_state = self.client.indices.get_alias(alias)
            current_indices = list(alias_state.keys())
            # Update alias swapping out old index for new
            self.client.indices.update_aliases({
                "actions": [
                    {"remove": {"indices": current_indices, "alias": alias}},
                    {"add": {"index": new_index_name, "alias": alias}}
                ]
            })
            self.logger.info(
                'Removed %s from alias %s', ", ".join(current_indices), alias)
            self.logger.info(
                'Added %s to alias %s', new_index_name, alias)
        except ElasticsearchException as e:
            self.logger.error('ERROR updating alias %s %o', alias, e)
