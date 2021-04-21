import os
import logging
import datetime
import concurrent.futures
import threading
import random
import time
import re
from pathlib import Path
import json
import yaml
import requests
from requests.adapters import HTTPAdapter
from urllib3.util.retry import Retry
from dotenv import load_dotenv
from lxml import etree
from harvester.harvester import HarvesterBase
from harvester.processors import (
    DelimiterProcessor,
    TranscriptionProcessor,
    FriendlyUrlProcessor,
    DurationProcessor,
)


class AssetBankHarvester(HarvesterBase):
    version = 0.1

    schema = None

    validator = None

    errors = 0

    max_items = 10000

    playlists = {}

    playlist_user = 14

    log_formatter = logging.Formatter(' %(asctime)s - %(threadName)s - %(name)s - %(levelname)s - %(message)s')

    """
    The current location of the output.
    This may change during the run as directories are renamed.
    """
    current_output_path = "None"

    thread_local = threading.local()

    def __init__(self, host, options):
        HarvesterBase.__init__(self)

        env_path = Path(__file__).parent.absolute() / ".env"
        load_dotenv(dotenv_path=env_path)

        self.summary = None
        self.host = host
        self.access_token = None
        self.init_auth()

        self.playlists_user_uri = "{}/rest/users/{}/lightboxes".format(
            self.host, self.playlist_user
        )

        self.base_dir = os.path.dirname(os.path.abspath(__file__))
        self.harvest_uri = "{}/{}".format(self.host, "rest/asset-search")
        self.asset_type = options["assetType"]
        self.assetIds = options['assetIds']
        self.docs = []
        self.slugs = []

        split_fields = ["tags", "speakers", "topics", "in_playlists"]

        transcription_fields = [
            "transcription",
        ]

        slug_field = ["title"]

        duration_field = ["video_url"]

        self.processors = [
            DelimiterProcessor(self, fields=split_fields),
            TranscriptionProcessor(
                self, os.getenv("TRINT_API_KEY"), fields=transcription_fields
            ),
            FriendlyUrlProcessor(self, fields=slug_field),
            DurationProcessor(self, fields=duration_field),
        ]

    def get_session(self):
        if not hasattr(self.thread_local, "session"):
            self.thread_local.session = self.setup_http_session()
        return self.thread_local.session

    def setup_http_session(self):
        """
        Helper to setup http utilties
        """
        strategy = Retry(
            total=3,
            status_forcelist=[429, 500, 502, 503, 504],
            method_whitelist=["GET"],
            backoff_factor=1,
        )
        adapter = HTTPAdapter(max_retries=strategy)
        http = requests.Session()
        http.mount("https://", adapter)
        http.mount("http://", adapter)
        return http

    def init_auth(self):
        """
        Setup authentication necessary to communicate with the Asset Bank API.
        """
        try:
            print("Attempting to Authenticate with Asset Bank")
            token_url = "{}{}".format(self.host, "/oauth/token")
            headers = {"Content-Type": "application/x-www-form-urlencoded"}
            data = {
                "grant_type": "password",
                "client_id": os.getenv("AB_CLIENT_ID"),
                "client_secret": os.getenv("AB_CLIENT_SECRET"),
                "username": os.getenv("AB_USERNAME"),
                "password": os.getenv("AB_PASSWORD"),
            }
            response = requests.post(token_url, headers=headers, data=data)
            response.raise_for_status()
            content = response.json()
            self.access_token = content["access_token"]
            print("Authentication successful")
        except requests.HTTPError as error:
            print("Failed to authenticate with API. Check credentials. %s", error)
            exit()

    def harvest(self):
        # Add a log handler for the run
        run_handler = logging.FileHandler(
            os.path.join(self.current_output_path, "run.log")
        )
        run_handler.setLevel(self.log_level)
        run_handler.setFormatter(self.log_formatter)
        self.logger.addHandler(run_handler)

        # Begin the harvest run
        self.logger.info("Beginning Harvester run")

        self.do_harvest()

        self.logger.info("Ending Harvester run")
        self.logger.info("%i records harvested", self.records_processed)
        self.logger.info("%i records failed", self.records_failed)

        if self.records_processed > 0:
            self.success = (self.records_succeeded / self.records_processed) > 0.9
            self.logger.info("Harvest succeeded")
        else:
            self.success = False
            self.logger.info("Harvest failed")

        # Remove the log handler for the run
        run_handler.close()
        self.logger.removeHandler(run_handler)

    def get_playlist_data(self):
        """
        This implementation retrieves
        playlist information from the DAMS and
        then uses this information further along
        the pipeline, augmenting the metadata
        harvested for each asset.
        """
        playlists = {}
        response = requests.get(
            self.playlists_user_uri,
            headers={
                "Authorization": "Bearer {}".format(self.access_token),
                "Accept": "application/json",
            },
        )

        for playlist in response.json():
            playlist = {
                "id": playlist["id"],
                "name": playlist["name"],
                "contents": self.get_playlist_contents(playlist["lightboxContentsUrl"]),
            }
            playlists[playlist["id"]] = playlist

        return playlists

    def get_playlist_contents(self, url):
        response = requests.get(
            url,
            headers={
                "Authorization": "Bearer {}".format(self.access_token),
                "Accept": "application/json",
            },
        )
        return [
            att["value"]
            for a in response.json()
            for att in a["attributes"]
            if att["name"] == "assetId"
        ]

    def preprocess(self):
        """
        Preprocessing callback.
        """
        self.playlists = self.get_playlist_data()

    def postprocess(self):
        """
        Postprocessing callback.
        """
        self.write_summary()

    def get_asset_list(self):
        """
        Returns an iterator for all requested assets.
        """

        current_harvest_uri = "{}".format(self.harvest_uri)
        session = self.get_session()

        params = {
            "assetTypeId": self.asset_type,
            "attribute_21": 'Active',
        }

        if self.assetIds:
            params = {
                'assetIds': self.assetIds
            }

        page_number = 0
        assets = []

        while page_number == 0 or len(assets):
            params['page'] = page_number

            self.logger.debug("Fetching page {}".format(params['page']))

            response =  session.get(
                current_harvest_uri,
                params=params
            )

            page_number = page_number + 1

            root = etree.fromstring(response.content)
            assets = root.xpath("//assetSummary")
            yield [asset for asset in assets]

    def do_harvest(self):
        """
        Main harvesting function.

        Orchetrates harvest by starting worker threads
        for gathering individual assets.
        """
        read = 0
        with concurrent.futures.ThreadPoolExecutor(max_workers=2) as executor:
            for page in self.get_asset_list():
                if read >= self.max_items:
                    self.logger.info("Harvesting reached max limit")
                    break

                for asset in page:
                    executor.submit(self.harvest_asset, asset)
                    read += 1
                    if read >= self.max_items:
                        self.logger.info("Harvesting reached max limit")
                        break

    def harvest_asset(self, asset):
        """
        Controls the operation to fetch a single asset.
        """

        identifier = asset.xpath("id")[0].text
        self.logger.info("Processing data record %s", identifier)

        # process the record
        asset_url = asset.xpath("fullAssetUrl")[0].text

        session = self.get_session()
        response = session.get(asset_url)

        record = response.content

        try:
            json_record = self.get_record_fields(record, identifier)
            self.preprocess_record(json_record)
            self.add_playlist_metadata(json_record, identifier)

            if self.validate_record(json_record):
                record_success = self.do_record_harvest(json_record, identifier)
                self.records_processed += 1

                if record_success:
                    self.records_succeeded += 1
                else:
                    self.records_failed += 1
            else:
                self.records_failed += 1
        except Exception as error:
            self.logger.info("Failed to retrieve metadata %s: %s", identifier, error)
            self.records_failed += 1

    def do_record_harvest(self, record, identifier):
        """
        Controls the record harvest operation.
        """
        file_name = "{!s}.json".format(identifier)
        output = record
        if output:
            self.docs.append(output)
            return self.write_record(output, file_name)

    def preprocess_record(self, record):
        """Run preprocessors on the current record."""
        for processor in self.processors:
            processor.process(record)

    def add_playlist_metadata(self, record, identifier):
        """
        Adds a playlist data object for each record
        """
        playlists = []
        for pid, data in self.playlists.items():
            for index, asset_id in enumerate(data["contents"]):
                if asset_id == identifier:
                    playlists.append(
                        {"id": pid, "name": data["name"], "position": index}
                    )
        record["playlists"] = playlists

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
            "asset_id": "assetId",
            "title": "Title",
            "description": "Description",
            "date_recorded": "Date",
            "tags": "Tags",
            "transcription": "Transcription ID",
            "speakers": "People",
            "topics": "Topics",
            "in_playlists": "Playlists",
            "quote": "Featured Quote",
        }

        output = {}
        root = etree.fromstring(record)
        for key, value in attributes.items():
            field = "name"
            query = '//attributes/attribute[{}[text()="{}"]]/value/text()'.format(
                field, value
            )
            attribute_value = root.xpath(query)
            if len(attribute_value):
                output[key] = str(attribute_value[0])
            else:
                output[key] = ""

        date = output["date_recorded"]
        output["date_recorded"] = datetime.datetime.strptime(
            date, "%d/%m/%Y %H:%M:%S"
        ).isoformat()

        output["asset_id"] = int(output["asset_id"])

        # Get some non-attribute properties.
        output["video_url"] = root.xpath("//asset/contentUrl/text()")[0]
        output["thumbnail_url"] = root.xpath("//asset/previewUrl/text()")[0]
        thumbnailId = re.match(".*file=([a-z\d]+)\.", output["thumbnail_url"]).group(
            1
        )
        output["thumbnailId"] = thumbnailId

        return output

    def write_record(self, record, file_name):
        """
        Overridden implementation of write_record
        for writing JSON output to disk.
        """
        record_path = os.path.join(self.current_output_path, file_name)

        if os.path.exists(record_path):
            self.logger.error("Record already exists at %s. Skipping.", record_path)
            return False

        try:
            os.makedirs(os.path.dirname(record_path), exist_ok=True)
            with open(record_path, "w", encoding="utf-8") as f:
                json.dump(record, f, ensure_ascii=False, separators=(",", ":"))
            return True
        except (IOError, OSError) as error:
            self.logger.error("Error writing record to %s. Skipping.", record_path)
            self.logger.error("The error was: %s", error)
            return False

    def validate_record(self, record):
        """
        Custom validation checks for this implementation.
        """
        present = [
            "asset_id",
            "date_recorded",
            "description",
            "duration",
            "in_playlists",
            "playlists",
            "quote",
            "speakers",
            "thumbnailId",
            "thumbnail_url",
            "title",
            "title_slug",
            "tags",
            "topics",
            "video_url",
        ]

        required = [
            "asset_id",
            "date_recorded",
            "description",
            "duration",
            "thumbnailId",
            "thumbnail_url",
            "title",
            "title_slug",
            "video_url",
        ]

        for field in present:
            try:
                record[field]
            except KeyError as e:
                self.logger.error(
                    "Record {} failed validation: missing field {}.".format(
                        record["asset_id"], field
                    )
                )
                return False

        for field in required:
            if not record[field]:
                self.logger.error(
                    "Record {} failed validation: required field {} has no data.".format(
                        record["asset_id"], field
                    )
                )
                return False

        return True

    def write_summary(self):
        summary = {
            "type": self.__class__.__name__,
            "version": self.version,
            "start": self.start_time,
            "end": self.end_time,
            "processed": self.records_processed,
            "succeeded": self.records_succeeded,
            "failed": self.records_failed,
            "errors": self.errors,
            "success": "{!s}".format(self.success),
        }

        self.summary = summary

        summary_path = os.path.join(self.current_output_path, "summary.log")

        with open(summary_path, "w") as fh:
            fh.write(yaml.dump(summary, default_flow_style=False))
