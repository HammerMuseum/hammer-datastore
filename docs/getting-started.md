# Setting up a development environment

## Requirements

- PHP 7.4
- Docker

## Setup

Install Docker Engine for your operating system.

Create a local .env file by copying the example.

```sh
cp .env.example .env
```

Ensure that the correct php image is selected in the .env file for your host operating system.

```sh
make up
docker-compose exec php composer install
docker-compose exec php php artisan key:generate
```

Create and upload the mapping template to Elasticsearch. The index template is stored in `elasticsearch/templates`

```sh
cd elasticsearch/scripts
./upload-index-template.sh --host http://localhost:9201 --name template_video --file ../templates/video_.json
```

### Add data to local search index (requires VPN)

The following command copies data from the dev videos index into your local Docker Elasticsearch environment.

```sh
npx elasticdump \
  --input=https://search-hammermuseum-search-sq42amyo3koerexnacxk3gws5i.us-west-1.es.amazonaws.com/videos_dev \
  --output=http://localhost:9201/videos
```

### Transcript data

There is a (mostly) complete copy of the transcript data on Alessi.

Download it into your local `storage` folder.

### Notes

```sh
# When running php-based tools and Docker, prefix commands with:
docker-compose exec php <command>

# e.g.
docker-compose exec php composer install

# and
docker-compose exec php php artisan cache:clear
```

Note the double `php` in the second command above. The first `php` refers to the name of the Docker service, the second refers to the command to invoke `php` on the command line.

### Optional steps

[Setup a local harvester](../harvester/README.md).
