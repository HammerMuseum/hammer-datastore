import os
import re
from datetime import datetime


def get_harvest_dirs(input_directory, input_pattern, timestamp_format):
    """
    Retrieve harvest directories matching a pattern, and extract and parse timestamps.

    :param input_directory:
        The base directory where harvest directories are saved.
    :param input_pattern:
        A regular expression to match completed harvest directories.
        Should have a single capturing group, which captures the timestamp
        parsed according to timestamp_format.
    :param timestamp_format:
        The format of the UTC timestamp extracted from the directory name.
        Uses the formatter syntax for the strftime() function.
    :return:
        A list of dictionaries containing:
            'directory' - A fully qualified path to the harvest directory
            'created' - A datetime object representing when the harvest was created.
        The list is sorted by 'created'.
        Any items with timestamps in the future are excluded.
    """
    complete_harvest_dirs = []

    for directory in os.listdir(input_directory):
        match = re.match(input_pattern, directory)

        if match:
            timestamp = datetime.strptime(match.group(1), timestamp_format)

            # Skip items which exist in the future.
            if timestamp > datetime.now():
                continue

            complete_harvest_dirs.append(
                {
                    "directory": os.path.join(input_directory, directory),
                    "created": timestamp,
                }
            )

    # Sort the complete directories by timestamp, newest to oldest.
    complete_harvest_dirs.sort(key=lambda d: d["created"].timestamp(), reverse=True)

    return complete_harvest_dirs


def exceeds_threshold(last_run_created, threshold):
    """
    Check whether a Mill run has exceeded the run threshold.
    This explicitly ignores timezones since the datetime should be UTC.
    Granularity smaller than a second is ignored.

    :param last_run_created:
        A datetime object corresponding to the last run.
    :param threshold:
        A threshold to check against in hours.
    :return:
        True if last_run_created is older than the threshold, and False otherwise.
        Returns False if the offset is exactly equal to the threshold.
    """
    last_run_age = datetime.now() - last_run_created
    last_run_hours = (last_run_age.days * 24) + (last_run_age.seconds / 3600)
    return last_run_hours > int(threshold)


def dict_to_uri(uri_dict):
    """
    Convert a dict to a / separated URI.

    :param uri_dict:
        A dictionary of values to join.
    :return:
        The joined dictionary as a / separated string. Contains both the index and value of each element.
    """
    return "/".join([i + "/" + v for i, v in uri_dict.items()])
