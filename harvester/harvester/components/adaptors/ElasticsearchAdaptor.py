import os 
import logging
import json
import confuse
from elasticsearch import Elasticsearch, helpers

class ElasticsearchAdaptor  ():
    """
    The location of the data to be adapted for Elasticsearch.
    """
    logger = None
    config = None

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


    def process(self):
        # self.logger.info('Beginning Elasticsearch Adaptor processing')
        # validation
        # send to elasticsearch
        self.submit(self.input_data_path)

        # Tidy up run
        # self.logger.info('Ending processing run')
        # self.logger.info('%i records processed' % self.records_processed)
        # self.logger.info('%i records failed' % self.records_failed)

        # if self.success:
        #     print('Processing succeeded')
        # else:
        #     print('Processing failed')


    def load_records(self, directory):
        for filename in os.listdir(directory):
            if filename.endswith('.json'):
                with open(os.path.join(directory, filename), 'r') as f:
                    data = json.load(f)
                    data['_id'] = data['asset_id']
                    yield data


    def submit(self, data_location):
        try:
            response = helpers.bulk(self.es, self.load_records(data_location),
                        index=self.es_index, doc_type=self.es_type)
            print("Successfully processed: {} records".format(response[0]))
        except Exception as e:
            print("ERROR:", e)
