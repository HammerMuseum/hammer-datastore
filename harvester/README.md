# Hammer Harvester

## Setup

Copy `.env.example` to `.env` and add your credentials.

## Running a Harvest

At present while the DAM is finalised, you can run a test harvest via:

```
cd scripts
python setup.py install
PYTHONPATH=../. python run_harvester.py \
  --host=https://hammer.assetbank-server.com/assetbank-hammer10/rest/asset-search \
  --asset-type=1
```

To harvest and additionally index the harvested content to Elasticsearch:

```
PYTHONPATH=../. python run_harvester.py \
  --host=https://hammer.assetbank-server.com/assetbank-hammer10/rest/asset-search \
  --asset-type=1 \
  --submit \
  --search-domain=<elasticsearch_domain>
```
