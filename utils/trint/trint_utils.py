#!/usr/bin/env python3
"""
Utilities for working with Trint webvtt transcriptions.

Initially there is one utility, it simply extracts plaintext from webvtt transcriptions.

Could be extended to accommodate alternative export formats.
"""

__author__ = "Andy Cummins"
__version__ = "0.1.0"

import argparse
import logzero
import logging
from logzero import logger
import webvtt
import os
import errno


def main(args):
    """ Let's a go... """

    # set log level
    if args.verbose > 0:
        logzero.loglevel(logging.DEBUG)
    else:
        logzero.loglevel(logging.INFO)

    mode = ""
    # are we dealing with a file or a directory?
    if os.path.isdir(args.path):
        mode = "DIR"
        if not args.path.endswith('/'):
            args.path += '/'
    elif os.path.isfile(args.path):
        mode = "FILE"
    else:
        logger.critical("Invalid file or directory path specified.")

    # check and set output dir if necessary otherwise
    # we use the input dir to write our new files to

    # if the output_dir is specified we create dirs as required
    #
    # if it isn't and we are dealing with a directory of files
    # then we just make sure the input has a trailing slash
    #
    # if we are in file mode we'll and we have no output_dir
    # specified we'll use the input path to the file
    output_dir = ""
    if args.output_dir:
        output_dir = args.output_dir

        if not output_dir.endswith('/'):
            output_dir += '/'

        # create any dirs we need to
        if not os.path.exists(os.path.dirname(output_dir)):
            try:
                os.makedirs(os.path.dirname(output_dir))
            except OSError as exc:  # Guard against race condition
                if exc.errno != errno.EEXIST:
                    raise
    elif mode == "DIR":
        output_dir = args.path
        if not output_dir.endswith('/'):
            output_dir += '/'

    # directory so let's loop over and do 'em all
    if mode == "DIR":
        logger.debug("Directory mode, scanning: " + args.path)

        # now let's get to the files and extract the transcripts from each
        directory = os.fsencode(args.path)

        for file in os.listdir(directory):
            filename = os.fsdecode(file)

            full_path = output_dir + filename
            if filename.endswith(".vtt"):
                logging.debug("Attempting to extract from file: " + filename)
                plaintext = extract_plaintext_from_webvtt(args.path + filename)
                output_string_to_file(plaintext, full_path, 'txt')
                continue
            else:
                logger.debug("Skipping file: " + full_path)
                continue

    elif mode == "FILE":
        # single file specified so just the one extraction to do
        logger.debug("Single file mode, scanning: " + args.path)

        if args.output_dir:
            # need to split the filename from the path as output_dir has been specified
            path, filename = os.path.split(args.path)
            output_path = output_dir + filename
        else:
            # we're plopping the files from whence they came so just use the input path to the file
            output_path = args.path

        try:
            with open(args.path):
                plaintext = extract_plaintext_from_webvtt(args.path)
                output_string_to_file(plaintext, output_path, 'txt')

        except IOError:
            logger.error("Input file not accessible, please check the path: " + args.filepath)


def extract_plaintext_from_webvtt(path_to_file: str):
    """Extract plaintext from a webvtt file.

    Keyword arguments:
    path_to_file -- string -- full path to webvtt file

    Return value:
    transcript_text -- string -- plaintext of transcript
    """

    logger.debug("generate_plaintext_transcript: " + path_to_file)

    vtt = webvtt.read(path_to_file)
    transcript: str = ""

    # loop over text lines and create one block of plain text with spaces
    for line in vtt:
        transcript += line.text.strip() + " "

    # remove trailing space
    transcript_text = transcript.strip()

    logger.debug("Text extracted from: " + path_to_file)

    return transcript_text


def output_string_to_file(output_string: str, path_to_file: str, file_format: str):
    """Output a string to a file with a given format

    Keyword arguments:
    output_string -- string -- string to populate the file
    path_to_file -- string -- path to original transcript file, used for naming this new output file
    file_format -- string -- Format of the output file e.g. txt
    """

    output_path: str = os.path.splitext(path_to_file)[0] + '.' + file_format

    try:
        output_file = open(output_path, 'w')
        output_file.write(output_string)
        output_file.close()

        logger.info("Success, output file created at: " + output_path)

    except IOError:
        logger.error("Unable to write to output file: " + output_path)


if __name__ == "__main__":

    """ This is executed when run from the command line """
    parser = argparse.ArgumentParser()

    # takes file path for input
    parser.add_argument("path", action="store",
                        help="Required full path to local webvtt file or directory containing webvtt files.")

    parser.add_argument(
        "-o",
        "--output_dir",
        action="store",
        default=0,
        help="Specific output directory, if not specified output file(s) will be written to same dir as input file(s)")

    # Optional verbosity counter (eg. -v, -vv, -vvv, etc.)
    parser.add_argument(
        "-v",
        "--verbose",
        action="count",
        default=0,
        help="Verbosity")

    # Specify output of "--version"
    parser.add_argument(
        "--version",
        action="version",
        version="%(prog)s (version {version})".format(version=__version__))

    command_line_args = parser.parse_args()
    main(command_line_args)
