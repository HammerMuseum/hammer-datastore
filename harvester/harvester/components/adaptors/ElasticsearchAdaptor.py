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

    def __init__(self, input_data, es_domain, port='443', scheme='https'):
        # Move this out to a config file
        self.es_domain = es_domain
        self.es_index = 'videos'
        self.es_type = 'video'
        self.es = Elasticsearch(
            self.es_domain,
            scheme=scheme,
            port=port
        )
        self.input_data_path = input_data

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

    def process(self):
        self.logger.info('Started processing at {}'.format(time.ctime()))

        # Submit documents for indexing.
        self.submit(self.input_data_path)

        self.logger.info('%i documents indexed' % self.records_processed)
        self.logger.info('%i documents failed' % self.records_failed)

        self.logger.info('Finished processing at {}'.format(time.ctime()))

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
                                           index=self.es_index, doc_type=self.es_type)
            if errors:
                for error in errors:
                    self.logger.log_error('Document failed {}'.format(error))
                    self.records_failed += 1
                    self.records_processed += 1

            self.records_processed += success
            self.logger.info(
                "Successfully indexed: {} documents".format(success))

        except Exception as e:
            self.logger.info("ERROR: {}".format(e))
