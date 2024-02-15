# Hammer Asset Bank Harvester

## Setup

The harvester is not currently setup to run within Docker 😕.
It is recommended to use `pyenv` on your local machine to
contain the python environment for the harvester.

ّIf you just need some data, then use the `elasticdump`
method outlined in the [setup guide](../docs/getting-started.md) directory.

```sh
poetry install
cp .env.example .env
```

You will need API credentials for Asset Bank and a value for `SLACK_WEBHOOK`.
See the Wiki for further information.

## Running a Harvest

From the harvester directory run the following commands.

### Against a local Elasticsearch

--submit: Omit to harvest without submitting to the search index
--host: The URL of the Asset Bank instance
--alias: The alias to add the index to
--asset-type: The Asset Bank asset type (1 is video)
--storage: The path to the storage directory for text transcipt data
--limit: The number of records to harvest

```sh
poetry run python scripts/run_harvester.py \
  --submit \
  --host=https://hammer.assetbank-server.com/assetbank-hammer \
  --alias=videos \
  --asset-type=1 \
  --search-domain=http://hammer-datastore.ddev.site:9200 \
  --storage=`pwd`/../storage/app \
  --limit=20
```

### Against a remote Elasticsearch

> [!Note]
> This requires ES_CLOUD_ID and ES_API_KEY to be defined in `.env`

```sh
poetry run python scripts/run_harvester.py \
  --submit \
  --host=https://hammer.assetbank-server.com/assetbank-hammer \
  --alias=videos \
  --asset-type=1 \
  --storage=/path/to/webroot/shared/storage/app \
  --limit=20
```

For full options: `python run_harvester.py -h`
