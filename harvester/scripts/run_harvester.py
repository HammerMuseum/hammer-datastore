import os
import sys
import logging
import argparse
import traceback
import dateparser
from datetime import datetime
from harvester.harvester.assetbank import AssetBankHarvester

parser = argparse.ArgumentParser('Run the Asset Bank harvester')
parser.add_argument('--short', action='store_true')
parser.add_argument('--debug', action='store_true')
parser.add_argument('--url', type=str, required=True, help='The URL of the Asset Bank resource.')
parser.add_argument('--es_domain', type=str, required=True, help='The URL of Elasticsearch.')
parser.add_argument('--api_token', type=str, required=True, help='The API token for the datastore.')
parser.add_argument('--datastore_url', type=str, required=True, help='The URL of datastore API endpoint.')
parser.add_argument('--type', type=str, required=True,
                    help=' Required. The Asset Bank asset type identifier.', dest='type')
parser.add_argument('--since', help='Optional. A date relative to today, can be "2019-01-01" or "yesterday" or "2 days ago" etc', dest='since')
args = parser.parse_args()

# Prepare arguments for passing into the harvester
options = {
    'assetType': args.type,
    'es_domain': args.es_domain,
    'api_token': args.api_token,
    'datastore_url': args.datastore_url
}

if args.since:
    optional = {'from': dateparser.parse(args.since).strftime('%Y-%m-%d')}
    options = dict(options, **optional)

if __name__ == '__main__':
    logger = logging.getLogger('run_assetbank')
    logger.setLevel(AssetBankHarvester.log_level)

    sh = logging.StreamHandler()
    sh.setLevel(AssetBankHarvester.log_level)
    sh.setFormatter(AssetBankHarvester.log_formatter)

    logger.addHandler(sh)

    fh = logging.FileHandler('../logs/run_assetbank.log')
    fh.setLevel(AssetBankHarvester.log_level)
    fh.setFormatter(AssetBankHarvester.log_formatter)

    logger.addHandler(fh)

    try:
        harvester = AssetBankHarvester(args.url, options)
        harvester.output_base = '../data'
        harvester.output_prefix = 'assetbank'

        if args.debug:
            harvester.log_level = logging.DEBUG

        harvester.add_logger('../logs',
                             'assetbank.log', 'AssetBankHarvester')

        # If short is specified, only harvest the first 10 pages.
        if args.short:
            harvester.max_requests = 10

        logger.info('Invoking Asset Bank harvester')
        harvester.process()
    except Exception as e:
        exc_info = sys.exc_info()
        logger.error('Unexpected Exception: {!s}'.format(e))
        logger.error(traceback.print_exception(*exc_info))
