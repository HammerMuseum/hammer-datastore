import os
import re
import csv
import json
import yaml
import logging
import requests
import datetime
import sys
from tqdm import tqdm
from lxml import etree
from collections import OrderedDict
from harvester.harvester import HarvesterBase
from harvester.processors import DelimiterProcessor
from elasticsearch import Elasticsearch, helpers


class AssetBankHarvester(HarvesterBase):
    version = 0.1

    schema = None

    validator = None

    errors = 0

    """
    The current location of the mill output.
    This may change during the run as directories are renamed.
    """
    current_output_path = None


    def __init__(self, url, options):
        HarvesterBase.__init__(self)
        self.base_dir = os.path.dirname(os.path.abspath(__file__))
        self.url = url
        self.assetType = options['assetType']
        self.docs = []
        self.api_token = options['api_token']
        self.datastore_url = options['datastore_url']
        self.es_domain = options['es_domain']
        self.es_index = "videos"
        self.es_type = "video"
        self.es = Elasticsearch(
            self.es_domain,
            scheme="https",
            port=443,
        )

    def harvest(self):
        # Add a log handler for the run
        run_handler = logging.FileHandler(os.path.join(self.current_output_path, 'run.log'))
        run_handler.setLevel(self.log_level)
        run_handler.setFormatter(self.log_formatter)
        self.logger.addHandler(run_handler)

        # Begin the harvest run
        self.logger.info('Beginning Harvester run')

        self.do_harvest()

        self.logger.info('Ending Harvester run')
        self.logger.info('%i records processed' % self.records_processed)
        self.logger.info('%i records failed' % self.records_failed)

        if self.records_processed > 0:
            self.success = (self.records_succeeded / self.records_processed) > 0.9
            self.logger.info('Harvest succeeded')
        else:
            self.success = False
            self.logger.info('Harvest failed')

        # Remove the log handler for the run
        run_handler.close()
        self.logger.removeHandler(run_handler)


    def postprocess(self):
        """Postprocessing callback."""
        self.send_to_elasticsearch()
        self.send_to_datastore(self.current_output_path)
        # Write out the summary
        self.write_summary()


    def load_json(self, directory):
        for filename in os.listdir(directory):
            if filename.endswith('.json'):
                with open(os.path.join(directory, filename), 'r') as f:
                    data = json.load(f)
                    data['_id'] = data['asset_id']
                    yield data


    def send_to_elasticsearch(self):
        helpers.bulk(self.es, self.load_json(self.current_output_path),
                     index=self.es_index, doc_type=self.es_type)


    def send_to_datastore(self, directory):

        for filename in os.listdir(directory):
            if filename.endswith('.json'):
                with open(os.path.join(directory, filename), 'r') as f:
                    payload = json.load(f)
                    params = {
                        'api_token': self.api_token
                    }
                    headers = {'Accept': 'application/json'}
                    r = requests.get(self.datastore_url + "/" + str(payload['asset_id']), headers=headers)
                    json_response = r.json()
                    if json_response['asset_id'] > 0:
                        r = requests.put(self.datastore_url + "/" + str(payload['asset_id']), headers=headers, params=params, data=payload)
                    else:
                        r = requests.post(self.datastore_url + "/" + str(payload['asset_id']), headers=headers, params=params, data=payload)
                    # if not r.ok:
                    #     print(r.text)
                    #     print("Failed to process " + filename)

    def do_harvest(self):
        """
        Main harvesting function.
        """
        
        response = requests.get(
            self.url,
            params={'assetTypeId': self.assetType},
        )
        root = etree.fromstring(response.content)
        assets = root.xpath('//assetSummary')
        size = len(assets)

        # Iterate over all resources
        with tqdm(total=int(size)) as progress:
            for asset in assets:
                # self.logger.debug('Processing data record {!s}'.format(record.header.identifier)

                # process the record
                asset_url = asset.xpath('fullAssetUrl')[0].text
                identifier = asset.xpath('id')[0].text
                record = requests.get(asset_url)
                record_success = self.do_record_harvest(record.content, identifier)

                self.records_processed += 1

                if record_success:
                    self.records_succeeded += 1
                else:
                    self.records_failed += 1
                
                # Update CLI progress bar
                progress.update(1)


    def do_record_harvest(self, record, identifier):
        file_name = '{!s}.json'.format(identifier)
        output = self.format_record(record, identifier)
        self.docs.append(output)
        return self.write_record(output, file_name)


    def format_record(self, record, identifier):
        # get fields we want
        json_record = {}
        root = etree.fromstring(record)
        
        json_record['video_url'] = root.xpath('//asset/contentUrl/text()')[0]
        json_record['thumbnail_url'] = root.xpath('//asset/thumbnailUrl/text()')[0]

        attributes = {
            'asset_id': 'assetId',
            'title': 'Title',
            'description': 'Description',
            'date_recorded': 'Date Created',
            'duration': 'Duration',
        }

        for key, value in attributes.items():
            query = '//attributes/attribute[name[contains(text(), "{}")]]/value/text()'.format(value)
            attribute_value = root.xpath(query)
            if len(attribute_value):
                json_record[key] = attribute_value[0]
            else:
                json_record[key] = ""
        
        if json_record['date_recorded'] is "":
            date = "20/11/2019 00:00:00"
        else:
            date = json_record['date_recorded']
        json_record['date_recorded'] = datetime.datetime.strptime(date, '%d/%m/%Y %H:%M:%S').strftime('%Y-%m-%d')

        if json_record['duration'] is "":
            json_record['duration'] = "00:00:00"
        
        json_record['asset_id'] = int(json_record['asset_id'])
        return json_record


    def write_record(self, record, file_name):
        """
        Overridden implementation of write_record 
        for writing JSON output to disk.
        """
        record_path = os.path.join(self.current_output_path, file_name)

        if os.path.exists(record_path):
            self.logger.error('Record already exists at {!s}. Skipping.'.format(record_path))
            return False

        try:
            os.makedirs(os.path.dirname(record_path), exist_ok=True)
            with open(record_path, 'w', encoding='utf-8') as f:
                json.dump(record, f, ensure_ascii=False, indent=4)

            return True
        except (IOError, OSError) as e:
            self.logger.error('Error writing record to {!s}. Skipping.'.format(record_path))
            self.logger.error('The error was: {!s}'.format(e))
            return False


    def validate_record(self, record):
        return True


    def write_summary(self):
        summary = {
            'type': self.__class__.__name__,
            'version': self.version,
            'start': self.start_time,
            'end': self.end_time,
            'processed': self.records_processed,
            'succeeded': self.records_succeeded,
            'failed': self.records_failed,
            'errors': self.errors,
            'success': '{!s}'.format(self.success)
        }

        summary_path = os.path.join(self.current_output_path, 'summary.log')

        with open(summary_path, 'w') as fh:
            fh.write(yaml.dump(summary, default_flow_style=False))

