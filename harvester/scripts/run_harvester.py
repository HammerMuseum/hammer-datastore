import os
import sys
import logging
import argparse
import traceback
import dateparser
from pathlib import Path
from harvester.harvester.assetbank import AssetBankHarvester
from harvester.components.adaptors import ElasticsearchAdaptor, LocalRepositoryAdaptor

parser = argparse.ArgumentParser("Run the Asset Bank harvester")

parser.add_argument(
    "--host", dest="url", type=str, help="The URL of the Asset Bank resource."
)

parser.add_argument(
    "--asset-type", dest="type", type=str, help="The Asset Bank asset type identifier."
)

parser.add_argument(
    "--assets",
    type=int,
    help="Only harvest asset with ID."
)

parser.add_argument(
    "--submit",
    action="store_true",
    help="If set, will attempt to submit the harvested records to the search index.",
)

parser.add_argument(
    "--data-dir",
    dest="data_path",
    type=str,
    help="If set, will not run a harvest, \
                        will use most recently harvested data at supplied path. \
                        If no data found, will exit.",
)

parser.add_argument(
    "--search-domain",
    type=str,
    help="The URL of the search index to populate. Required when using --submit.",
    dest="search_host",
)

parser.add_argument("--alias", type=str, dest="alias")

parser.add_argument("--port", type=str, dest="port")

parser.add_argument("--scheme", type=str, dest="scheme")

parser.add_argument("--debug", action="store_true")

parser.add_argument(
    "--limit",
    type=int,
    dest="limit",
    help="Only harvest the specified number of records.",
)

parser.add_argument(
    "--since",
    help='Optional. A date relative to today, can be "2019-01-01" or "yesterday" or "2 days ago" etc',
    dest="since",
)

parser.add_argument("--storage", help="Optional. Path to local storage.")

args = parser.parse_args()

# Prepare arguments for passing into the harvester
options = {
    "assetType": args.type,
    "assetIds": args.assets,
}

if args.since:
    optional = {"from": dateparser.parse(args.since).strftime("%Y-%m-%d")}
    options = dict(options, **optional)

if args.data_path:
    DATA_DIR = Path(args.data_path)
    all_subdirs = [d for d in DATA_DIR.absolute().iterdir() if d.is_dir()]
    if all_subdirs:
        data_path = str(max(all_subdirs, key=os.path.getmtime))
    else:
        print('\nError: No harvested data found."\n')
        exit(1)
else:
    data_path = None

if args.submit:
    if not args.search_host:
        print(
            '\nError: The "--search-domain" option is required when using "--submit"\n'
        )
        exit(1)

if not args.storage:
    args.storage = "/tmp"

if __name__ == "__main__":
    logger = logging.getLogger("run_assetbank")
    logger.setLevel(AssetBankHarvester.log_level)

    sh = logging.StreamHandler()
    sh.setLevel(AssetBankHarvester.log_level)
    sh.setFormatter(AssetBankHarvester.log_formatter)

    logger.addHandler(sh)

    fh = logging.FileHandler("../logs/run_assetbank.log")
    fh.setLevel(AssetBankHarvester.log_level)
    fh.setFormatter(AssetBankHarvester.log_formatter)

    logger.addHandler(fh)

    try:
        submit = args.submit

        if data_path is None:
            harvester = AssetBankHarvester(args.url, options)
            harvester.output_base = "../data"
            harvester.output_prefix = "assetbank"

            if args.debug:
                harvester.log_level = logging.DEBUG

            harvester.add_logger("../logs", "harvest.log", "AssetBankHarvester")

            # If limit is specified, only harvest the first x items.
            if args.limit:
                harvester.max_items = args.limit
                logger.info("Limiting harvest to %s records", args.limit)

            logger.info("Invoking Asset Bank harvester")
            harvester.process()

            data_path = harvester.current_output_path

            if not harvester.success:
                submit = False
                logger.info("Harvest failed. Submission cancelled and ending run.")

        # If submit option is enabled also process
        # records through registered adaptors.
        if submit:
            logger.info("Running submission processes")
            logger.info("Starting Elasticsearch adaptor")

            kwargs = dict(port=args.port, scheme=args.scheme, alias=args.alias)
            adaptors = [
                # Adapts harvest data for elasticsearch
                ElasticsearchAdaptor(
                    data_path,
                    args.search_host,
                    **{k: v for k, v in kwargs.items() if v is not None}
                ),
                # Adapts harvest data for local data repository (transcripts)
                LocalRepositoryAdaptor(data_path, args.storage,),
            ]
            for adaptor in adaptors:
                if args.debug:
                    adaptor.log_level = logging.DEBUG
                adaptor.add_logger("../logs", "adaptor.log", "Adaptors")
                adaptor.pre_process()
                adaptor.process()
                adaptor.post_process()

            logger.info("Ending submissions processes")

    except Exception as e:
        exc_info = sys.exc_info()
        logger.error("Unexpected Exception: {!s}".format(e))
        logger.error(traceback.print_exception(*exc_info))
