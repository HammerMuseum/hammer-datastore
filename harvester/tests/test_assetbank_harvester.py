import pytest
import logging
from unittest.mock import patch, MagicMock
from harvester.harvester.assetbank import AssetBankHarvester

class TestAssetBankHarvester(AssetBankHarvester):
    def __init__(self):
        self.init_auth = None
        self.logger = logging.getLogger('mock-harvester')

def test_validate_record_detects_valid_records(example_valid_records):
    for record in example_valid_records:
        harvester = TestAssetBankHarvester()
        assert AssetBankHarvester.validate_record(harvester, record)

harvester = TestAssetBankHarvester()
@patch.object(harvester.logger, "error", MagicMock())
def test_validate_record_detects_invalid_records(example_invalid_records):
    for record in example_invalid_records:
        assert AssetBankHarvester.validate_record(harvester, record) is False
        harvester.logger.error.assert_called()

@pytest.fixture
def example_valid_records():
    return [
        {
            "asset_id": "1",
            "title": "Example Valid Record 1 - all fields and values present",
            "description": "<p>Description text</p>",
            "date_recorded": "2012-05-16T00:00:00",
            "duration": "1:38:35",
            "tags": ["example-tag","example-tag-2"],
            "transcription": "exampleid",
            "transcription": "text",
            "transcription_txt": "text",
            "transcription_vtt": "text",
            "transcription_json": "{\"words\": []}",
            "speakers":["example speaker"],
            "topics":["Environment"],
            "quote": "",
            "video_url": "https://example.com/video/content/url",
            "thumbnail_url": "https://example.com/thumbnail/content/url",
            "thumbnailId": "22137aa7f966085d",
            "in_playlists":[],
            "playlists":[],
            "title_slug": "example-valid-record-1"
        },
        {
            "asset_id": "2",
            "title": "Example Valid Record 2 - all fields present and required fields have values",
            "description": "<p>Description text</p>",
            "date_recorded": "2012-05-16T00:00:00",
            "duration": "1:38:35",
            "tags": [],
            "transcription": "",
            "transcription": "",
            "transcription_txt": "",
            "transcription_vtt": "",
            "transcription_json": "",
            "speakers":[],
            "topics":[],
            "quote": "",
            "video_url": "https://example.com/video/content/url",
            "thumbnail_url": "https://example.com/thumbnail/content/url",
            "thumbnailId": "22137aa7f966085d",
            "in_playlists":[],
            "playlists":[],
            "title_slug": "example-valid-record-2"
        },
    ]

@pytest.fixture
def example_invalid_records():
    return [
        {
            "asset_id": "3",
            "title": "Example Invalid Record 2 - missing field",
            "description": "<p>Description text</p>",
            "date_recorded": "2012-05-16T00:00:00",
            "tags": ["example-tag","example-tag-2"],
            "transcription": "exampleid",
            "transcription": "text",
            "transcription_txt": "text",
            "transcription_vtt": "text",
            "transcription_json": "{\"words\": []}",
            "speakers":["example speaker"],
            "topics":["Environment"],
            "quote": "",
            "video_url": "https://example.com/video/content/url",
            "thumbnail_url": "https://example.com/thumbnail/content/url",
            "thumbnailId": "22137aa7f966085d",
            "in_playlists":[],
            "playlists":[],
            "title_slug": "example-invalid-record-1"
        },
        {
            "asset_id": "4",
            "title": "Example Invalid Record 2 - missing value in required field",
            "description": "",
            "date_recorded": "2012-05-16T00:00:00",
            "duration": "1:38:35",
            "tags": [],
            "transcription": "",
            "transcription": "",
            "transcription_txt": "",
            "transcription_vtt": "",
            "transcription_json": "",
            "speakers":[],
            "topics":[],
            "quote": "",
            "video_url": "https://example.com/video/content/url",
            "thumbnail_url": "https://example.com/thumbnail/content/url",
            "thumbnailId": "22137aa7f966085d",
            "in_playlists":[],
            "playlists":[],
            "title_slug": "example-invalid-record-2"
        },
    ]
