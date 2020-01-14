import os
import logging
import json
import time
from elasticsearch import Elasticsearch, helpers


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
        self.es = Elasticsearch(
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
        self.logger.info("Using index {}".format(index_name))
        # Create alias if required.
        alias = self.alias
        self.create_alias(alias)
        self.logger.info("Using alias: {}".format(alias))


    def process(self):
        """
        Submit documents for indexing.
        """
        self.logger.info('Started processing at {}'.format(time.ctime()))

        self.submit(self.input_data_path)

        self.logger.info('%i documents indexed' % self.records_processed)
        self.logger.info('%i documents failed' % self.records_failed)

        self.logger.info('Finished processing at {}'.format(time.ctime()))


    def post_process(self):
        """
        Promote the new index to 
        the configured alias.
        """
        # Switch alias to newly created index
        alias = self.alias
        if self.update_alias(alias, self.index_name):
            print('Updated {} alias to point to {}.'.format(alias, self.index_name))


    def load_records(self, directory):
        for filename in os.listdir(directory):
            if filename.endswith('.json'):
                with open(os.path.join(directory, filename), 'r') as f:
                    data = json.load(f)
                    data['_id'] = data['asset_id']
                    yield data


    def submit(self, data_location):
        try:
            success, errors = helpers.bulk(self.es, self.load_records(data_location),
                                           index=self.index_name, doc_type=self.es_type)
            if errors:
                for error in errors:
                    self.logger.log_error('Document failed {}'.format(error))
                    self.records_failed += 1
                    self.records_processed += 1

            self.records_processed += success
            self.logger.info(
                "Successfully indexed: {} documents".format(success))

        except Exception as e:
            self.logger.error("ERROR: {}".format(e))


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
            self.es.indices.create(index=index_name)
            self.logger.info('Created index {}'.format(index_name))
        except Exception as err:
            self.logger.error('ERROR - Could not create index - {}'.format(err))


    def create_alias(self, alias):
        """
        Adds a new alias to Elasticsearch.
        :param alias: The new alias name
        """
        # Add the defined alias if it doesn't already exist on the cluster.
        if not self.es.indices.exists_alias(name=alias):
            self.logger.info(
                'Alias not found, adding new alias - {}'.format(alias))
            self.es.indices.put_alias(name=alias, index="{}*".format(self.index_prefix))


    def update_alias(self, alias, new_index_name):
        """
        Removes indexes from an alias and adds new indexes.
        :param alias: The alias to be updated
        :param new_index_name: The name of the index to be added to the alias
        """
        # Switch old index with the newly generated index.
        try:
            # Get indices attached to the alias
            alias_state = self.es.indices.get_alias(alias)
            current_indices = list(alias_state.keys())
            # Update alias swapping out old index for new
            self.es.indices.update_aliases({
                "actions": [
                    {"remove": {"indices": current_indices, "alias": alias}},
                    {"add": {"index": new_index_name, "alias": alias}}
                ]
            })
            self.logger.info(
                'Removed {} from alias {}'.format(", ".join(current_indices), alias))
            self.logger.info(
                'Added {} to alias {}'.format(new_index_name, alias))
        except Exception as err:
            self.logger.error('ERROR updating alias {} - {}'.format(alias, err))
