import os
import re
import csv
import json
import yaml
import logging
import requests
import datetime
import sys
from dotenv import load_dotenv
from pathlib import Path  
from tqdm import tqdm
from lxml import etree
from collections import OrderedDict
from harvester.harvester import HarvesterBase
from harvester.processors import DelimiterProcessor


class AssetBankHarvester(HarvesterBase):
    version = 0.1

    schema = None

    validator = None

    errors = 0

    """
    The current location of the output.
    This may change during the run as directories are renamed.
    """
    current_output_path = None

    def __init__(self, host, options):
        HarvesterBase.__init__(self)
        
        env_path = Path(__file__).parent.parent.absolute() / '.env'
        load_dotenv(dotenv_path=env_path)

        self.host = host
        self.access_token = None

        self.base_dir = os.path.dirname(os.path.abspath(__file__))
        self.harvest_uri = "{}/{}".format(self.host, 'rest/asset-search')
        self.assetType = options['assetType']
        self.docs = []

        split_fields = [
            'tags',
        ]

        self.processors = [
            DelimiterProcessor(self, delimiter=',', fields=split_fields)
        ]


    def init_auth(self):
        """
        Setup authentication necessary to communicate with the Asset Bank API.
        """
        try:
            token_url = "{}{}".format(self.host, '/oauth/token')
            headers = {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
            data = {
                'grant_type': 'password',
                'client_id': os.getenv('AB_CLIENT_ID'),
                'client_secret': os.getenv('AB_CLIENT_SECRET'),
                'username': os.getenv('AB_USERNAME'),
                'password': os.getenv('AB_PASSWORD'),
            }
            response = requests.post(token_url, headers=headers, data=data)
            response.raise_for_status()
            content = response.json()
            self.access_token = content['access_token']
        except requests.HTTPError as e:
            self.logger.error('Failed to authenticate with API. Check credentials. {}'.format(e))
            exit()

    def harvest(self):
        # Add a log handler for the run
        run_handler = logging.FileHandler(
            os.path.join(self.current_output_path, 'run.log'))
        run_handler.setLevel(self.log_level)
        run_handler.setFormatter(self.log_formatter)
        self.logger.addHandler(run_handler)

        # Begin the harvest run
        self.logger.info('Beginning Harvester run')

        self.init_auth()
        self.do_harvest()

        self.logger.info('Ending Harvester run')
        self.logger.info('%i records harvested' % self.records_processed)
        self.logger.info('%i records failed' % self.records_failed)

        if self.records_processed > 0:
            self.success = (self.records_succeeded /
                            self.records_processed) > 0.9
            self.logger.info('Harvest succeeded')
        else:
            self.success = False
            self.logger.info('Harvest failed')

        # Remove the log handler for the run
        run_handler.close()
        self.logger.removeHandler(run_handler)


    def postprocess(self):
        """
        Postprocessing callback.
        """
        self.write_summary()


    def do_harvest(self):
        """
        Main harvesting function.
        """

        response = requests.get(
            self.harvest_uri,
            headers={'Authorization': 'Bearer {}'.format(self.access_token)},
            params={'assetTypeId': self.assetType},
        )
        root = etree.fromstring(response.content)
        assets = root.xpath('//assetSummary')

        # Iterate over all resources
        for asset in assets:
            identifier = asset.xpath('id')[0].text
            self.logger.debug('Processing data record {!s}'.format(identifier))

            # process the record
            asset_url = asset.xpath('fullAssetUrl')[0].text
            response = requests.get(asset_url)
            record = response.content

            json_record = self.get_record_fields(record, identifier)
            self.preprocess_record(json_record)
            record_success = self.do_record_harvest(json_record, identifier)

            self.records_processed += 1

            if record_success:
                self.records_succeeded += 1
            else:
                self.records_failed += 1


    def do_record_harvest(self, record, identifier):
        file_name = '{!s}.json'.format(identifier)
        output = record
        if output:
            self.docs.append(output)
            return self.write_record(output, file_name)


    def preprocess_record(self, record):
        """Run preprocessors on the current record."""
        for processor in self.processors:
            processor.process(record)


    def get_record_fields(self, record, identifier):
        """
        List of fields could be moved to configuration

        Attributes make up the majority of the fields.
        The dictionary below maps Asset Bank attributes to 
        property names used in harvested data structure.
        """
       
        # Map for all attributes from which we want the content
        # of the "value" property in the Asset Bank API response.
        attributes = {
            'asset_id': 'assetId',
            'title': 'Title',
            'description': 'Description',
            'date_recorded': 'Date Created',
            'duration': 'Duration',
            'tags': 'Keywords',
        }

        try:
            for key, value in attributes.items():
                query = '//attributes/attribute[name[contains(text(), "{}")]]/value/text()'.format(
                    value)
                attribute_value = root.xpath(query)
                if len(attribute_value):
                    json_record[key] = attribute_value[0]
                else:
                    json_record[key] = ""
            
            # @todo move to validate_record method
            date = json_record['date_recorded'] or '01/01/1970 00:00:00'
            json_record['date_recorded'] = datetime.datetime.strptime(
                date, '%d/%m/%Y %H:%M:%S').strftime('%Y-%m-%d')

            json_record['asset_id'] = int(json_record['asset_id'])
            return json_record

        except Exception as e:
            self.logger.info(
                'Failed to process asset {}: {}'.format(identifier, e))


    def write_record(self, record, file_name):
        """
        Overridden implementation of write_record 
        for writing JSON output to disk.
        """
        record_path = os.path.join(self.current_output_path, file_name)

        if os.path.exists(record_path):
            self.logger.error(
                'Record already exists at {!s}. Skipping.'.format(record_path))
            return False

        try:
            os.makedirs(os.path.dirname(record_path), exist_ok=True)
            with open(record_path, 'w', encoding='utf-8') as f:
                json.dump(record, f, ensure_ascii=False, indent=4)
            return True
        except (IOError, OSError) as e:
            self.logger.error(
                'Error writing record to {!s}. Skipping.'.format(record_path))
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
