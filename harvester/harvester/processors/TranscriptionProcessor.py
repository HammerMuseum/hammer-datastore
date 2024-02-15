import json
from pathlib import Path
import requests
from time import sleep
from requests import HTTPError
from requests.adapters import HTTPAdapter
from urllib3.util.retry import Retry


class TranscriptionProcessor:
    """
    A basic processor to export a transcription from an external service.

    The core process() method accepts an identifier that can be used to fetch
    a remote resource.

    :param harvester: The harvester that this processor is attached to.
    :type harvester: HarvesterBase
    :param api_key: Trint API key.
    :type api_key: str
    :param fields: The fields holding the Trint ID for a transcript.
    :type fields: list[str]
    :param local_dir:
        A local directory that holds transcripts (VTT, JSON, TXT). If defined, `local_dir_key` must also be defined.
    :type local_dir: str, optional
    :param local_dir_key:
        The row's field holding a key that matches the name of the file (i.e. not including the extension).
        If defined, `local_dir` must also be defined.
    :type local_dir_key: str, optional

    Returns the content of the file.
    """

    def __init__(self, harvester, api_key, fields, local_dir=None, local_dir_key=None):
        self.harvester = harvester
        self.fields = fields

        if local_dir or local_dir_key:
            # if we have a local_dir, we must have a local_dir_key to access files.
            for name, var in [
                ("local_dir", local_dir),
                ("local_dir_key", local_dir_key),
            ]:
                if not var:
                    raise NameError(f"'{name}' must be defined")

            # check if local_dir is a valid dir
            local_dir = Path(local_dir).resolve()
            if not local_dir.is_dir():
                raise FileNotFoundError(f"No folder exists at {local_dir}")

            self.local_dir = local_dir
            self.local_dir_key = local_dir_key

        strategy = Retry(
            total=5,
            status_forcelist=[403, 429, 500, 502, 503, 504],
            allowed_methods=["GET"],
            backoff_factor=4,
        )
        adapter = HTTPAdapter(max_retries=strategy)
        http = requests.Session()
        http.mount("https://", adapter)
        http.mount("http://", adapter)
        http.headers.update({"api-key": api_key})
        self.session = http

    def get_transcript_vtt(self, location):
        """
        Fetches a VTT transcription.
        """
        url = "https://api.trint.com/export/webvtt/{}".format(location)
        querystring = {
            "captions-by-paragraph": "not",
            "max-subtitle-character-length": "37",
            "highlights-only": "false",
            "enable-speakers": "false",
            "speaker-on-new-line": "false",
            "speaker-uppercase": "false",
            "skip-strikethroughs": "false",
        }

        try:
            response = self.session.get(url, params=querystring)
            response.raise_for_status()
            response_json = response.json()
            url = response_json["url"]
            response = self.session.get(url)
            response.raise_for_status()
            return response.text
        except HTTPError as error:
            self.harvester.logger.warning(
                "{} code when fetching VTT transcription from {}".format(
                    error.response.status_code, url
                )
            )
            self.harvester.logger.debug(error)

    def get_transcript_json(self, location):
        """
        Fetches a JSON transcription.
        """
        url = "https://api.trint.com/export/json/{}".format(location)
        try:
            response = self.session.get(url)
            response.raise_for_status()
            return json.dumps(response.json())
        except HTTPError as error:
            self.harvester.logger.warning(
                "{} code when fetching JSON transcription from {}".format(
                    error.response.status_code, url
                )
            )
            self.harvester.logger.debug(error)

    def get_transcript_text(self, location):
        """
        Fetches a plain text transcription.
        """
        url = "https://api.trint.com/export/text/{}".format(location)
        try:
            response = self.session.get(url)
            response.raise_for_status()
            response_json = response.json()
            url = response_json["url"]
            response = self.session.get(url)
            response.raise_for_status()
            return response.text
        except HTTPError as error:
            self.harvester.logger.warning(
                "{} code when fetching JSON transcription from {}".format(
                    error.response.status_code, url
                )
            )
            self.harvester.logger.debug(error)

    def process(self, row):
        """
        Processes each row of the input.
        """
        for field in self.fields:
            value = row[field]
            if not value:
                # No Trint ID defined, so check for files that have been manually added to the server
                if self.local_dir:
                    for suffix in ["vtt", "json", "txt"]:
                        custom_transcript_file = f"{row[self.local_dir_key]}.{suffix}"
                        custom_transcript = self.local_dir.joinpath(
                            custom_transcript_file
                        )
                        if custom_transcript.exists():
                            with open(custom_transcript) as fh:
                                row[f"{field}_{suffix}"] = fh.read()
                continue
            # Get transcripts from Trint
            self.harvester.logger.debug("Processing a {!s}".format(field))
            row["{}_vtt".format(field)] = self.get_transcript_vtt(value)
            sleep(0.4)
            row["{}_json".format(field)] = self.get_transcript_json(value)
            sleep(0.4)
            row["{}_txt".format(field)] = self.get_transcript_text(value)
            sleep(0.4)
