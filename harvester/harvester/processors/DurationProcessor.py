import time
from pymediainfo import MediaInfo
from filecache import filecache


class DurationProcessor:
    """
    A processor to determine the duration of a video.

    Returns as a string.

    Uses mediainfo
    """

    def __init__(self, harvester, fields=[]):
        self.harvester = harvester
        self.fields = fields

    def process(self, row):
        for field in self.fields:
            try:
                self.harvester.logger.debug("Processing a video duration")
                new_field = "duration"
                video_url = str(row[field])

                tic = time.perf_counter()
                duration = get_duration(video_url)
                toc = time.perf_counter()
                self.harvester.logger.debug(
                    f"Duration determined in {toc - tic:0.4f} seconds"
                )
                formatted_duration = format_duration(duration)

                row[new_field] = formatted_duration
            except Exception as e:
                self.harvester.logger.error(e)


@filecache
def get_duration(path):
    media_info = MediaInfo.parse(path)
    for track in media_info.tracks:
        if track.track_type == "Video":
            duration = track.other_duration[3]
            return duration


def format_duration(duration):
    return duration.split(".")[0]
