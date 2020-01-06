import os 
import logging
import json
from elasticsearch import Elasticsearch, helpers

class ElasticsearchAdaptor():
    """
    The location of the data to be adapted for Elasticsearch.
    """
    input_data = None

    """Logger as set by the add_logging() method."""
    logger = None

    """Log level for logging module."""
    log_level = logging.INFO

    """Formatter for logging messages."""
    log_formatter = logging.Formatter(
        '%(asctime)s - %(name)s - %(levelname)s - %(message)s')

    def __init__(self, options):
        # load config to provide all of the following property values
        self.es_domain = options['es_domain']
        self.es_index = "videos"
        self.es_type = "video"
        self.es = Elasticsearch(
            self.es_domain,
            scheme="https",
            port=443,
        )
        

    def add_logging(self, log_directory, log_file, log_name='elasticsearch'):
        """
        Add a basic logger.
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
        self.logger.info('Beginning Elasticsearch Adaptor processing')
        # load records for processing
        records = load_records()
        # validation
        # send to elasticsearch
        self.send_to_elasticsearch()

        # Tidy up run
        self.logger.info('Ending processing run')
        self.logger.info('%i records processed' % self.records_processed)
        self.logger.info('%i records failed' % self.records_failed)

        if self.success:
            self.logger.info('Harvest succeeded')
        else:
            self.logger.info('Harvest failed')


    def load_records(self, directory):
        for filename in os.listdir(directory):
            if filename.endswith('.json'):
                with open(os.path.join(directory, filename), 'r') as f:
                    data = json.load(f)
                    data['_id'] = data['asset_id']
                    yield data


    def send_to_elasticsearch(self):
        helpers.bulk(self.es, self.load_json(self.current_output_path),
                     index=self.es_index, doc_type=self.es_type)
