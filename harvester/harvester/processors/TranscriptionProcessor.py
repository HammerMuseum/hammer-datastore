import json
import requests
from requests import HTTPError


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
        session = requests.Session()
        session.headers.update({'api-key': api_key})
        self.session = session

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
                'Error {} while fetching VTT transcription at location {}'.format(error.response.status_code, url))

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
                'Error {} while fetching JSON transcription at location {}'.format(error.response.status_code, url))

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
                'Error {} while fetching plain text transcription at location {}'.format(error.response.status_code, url))

    def process(self, row):
        """
        Processes each row of the input.
        """
        for field in self.fields:
            value = row[field]
            if not row[field]:
                continue
            try:
                self.harvester.logger.debug('Processing a {!s}'.format(field))
                row["{}_vtt".format(field)] = self.get_transcript_vtt(value)
                row["{}_json".format(field)] = self.get_transcript_json(value)
                row["{}_txt".format(field)] = self.get_transcript_text(value)
            except HTTPError as error:
                self.harvester.logger.warning(
                    'Error {} file not found'.format(error.response.status_code))
