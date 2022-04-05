# Setting up a development environment

## Requirements

- Docker
- DDEV
- NodeJS 14 (use [nvm](https://github.com/nvm-sh/nvm/blob/master/README.md#intro))

## Setup

Install Docker and DDEV

Create a local .env file by copying the example.

```sh
cp .env.ddev.example .env
ddev start
```

### Populate the search index (requires VPN)

The following command copies data from the dev videos index into your local Docker Elasticsearch environment.

```sh
npx elasticdump \
  --input=https://search-hammermuseum-search-sq42amyo3koerexnacxk3gws5i.us-west-1.es.amazonaws.com/videos_dev \
  --output=http://hammer-datastore.ddev.site:9200/videos_dev
```

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

There is a copy of the transcript data on Alessi.

Download and unzip it into your local `storage` folder.
