# Setting up a development environment

### Requirements

- PHP 7.4
- [Composer](https://getcomposer.org/)
- Docker is recommended for development

### Setup

Install Docker Engine for your operating system.

Copy the example env file to create a local env file.

```sh
ln -s .env.example.docker .env
```

Ensure that the correct php image is selected in the .env file for your host operating system.

```sh
make up
```

Create and upload the Elasticsearch mapping template. Index templates are stored in `elasticsearch/templates`

```sh
cd elasticsearch/scripts
./upload-index-template.sh --host http://localhost:9201 --name template_video --file ../templates/video_.json
```

To run a harvest of the asset data and submit documents to Elasticsearch.

```sh
cd harvester/scripts
PYTHONPATH=../. python run_harvester.py https://hammer.assetbank-server.com/assetbank-hammer/rest/asset-search 1 --submit --search-domain=http://localhost --port=9201 --scheme=http
```

Further Harvester setup docs are in the readme.md within the harvester directory.

Notes:

```sh
# When running php-based tools and Docker, prefix commands with:
docker-compose exec php <command>

# e.g.
docker-compose exec php composer install

# and
docker-compose exec php php artisan cache:clear
```

Note the double `php` in the second command above. The first `php` refers to the name of the Docker service, the second refers to the command to invoke `php` on the command line.
