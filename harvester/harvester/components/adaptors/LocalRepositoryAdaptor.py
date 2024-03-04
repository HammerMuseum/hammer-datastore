import os
import logging
import json
from pathlib import Path
import time


class LocalRepositoryAdaptor:
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
    """
    schema_fields = ["transcription_json", "transcription_vtt"]

    def __init__(self, source, destination):
        self.source = source
        self.destination = destination

        self.timestamp = int(time.time())

    def add_logger(self, log_directory, log_file, log_name="transcription-adaptor"):
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
        pass

    def process(self):
        """
        Manipulate and move data into local repository as needed.
        """
        self.logger.info("------------------------")
        self.logger.info("Started processing at %s", time.ctime())
        self.logger.info("------------------------")

        paths = [path for path in os.listdir(self.source) if path.endswith(".json")]
        for path in paths:
            abs_input_path = os.path.join(self.source, path)
            try:
                with open(abs_input_path, "r") as file:

                    data = self.select_fields(json.load(file))

                    if data["transcription_json"]:
                        dest = os.path.join(
                            self.destination, "transcripts/json", Path(path).stem
                        )
                        self.logger.debug("requesting creation of file at %s" % dest)
                        self.add_file_to_repository(dest, data["transcription_json"])

                    if data["transcription_vtt"]:
                        dest = os.path.join(
                            self.destination, "transcripts/vtt", Path(path).stem
                        )
                        self.logger.debug("requesting creation of file at %s" % dest)
                        self.add_file_to_repository(dest, data["transcription_vtt"])

                    self.records_processed += 1
                    self.records_succeeded += 1
            except KeyError as e:
                self.records_processed += 1
                self.logger.warning("%s not found in input data %s", e, abs_input_path)
            except Exception as e:
                self.records_processed += 1
                self.records_failed += 1
                self.logger.error("ERROR: %s", e)

        if self.records_processed > 0:
            self.success = (self.records_succeeded / self.records_processed) > 0.9
        else:
            self.success = False

        if self.success:
            self.logger.info(
                "Local repository adaptor processing completed successfully."
            )
        else:
            self.logger.info("Local repository adaptor processing failed.")

        self.logger.info("Processed: %i", self.records_processed)
        self.logger.info("Success: %i", self.records_succeeded)
        self.logger.info("Errors: %i", self.records_failed)

    def select_fields(self, data):
        return {k: v for k, v in data.items() if k in self.schema_fields}

    def post_process(self):
        self.logger.info("Finished processing at %s", time.ctime())

    def add_file_to_repository(self, path, data):
        try:
            os.makedirs(os.path.dirname(path), exist_ok=True)
            with open(path, "w", encoding="utf-8") as f:
                f.write(data)
                self.logger.debug("Created file at %s" % path)
        except Exception as e:
            self.logger.debug("Failed to create file at %s" % path)
            self.logger.error("ERROR: %s", e)
