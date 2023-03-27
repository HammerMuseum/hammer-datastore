# Hammer Harvester

## Setup

The harvester is not currently setup to run within Docker 😕. It is recommended to use `pyenv` on your local machine to contain the python environemnt for the harvester.

ّIf you just need some data, then use the `elasticdump` method outlined in the [setup guide](../docs/getting-started.md) directory.

```sh
cd scripts
python setup.py install
cd ../harvester/harvester/assetbank
cp .env.example .env
```

Add API [credentials for Asset Bank](http://tpm.office.cogapp.com/index.php/pwd/view/769) to `.env`.

-   Note: leave `SLACK_WEBHOOK` empty, unless you want to spam the `#hammer-notifications` Slack channel, otherwise use [this URL](https://tpm.office.cogapp.com/index.php/pwd/view/1059).

## Running a Harvest

```sh
cd scripts
PYTHONPATH=../. python run_harvester.py \
  --submit \
  --host=https://hammer.assetbank-server.com/assetbank-hammer \
  --port=9201 \
  --scheme=http \
  --alias=videos \
  --asset-type=1 \
  --search-domain=http://localhost:9201 \
  --storage=/path/to/repo/shared/storage/app \
  --limit=20
```
