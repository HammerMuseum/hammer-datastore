import os
import pytz
import logging
from datetime import datetime
from abc import ABCMeta, abstractmethod


class HarvesterBase(metaclass=ABCMeta):
    """
    The base Harvester class. Sets the core properties and abstract methods for
    each individual harvester, which should be subclassed from this class.
    """

    version = 0.0

    name = None

    """Total number of records processed in last Mill run."""
    records_processed = 0

    """Number of records successfully processed in last Mill run."""
    records_succeeded = 0

    """Number of records unsuccessfully processed in last Mill run."""
    records_failed = 0

    """Success of last Mill run."""
    success = False

    """Logger as set by the add_logging() method."""
    logger = None

    """Log level for logging module."""
    log_level = logging.INFO

    """Formatter for logging messages."""
    log_formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s - %(message)s')

    """The base directory to save harvest runs."""
    output_base = None

    """The path base for outputting """
    output_prefix = None

    """The directory currently being used to store harvester output."""
    current_output_path = None

    """The date format used for creating date stamps."""
    date_format = '%Y.%m.%d_%H.%M.%S_%Z'

    def __init__(self):
        # Set the base directory, which is currently two levels above the harvester.
        base_dir = os.path.dirname(os.path.abspath(__file__))

        self.base_dir = base_dir

        self.start_time = None
        self.end_time = None

    def add_logger(self, log_directory, log_file, log_name='harvester'):
        """
        Add a basic logger, with a file and stream handler.
        In the future this would be better as an extensible function to which
        we can supply handlers.
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

    def preprocess(self):
        """Preprocessing callback, available for implementation by child classes."""
        pass

    def postprocess(self):
        """Postprocessing callback, available for implementation by child classes."""
        pass

    def process(self):
        """The core processing function for a harvest."""
        self.preprocess()

        # Create the harvest output directory.
        start_time = datetime.now(pytz.utc).strftime(self.date_format)
        harvest_progress_directory = self.output_path() + '_IN_PROGRESS_' + start_time
        self.start_time = start_time

        os.mkdir(harvest_progress_directory)
        self.current_output_path = harvest_progress_directory

        self.harvest()

        end_time = datetime.now(pytz.utc).strftime(self.date_format)
        harvest_complete_directory = self.output_path() + '_COMPLETE_' + end_time
        self.end_time = end_time

        self.current_output_path = harvest_complete_directory
        os.rename(harvest_progress_directory, harvest_complete_directory)

        self.postprocess()

    @abstractmethod
    def harvest(self):
        """The core harvesting method invoked by process(). Should return True if successful, False if not."""
        pass

    def output_path(self):
        if self.output_base is None or self.output_prefix is None:
            raise AttributeError('The output_base and output_prefix attributes must be set for the harvester.')

        return os.path.join(self.output_base, self.output_prefix)

    def write_record(self, record, file_name):
        record_path = os.path.join(self.current_output_path, file_name)

        with open(record_path, 'w') as fh:
            fh.write(record)

    @abstractmethod
    def validate_record(self, record):
        """Validate an individual record. Should return True if valid, False if not."""
        return
