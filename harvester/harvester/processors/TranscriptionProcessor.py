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
        self.headers = {'api-key': api_key}

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
            response = requests.get(
                url, headers=self.headers, params=querystring)
            response.raise_for_status()
            response_json = response.json()
            url = response_json['url']
            response = requests.get(url)
            response.raise_for_status()
            return response.text
        except HTTPError as error:
            self.harvester.logger.warning(
                'Error {} while fetching VTT transcription'.format(error.response.status_code))

    def get_transcript_json(self, location):
        """
        Fetches a JSON transcription.
        """
        url = "https://api.trint.com/export/json/{}".format(location)
        try:
            response = requests.get(url, headers=self.headers)
            response.raise_for_status()
            return json.dumps(response.json())
        except HTTPError as error:
            self.harvester.logger.warning(
                'Error {} while fetching JSON transcription'.format(error.response.status_code))

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
                row[field] = self.get_transcript_vtt(value)
                row["{}_json".format(field)] = self.get_transcript_json(value)
            except HTTPError as error:
                self.harvester.logger.warning(
                    'Error {} file not found'.format(error.response.status_code))
