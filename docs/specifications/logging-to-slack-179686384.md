<!-- Generate a new file using -->
<!-- sed -e "s/\Harvester summary to Slack/My story/" -e "s/\179686384/156128780/" -e "s/\logging-to-slack-179686384/`git_current_branch`/g" spec-template.md | tee "`git_current_branch`.md" -->

# Harvester summary to Slack

## Pivotal story

[Harvester summary to Slack](https://www.pivotaltracker.com/story/show/179686384)

## Git branch

[logging-to-slack-179686384](https://github.com/HammerMuseum/hammer-datastore/logging-to-slack-179686384)

## Story description

Log each harvest's summary.log to Slack. Highlight if it wasn't successful.

## Implementation

-   Add `SLACK_WEBHOOK` key to the Harvester's `.env.example`
-   On `AssetBankHarvester`, add a method `post_summary_to_url(url)`.
    -   In this method, parse the summary fields to give a top-level summary, and also dump the entire summary set and the foot of the message.
    -   Use `requests` to POST the data to the URL from the function arg
    -   Log an error if the response of that POST is not ok.
-   In the `postprocess` method of `AssetBankHarvester`, after the `write_summary` method call, check if the `SLACK_WEBHOOK` env variable exists, and if so, call `post_summary_to_url(url=SLACK_WEBHOOK)`

## Documentation required

-   Create a Slack webhook to post to `#hammer-notifications` & add to TPM & the README of this repo.
