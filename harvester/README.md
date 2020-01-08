# Hammer Harvester

## Running a Harvest

At present while the DAM is finalised, you can run a test harvest via:

```
cd scripts
python setup.py install
PYTHONPATH=../. python run_harvester.py \
  https://trial10-8.assetbank-server.com/assetbank-trial10/rest/asset-search \
  1 \
```

To harvest and additionally index the harvested content to Elasticsearch:

```
PYTHONPATH=../. python run_harvester.py \
  https://trial10-8.assetbank-server.com/assetbank-trial10/rest/asset-search \
  1 \
  --submit \
  --search-domain=<elasticsearch_domain>
```