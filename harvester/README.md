# Hammer Harvester

## Running a Harvest

At present while the DAM is finalised, you can run a test harvest via:

```
cd scripts
python setup.py install
PYTHONPATH=../. python run_harvester.py \
  --url=<api endpoint being harvested from> \
  --type=<asset type> \
  --es_domain=<elasticsearch domain> 
  --datastore_url=<datastore endpoint>
  --api_token=<api token for the datastore endpoint> \
```