import os
import logging
import json
import time
from datetime import datetime, timedelta
from elasticsearch import Elasticsearch, helpers


class ElasticsearchAdaptor:
    """Logger as set by the add_logging() method."""

    logger = None

    """Log level for logging module."""
    log_level = logging.INFO

    """Formatter for logging messages."""
    log_formatter = logging.Formatter(
        "%(asctime)s - %(name)s - %(levelname)s - %(message)s"
    )

    records_processed = 0
    records_succeeded = 0
    records_failed = 0

    """
    The adaptor will attempt to retrieve only the
    following defined fields from the input data.
    These will most likely match the fields used in your
    Elasticsearch mapping. Additional fields will still be
    indexed and Elasticsearch will make a best guess at
    field type settings.
    """
    schema_fields = [
        "asset_id",
        "date_recorded",
        "description",
        "duration",
        "in_playlists",
        "playlists",
        "program_series",
        "quote",
        "speakers",
        "thumbnailId",
        "thumbnail_url",
        "title",
        "title_slug",
        "transcription",
        "transcription_txt",
        "tags",
        "topics",
        "video_url",
    ]

    def __init__(
        self, data_path, es_client, alias="videos", update=False, cleanup=False
    ):
        self.input_data_path = data_path

        # @todo Move to a config file
        self.timestamp = int(time.time())
        self.alias = alias
        self.index_prefix = "video_"
        self.update = update
        self.cleanup = cleanup
        self.client: Elasticsearch = es_client

        if self.update:
            self.index_name = self.establish_index_name(self.alias)
        else:
            self.index_name = "{}{}".format(self.index_prefix, int(time.time()))

    def add_logger(self, log_directory, log_file, log_name="elasticsearch-adaptor"):
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
        try:
            index_name = self.index_name

            if not self.update:
                self.create_index(index_name)

            self.logger.info("Using index %s", index_name)

            # Create alias if required.
            alias = self.alias
            self.prepare_alias(alias)
            self.logger.info("Using alias: %s", alias)
        except Exception as e:
            self.logger.error("Error: could not create index -  {}".format(e))
            exit(1)

    def process(self):
        """
        Submit documents for indexing.
        """
        self.logger.info("------------------------")
        self.logger.info("Started processing at %s", time.ctime())
        self.logger.info("------------------------")

        self.submit()

        if self.records_processed > 0:
            self.success = (self.records_succeeded / self.records_processed) > 0.9
        else:
            self.success = False

        if self.success:
            self.logger.info("Elasticsearch adaptor completed successfully.")
        else:
            self.logger.info("Elasticsearch adaptor failed.")
            self.success = False

        self.logger.info("Processed: %i", self.records_processed)
        self.logger.info("Indexed: %i", self.records_succeeded)
        self.logger.info("Errors: %i", self.records_failed)

        self.logger.info("Finished processing at %s", time.ctime())

    def post_process(self):
        """
        Promote the new index to
        the configured alias.
        """
        # Switch alias to newly created index only if process successful
        if not self.success:
            self.logger.info("Finished processing at %s", time.ctime())
            return

        alias = self.alias
        try:

            if not self.update:
                self.update_alias(alias, self.index_name)
                self.logger.info(
                    "Updated {} alias to point to {}.".format(alias, self.index_name)
                )
        except Exception as e:
            self.logger.error("ERROR: Failed to update alias: {}.".format(e))
        finally:
            if self.cleanup:
                self.cleanup_indices(days=7)
            self.logger.info("Finished processing at %s", time.ctime())

    def load(self):
        paths = [
            path for path in os.listdir(self.input_data_path) if path.endswith(".json")
        ]
        for path in paths:
            with open(os.path.join(self.input_data_path, path), "r") as file:
                data = self.select_fields(json.load(file))
                yield {
                    "_index": self.index_name,
                    "_source": data,
                    "_id": data["asset_id"],
                }

    def select_fields(self, data):
        if len(self.schema_fields):
            return {k: v for k, v in data.items() if k in self.schema_fields}
        else:
            return data.items()

    def submit(self):
        try:
            if self.update:
                self.reset_playlist_content_prior_to_rebuild()

            success, errors = helpers.bulk(
                self.client,
                self.load(),
                chunk_size=10,
                max_retries=4,
                initial_backoff=1,
                request_timeout=30,
                raise_on_error=False,
            )

            if errors:
                for error in errors:
                    self.logger.error("Document failed %s", error)
                    self.records_failed += 1
                    self.records_processed += 1

            self.records_succeeded += success
            self.records_processed += success

            self.logger.info("Processed: %i documents", self.records_processed)
            self.logger.info("Indexed: %i documents", self.records_succeeded)
            self.logger.info("Errors: %i documents", self.records_failed)
        except Exception as e:
            self.logger.error("ERROR: %s", e)

    def create_index(self, index_name):
        """
        Adds a new index to Elasticsearch.
        :param index_name: The new index name
        """
        self.client.indices.create(index=index_name)
        self.logger.info("Created index %s", index_name)

    def establish_index_name(self, alias):
        try:
            alias_state = self.client.indices.get_alias(index=alias)
            current_indices = list(alias_state.keys())
            if len(current_indices) > 1:
                raise EstablishIndexNameException(
                    'Cannot use "since" because multiple indexes are in use for this alias.'
                )
            else:
                return current_indices[0]
        except EstablishIndexNameException as e:
            print(str(e))
            exit(1)

    def prepare_alias(self, alias):
        """
        Adds a new alias to Elasticsearch if required.
        :param alias: The new alias name
        """
        # Add the defined alias if it doesn't already exist on the cluster.
        try:
            if not self.client.indices.exists_alias(name=alias):
                self.logger.info("Alias not found, adding new alias %s", alias)
                self.client.indices.put_alias(
                    name=alias, index="{}*".format(self.index_prefix)
                )
        except Exception as e:
            self.logger.error("ERROR - Could not create alias %s", e)

    def update_alias(self, alias, new_index_name):
        """
        Removes indexes from an alias and adds new indexes.
        :param alias: The alias to be updated
        :param new_index_name: The name of the index to be added to the alias
        """
        # Switch old index with the newly generated index.
        # Get indices attached to the alias
        alias_state = self.client.indices.get_alias(index=alias)
        current_indices = list(alias_state.keys())

        # Update alias swapping out old index for new
        self.client.indices.update_aliases(
            body={
                "actions": [
                    {"remove": {"indices": current_indices, "alias": alias}},
                    {"add": {"index": new_index_name, "alias": alias}},
                ]
            }
        )

        self.logger.info("Removed %s from alias %s", ", ".join(current_indices), alias)
        self.logger.info("Added %s to alias %s", new_index_name, alias)

    def reset_playlist_content_prior_to_rebuild(self):
        """
        This should really be implemented as a
        generic hooks pre and post hooks system.
        If doing a partial update this will ensure playlist
        content that has been removed is removed from the index.
        The partial harvest will always get the latest playlist
        data and rebuild it.
        """
        body = {
            "query": {
                "nested": {
                    "path": "playlists",
                    "query": {"term": {"playlists.id": {"value": 16}}},
                }
            },
            "script": "ctx._source.playlists = []",
        }
        self.client.update_by_query(index=self.alias, body=body)

    def cleanup_indices(self, days):
        """
        Delete indices if they fit the criteria and are older than a certain number of days.

        Age of the index is parsed using its name, rather than its creation date.
        If a date cannot be parsed the index will be ignored.

        Args:
            days (int): if an index is older than 'now' minus days, it will be deleted.
        """
        EXPIRY_DATE = datetime.now() - timedelta(days=days)

        self.logger.info(
            "Checking for, and deleting indices with no alias, that are older than {} day(s), with the prefix '{}'".format(
                days, self.index_prefix
            )
        )
        # get all indices
        all_indices = dict(self.client.indices.get(index="*"))

        to_delete = []
        for index_name, index_properties in all_indices.items():
            # ignore any index that has an alias
            if index_properties["aliases"].keys():
                self.logger.info("Skipping {}; it has an alias.".format(index_name))
                continue
            # if here, the index has no alias, try parsing its date
            try:
                date = index_name.replace(self.index_prefix, "")
                date = datetime.fromtimestamp(int(date))
            except ValueError:
                self.logger.info(
                    "Skipping {}; can't parse {} as a date.".format(index_name, date)
                )
                continue

            if date <= EXPIRY_DATE:
                to_delete.append(index_name)

        if to_delete:
            self.client.indices.delete(index=to_delete)
            self.logger.info("Deleted old indices: {}".format(", ".join(to_delete)))
        else:
            self.logger.info("No old indices to delete")


class EstablishIndexNameException(Exception):
    """Exception raised when a suitable index cannot be found"""

    pass
