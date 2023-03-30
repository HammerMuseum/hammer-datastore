<!-- Generate a new file using -->
<!-- sed -e "s/\Harvester summary to Slack/My story/" -e "s/\179686384/156128780/" -e "s/\logging-to-slack-179686384/`git_current_branch`/g" template.md | tee "`git_current_branch`.md" -->

# Harvester summary to Slack

## Related documentation

## Pivotal Story

-   [Harvester summary to Slack](https://www.pivotaltracker.com/story/show/179686384)

## Git branch

-   [logging-to-slack-179686384](https://github.com/HammerMuseum/hammer-datastore/tree/logging-to-slack-179686384)

## Description

## Requirements to test

-   Pyenv or some Python version manager / virtualenv thingy
-   `ddev start` to bring up your local ES server
-   Add `SLACK_WEBHOOK=` (i.e. an empty key) in `harvester/harvester/harvester/assetbank/.env`
-   Create a request bin at https://pipedream.com/requestbin (this will allow us to test the POST of data that would be sent to Slack)

## Test Plan

-   Make a dir for the Harvest output, e.g. `/harvester/hammer-datastore`

Notes:

If you hit some TLS error running the harvester, you may need to temporarily set `verify_certs=False` when creating the Elasticsearch client in `harvester/harvester/components/adaptors/ElasticsearchAdaptor.py`:

-   i.e. `self.client = Elasticsearch(es_domain, verify_certs=False)`

After the first harvest run, you need to manually set `update=True` on `harvester/scripts/run_harvester.py` line 159 because subsequent runs will need to update the `videos` alias.

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

    -   Check the data folder (`harvester/data`) for the files from that harvest.
        -   Does `summary.log` exist there?

-   Copy the endpoint from your created Pipedream workflow, and paste as the value to `SLACK_WEBHOOK` in your .env

    -   Re-run the harvest
    -   Does `summary.log` exist locally?
    -   Check Pipedream, do you see an POST logged?
        -   Within that log, copy the value from the `body` key (this is what was POSTed)
        -   Paste that into the [Slack block builder](https://app.slack.com/block-kit-builder/T045774MG)
        -   Does that successfully show a message? Does it dump the contents of `summary.log` there too?

-   To mock the message to be POSTed when a harvest fails, edit the `post_summary_to_slack` method on `AssetBankHarvester` to set `success = False`.
    -   Re-run the harvest
    -   Check Pipedream, do you see an POST logged?
        -   Within that log, copy the value from the `body` key (this is what was POSTed)
        -   Paste that into the [Slack block builder](https://app.slack.com/block-kit-builder/T045774MG)
        -   Does that successfully show a message? Does it dump the contents of `summary.log` there too?
        -   Does it also `@channel` which would notify people of an issue?
