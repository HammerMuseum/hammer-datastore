<!--Generateanewfileusing-->
<!--sed -e s/\Friendly urls/My story/ -e s/\170926135/156128780/ -e s/\friendly-urls-170926135/`git_current_branch`/g template.md tee `git_current_branch`.md -->

# Friendly urls

## Related documentation

## Pivotal Story

* [Friendly urls](https://www.pivotaltracker.com/story/show/170926135)

## Git branch

* [friendly-urls-170926135](https://github.com/HammerMuseum/hammer-datastore/tree/friendly-urls-170926135)

## Description

## Requirements to test

## Test Plan

### Check slug generation

- Log in to Asset Bank.
- Create a new Video in Asset Bank.
- Populate the title field using the value: "it's a \"'""|----test video title with dodgy {}\^[]`;/?:@&=+$,"
- Run a harvest against a local copy of Elasticsearch and ensure there are no reported errors or failures.
- Query using Kibana and they query:
```GET videos/_search
{
  "size": 50, 
  "_source": ["title_slug"],
  "query": {
    "match_all": {}
  }
}
```

### Add a duplicate item

- Find the title of an existing video on the site and copy it.
- Log in to Asset Bank.
- Create a new Video in Asset Bank.
- Populate the title field using the value copied from step 2.
- Run a harvest ensure the harvest fails the item and reports an error detailing the asset ID of the failed item.
