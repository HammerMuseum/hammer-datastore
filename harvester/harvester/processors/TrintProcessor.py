import requests
from requests import HTTPError

class TrintProcessor():
    """
    A basic processor to export a transcription from Trint.
    
    The core process() method accepts a remote url pointing to a file.

    Returns the content of the file.
    """

    def __init__(self, harvester, api_key, fields):
        self.harvester = harvester
        self.fields = fields
        self.api_key = api_key


    def get_url(self, trint_id):
        url = "https://api.trint.com/export/webvtt/{}".format(trint_id)
        querystring = {
            "captions-by-paragraph":"false",
            "max-subtitle-character-length":"37",
            "highlights-only":"false",
            "enable-speakers":"false",
            "speaker-on-new-line":"false",
            "speaker-uppercase":"false",
            "skip-strikethroughs":"false"
        }        
        headers = {'api-key': self.api_key}
        try:
            response = requests.get(url, headers=headers, params=querystring)
            response.raise_for_status()
            json = response.json()
            return json['url']
        except HTTPError as e:
            self.harvester.logger.warning(
                'Error {} while fetching transcription'.format(e.response.status_code))


    def process(self, row):
        for field in self.fields:
            if not row[field]:
                continue
            try:
                self.harvester.logger.debug('Processing a {!s}'.format(field))
                url = self.get_url(row[field])
                response = requests.get(url)
                response.raise_for_status()
                row[field] = response.text.replace('\n', ' ')
            except HTTPError as e:
                self.harvester.logger.warning('Error {} file not found'.format(e.response.status_code))
