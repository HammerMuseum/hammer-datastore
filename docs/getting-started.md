# Setting up a development environment

## Requirements

- Docker
- DDEV
- NodeJS 14 (use [nvm](https://github.com/nvm-sh/nvm/blob/master/README.md#intro))

## Setup

Install Docker and DDEV on your host machine.

### Create the DDEV environment

Create a local copy of the `.env` file by copying the example file.

```sh
cp .env.ddev.example .env
ddev start
```

### Populate a search index

You can shortcut to a full index with the following command, which
copies data from the an elasticsearch index into your DDEV Elasticsearch.

You will need to know the URL of the elasticsearch index you want to copy from.
See the Wiki for further information.

```sh
npx elasticdump \
  --input=https://<elasticsearch-url>/<index-name> \
  --output=http://hammer-datastore.ddev.site:9200/<index-name>
```

### Test

Available endpoints:

- /api/videos
- /api/videos/<id> e.g. <https://hammer-datastore.ddev.site/api/videos/1730>
- /api/search

### Notes

```sh
# To run artisan commands
ddev exec artisan <command>

# e.g.
ddev exec artisan cache:clear
```

### Optional steps

#### Harvester

[Setup a local harvester](../harvester/README.md).

#### Transcript data

A harvest will generate the transcript output data.
