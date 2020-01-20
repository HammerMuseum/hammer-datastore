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

    def __init__(self, input_data, es_domain, port='443', scheme='https', alias='videos'):
        self.input_data_path = input_data

        # @todo Move to a config file
        self.timestamp = int(time.time())
        self.alias = alias
        self.index_prefix = 'video_'

        self.es_type = '_doc'
        self.client = Elasticsearch(
            es_domain,
            scheme=scheme,
            port=port
        )

        self.index_name = self.create_index_name(self.index_prefix, self.timestamp)

    def add_logger(self, log_directory, log_file, log_name='adaptor'):
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

        self.submit(self.input_data_path)

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


    def load_records(self, directory):
        for filename in os.listdir(directory):
            if filename.endswith('.json'):
                with open(os.path.join(directory, filename), 'r') as f:
                    data = json.load(f)
                    data['_id'] = data['asset_id']
                    yield data


    def submit(self, data_location):
        try:
            success, errors = helpers.bulk(self.client, self.load_records(data_location),
                                           index=self.index_name, doc_type=self.es_type)
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
