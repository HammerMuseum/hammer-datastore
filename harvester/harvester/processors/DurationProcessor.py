import subprocess
import shlex
import json
import datetime


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
                self.harvester.logger.debug('Processing a {!s}'.format(field))
                new_field = 'duration'
                video_url = row[field]
                duration = self.get_duration(video_url)
                row[new_field] = duration
            except Exception as e:
                self.harvester.logger.error(e)
                # @todo Possibly raise an expception here?

    def get_duration(self, file_path):
        cmd = "ffprobe -v quiet -print_format json -show_streams"
        args = shlex.split(cmd)
        args.append(file_path)

        # run the ffprobe process, decode stdout into utf-8 & convert to JSON
        ff_probe_output = subprocess.check_output(args).decode('utf-8')
        ff_probe_output = json.loads(ff_probe_output)

        duration_value = float(ff_probe_output['streams'][0]['duration'])

        # change duration into a readable format
        duration = str(datetime.timedelta(seconds=duration_value))

        return '{}'.format(duration.split(".")[0])
