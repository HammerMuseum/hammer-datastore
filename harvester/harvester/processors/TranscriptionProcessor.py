import json
import requests
from time import sleep
from requests import HTTPError
from requests.adapters import HTTPAdapter
from urllib3.util.retry import Retry

class TranscriptionProcessor():
    """
    A basic processor to export a transcription from an external service.

    The core process() method accepts an identifier that can be used to fetch
    a remote resource.

    Returns the content of the file.
    """

    def __init__(self, harvester, api_key, fields):
        self.harvester = harvester
        self.fields = fields
        strategy = Retry(
            total=3,
            status_forcelist=[403, 429, 500, 502, 503, 504],
            method_whitelist=["GET"],
            backoff_factor=2,
        )
        adapter = HTTPAdapter(max_retries=strategy)
        http = requests.Session()
        http.mount("https://", adapter)
        http.mount("http://", adapter)
        http.headers.update({'api-key': api_key})
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
            response = self.session.get(
                url, params=querystring)
            response.raise_for_status()
            response_json = response.json()
            url = response_json['url']
            response = self.session.get(url)
            response.raise_for_status()
            return response.text
        except HTTPError as error:
            self.harvester.logger.warning(
                '{} code when fetching VTT transcription from {}'.format(
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
                '{} code when fetching JSON transcription from {}'.format(
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
            url = response_json['url']
            response = self.session.get(url)
            response.raise_for_status()
            return response.text
        except HTTPError as error:
            self.harvester.logger.warning(
                '{} code when fetching JSON transcription from {}'.format(
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
            if not row[field]:
                continue
            self.harvester.logger.debug('Processing a {!s}'.format(field))
            row["{}_vtt".format(field)] = self.get_transcript_vtt(value)
            sleep(0.2)
            row["{}_json".format(field)] = self.get_transcript_json(value)
            sleep(0.2)
            row["{}_txt".format(field)] = self.get_transcript_text(value)
            sleep(0.2)
