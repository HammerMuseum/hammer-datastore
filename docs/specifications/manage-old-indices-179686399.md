<!-- Generate a new file using -->
<!-- sed -e "s/\Manage old indices/My story/" -e "s/\179686399/156128780/" -e "s/\manage-old-indices-179686399/`git_current_branch`/g" spec-template.md | tee "`git_current_branch`.md" -->

# Manage old indices

## Pivotal story

[Manage old indices](https://www.pivotaltracker.com/story/show/179686399)

## Git branch

[manage-old-indices-179686399](https://github.com/HammerMuseum/hammer-datastore/manage-old-indices-179686399)

## Story description

To avoid the ES cluster getting full (with unneeded data), after a harvest, check for indices that don't have an alias, and are older than 7 days. If they fall within this category, delete them.

## Implementation

Extend `ElasticsearchAdaptor.py` to have an additional method: `cleanup_indices` that take an arg `days`:

-   get all indices
-   filter out those that have an alias (i.e. the live index)
-   for the remaining indices, remove the known prefix (`self.index_prefix`), which should leave a timestamp string
-   attempt to parse the timestamp string as a date
    -   catch any errors and continue (i.e. ignore indices that we can't parse. These could be testing indices.)
-   at this point we have a list of indices that don't have an alias, and have parseable dates
-   filter this list to keep only those that are older than 'now' minus `days` (these are the indices to be deleted)
-   with this list, pass to `self.client.indices.delete(index=indices_to_delete)` to actually delete them
-   add logging to clearly show what indices are being ignore or deleted.

Add this method to the `finally` block of the `post_process` method, so that cleanup occurs even if the alias setting fails.

## Documentation required
