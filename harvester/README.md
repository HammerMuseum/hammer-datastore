# Hammer Asset Bank Harvester

## Setup

The harvester is not currently setup to run within Docker 😕.
It is recommended to use `pyenv` on your local machine to
contain the python environment for the harvester.

ّIf you just need some data, then use the `elasticdump`
method outlined in the [setup guide](../docs/getting-started.md) directory.

```sh
pip install -e .
cd ../harvester/harvester/assetbank
cp .env.example .env
```

You will need API credentials for Asset Bank and a value for `SLACK_WEBHOOK`.
See the Wiki for further information.

## Running a Harvest

```sh
cd scripts
PYTHONPATH=../. python run_harvester.py \
  --submit \
  --host=https://hammer.assetbank-server.com/assetbank-hammer \
  --alias=videos \
  --asset-type=1 \
  --search-domain=http://hammer-datastore.ddev.site:9200 \
  --storage=/path/to/repo/shared/storage/app \
  --limit=20
```

For full options: `python run_harvester.py -h`
