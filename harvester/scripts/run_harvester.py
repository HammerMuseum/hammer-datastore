import os
import sys
import logging
import argparse
import traceback
import dateparser
from datetime import datetime

from harvester.harvester.assetbank import AssetBankHarvester
from harvester.components.adaptors import ElasticsearchAdaptor

parser = argparse.ArgumentParser('Run the Asset Bank harvester')
parser.add_argument('--host', dest='url', type=str, help='The URL of the Asset Bank resource.')
parser.add_argument('--asset-type', dest='type', type=str, help='The Asset Bank asset type identifier.')
parser.add_argument('--submit', action='store_true',
                    help='If set, will attempt to submit the harvested records to the search index.')
parser.add_argument('--search-domain', type=str,
                    help='The URL of the search index to populate. Required when using --submit.', dest='search_host')
parser.add_argument('--alias', type=str, dest='alias')
parser.add_argument('--port', type=str, dest='port')
parser.add_argument('--scheme', type=str, dest='scheme')
parser.add_argument('--debug', action='store_true')
parser.add_argument('--short', action='store_true',
                    help='Only harvest 10 records at most.')
parser.add_argument(
    '--since', help='Optional. A date relative to today, can be "2019-01-01" or "yesterday" or "2 days ago" etc', dest='since')
args = parser.parse_args()

# Prepare arguments for passing into the harvester
options = {
    'assetType': args.type,
}

if args.since:
    optional = {'from': dateparser.parse(args.since).strftime('%Y-%m-%d')}
    options = dict(options, **optional)

if args.submit:
    if not args.search_host:
        print('\nError: The "--search-domain" option is required when using "--submit"\n')
        exit(1)

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
                             'harvest.log', 'AssetBankHarvester')

        # If short is specified, only harvest the first 10 pages.
        if args.short:
            harvester.max_requests = 10

        logger.info('Invoking Asset Bank harvester')
        harvester.process()

        # If submit option is enabled also adapt and send
        # the records to registered outputs, presently only Elasticsearch.
        if args.submit:
            logger.info('Running submission processes')
            logger.info('Starting Elasticsearch adaptor')
            
            kwargs = dict(port=args.port, scheme=args.scheme, alias=args.alias)
            adaptor = ElasticsearchAdaptor(
                harvester.current_output_path,
                args.search_host,
                **{k: v for k, v in kwargs.items() if v is not None}
            )
            adaptor.add_logger('../logs',
                               'adaptor.log', 'ElasticsearchAdaptor')
            adaptor.pre_process()
            adaptor.process()
            adaptor.post_process()

            logger.info('Ending submissions processes')

    except Exception as e:
        exc_info = sys.exc_info()
        logger.error('Unexpected Exception: {!s}'.format(e))
        logger.error(traceback.print_exception(*exc_info))
