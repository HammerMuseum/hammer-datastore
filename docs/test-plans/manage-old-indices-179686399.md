<!-- Generate a new file using -->
<!-- sed -e "s/\Manage old indices/My story/" -e "s/\179686399/156128780/" -e "s/\manage-old-indices-179686399/`git_current_branch`/g" template.md | tee "`git_current_branch`.md" -->

# Manage old indices

## Related documentation

## Pivotal Story

-   [Manage old indices](https://www.pivotaltracker.com/story/show/179686399)

## Git branch

-   [manage-old-indices-179686399](https://github.com/HammerMuseum/hammer-datastore/tree/manage-old-indices-179686399)

## Description

## Requirements to test

-   Pyenv or some Python version manager / virtualenv thingy
-   `ddev start` to bring up your local ES server

## Test Plan

-   Make a dir for the Harvest output, e.g. `/harvester/hammer-datastore`

Notes:

If you hit some TLS error running the harvester, you may need to temporarily set `verify_certs=False` when creating the Elasticsearch client in `harvester/harvester/components/adaptors/ElasticsearchAdaptor.py`: - i.e. `self.client = Elasticsearch(es_domain, verify_certs=False)`

After the first harvest run, you need to manually set `update=True` on `harvester/scripts/run_harvester.py` line 159 because subsequent runs will need to update the `videos` alias.

### Create some dummy indices which should be deleted

-   Generate three timestamps older than 7 days ago, e.g.`1647261365` ([this tool might help](https://www.epochconverter.com/))
-   Generate three timestamps younger that 7 days ago (i.e. ones that shouldn't be deleted by this process)
-   Create a dummy index for each, i.e. `curl -X PUT "https://hammer-datastore.ddev.site:9201/video_1647261365"`
-   List all indices with `curl https://hammer-datastore.ddev.site:9201/_cat/indices/*?v=true&pretty` to validate the state pre-deletion

### Test Harvester

-   `cd harvester`
-   Create / activate a Python 3.7 virtualenv, and install Harvester using `pip install -e .`
-   `cd scripts`
-   Run a small harvest, e.g.

    ```bash
        python run_harvester.py \
        --submit \
        --host=https://hammer.assetbank-server.com/assetbank-hammer \
        --port=9201 \
        --scheme=http \
        --alias=videos \
        --asset-type=1 \
        --search-domain=https://hammer-datastore.ddev.site:9201 \
        --storage=/harvester/hammer-datastore \
        --assets 1775
    ```

    -   List the indices again
        -   Have the 3 indices older than 7 days been deleted?
        -   Are the 3 indices younger than 7 days still there?
    -   Also, check `harvester/logs/adaptor.log` for logging of what happened.
        -   Is it clear which indices were ignored, and which were deleted?
