<!-- Generate a new file using -->
<!-- sed -e "s/\Convert and upload SRT files/My story/" -e "s/\156128780/156128780/" -e "s/\convert-upload-srt-184461678/`git_current_branch`/g" template.md | tee "`git_current_branch`.md" -->

# Convert and upload SRT files

## Related documentation

## Pivotal Story

-   [Convert and upload SRT files](https://www.pivotaltracker.com/story/show/156128780)

## Git branch

-   [convert-upload-srt-184461678](https://github.com/HammerMuseum/hammer-datastore/tree/convert-upload-srt-184461678)

## Description

## Requirements to test

-   Pyenv or some Python version manager / virtualenv thingy

## Test Plan

-   Make the dir `/var/manual_transcripts`.
-   Make a dir for the Harvest output, e.g. `/harvester/hammer-datastore`

Notes:

If you hit some TLS error running the harvester, you may need to temporarily set `verify_certs=False` when creating the Elasticsearch client in `harvester/harvester/components/adaptors/ElasticsearchAdaptor.py`: - i.e. `self.client = Elasticsearch(es_domain, verify_certs=False)`

After the first harvest run, you need to manually set `update=True` on `harvester/scripts/run_harvester.py` line 159 because subsequent runs will need to update the `videos` alias.

### Generate transcripts

-   `cd scripts/srt/`
-   Save the SRT from [PT](https://www.pivotaltracker.com/story/show/184461678/comments/235556848) as `1105.srt` in this dir
-   Generate the transcript with `python3 srt.py 1105.srt`
    -   This should generate 3 files under `./manual_transcripts`: `1105.json`, `1105.vtt` & `1105.txt`
    -   Do the JSON & VTT match the format outlined in the spec, or compare to [JSON](https://channel.hammer.ucla.edu/api/videos/1775/transcript?format=json) & [VTT](https://channel.hammer.ucla.edu/api/videos/1775/transcript?format=vtt)
-   Move all to the 'server': `cp -r manual_transcripts /var/manual_transcripts`

### Test Harvester

-   `cd ../..`
-   `ddev start` to bring up your local ES server
-   `cd harvester`
-   Create / activate a Python 3.7 virtualenv, and install Harvester using `pip install -e .`
-   `cd scripts`
-   For video `1775` (this has a Trint transcription)

    -   Run a harvest:

        ```bash
        python scripts/run_harvester.py \
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

    -   Get the ES doc: `curl https://hammer-datastore.ddev.site:9201/videos/_doc/1775?pretty`

        -   Does this doc exist?
        -   This doc should have a value for the `transcription` key.
        -   Does the doc have a `transcription_txt` key & value?

    -   See the output files: `ls /harvester/hammer-datastore/transcripts/*`
        -   Does the `json` dir have `1775`? Is it a JSON?
        -   Does the `vtt` dir have `1775`? Is it a VTT?

-   Repeat the same as above, but for video `--assets 1105` (which is one that doesn't have a Trint ID, but does have a local transcription)
    -   `curl https://hammer-datastore.ddev.site:9201/videos/_doc/1105?pretty`
        -   Does the ES doc exist?
        -   Does it have an empty value for `transcription`?
        -   Does the doc have a `transcription_txt` key & value?
    -   See the output files: `ls /harvester/hammer-datastore/transcripts/*`
        -   Does the `json` dir have `1105`? Is it a JSON?
        -   Does the `vtt` dir have `1105`? Is it a VTT?
-   Repeat the same as above, but for video `1731` (which is one that doesn't have a Trint ID, nor a local transcription)
    -   `curl https://hammer-datastore.ddev.site:9201/videos/_doc/1731?pretty`
        -   Does the ES doc exist?
        -   Does it have an empty value for `transcription`?
        -   Does it have an empty value for `transcription_txt`?
    -   See the output files: `ls /harvester/hammer-datastore/transcripts/*`
        -   Does the `json` dir _NOT_ have `1731`?
        -   Does the `vtt` dir _NOT_ have `1731`?
