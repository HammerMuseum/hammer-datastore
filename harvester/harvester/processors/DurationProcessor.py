import subprocess
import shlex
import json
import datetime
import time
from filecache import filecache

class DurationProcessor():
    """
    A processor to determine the duration of a video.

    Returns as a string.

    Uses ffprobe
    """

    def __init__(self, harvester, fields=[]):
        self.harvester = harvester
        self.fields = fields

    def process(self, row):
        for field in self.fields:
            try:
                self.harvester.logger.debug('Processing a video duration')
                new_field = 'duration'
                video_url = row[field]

                tic = time.perf_counter()
                duration = get_duration(video_url)
                toc = time.perf_counter()
                self.harvester.logger.debug(f"Duration determined in {toc - tic:0.4f} seconds")
                formatted_duration = format_duration(duration)

                row[new_field] = formatted_duration
            except Exception as e:
                self.harvester.logger.error(e)

# In order to cache the output of this function, it cannot be on the class.
# The filecache module won't work as a new instance is created each for each harvest.
def get_duration(file_path):
    cmd = str("ffprobe -v quiet -print_format json -show_streams")
    args = shlex.split(cmd)
    args.append(str(file_path))
    output = run_ff_probe(args)
    output_json = json.loads(output.decode('utf-8'))
    return float(output_json['streams'][0]['duration'])

@filecache
def run_ff_probe(args):
    # run the ffprobe process, decode stdout into utf-8 & convert to JSON
    return subprocess.check_output(args)

def format_duration(duration):
    duration = str(datetime.timedelta(seconds=duration))
    return '{}'.format(duration.split(".")[0])
