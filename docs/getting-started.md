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

Create and upload the mapping template to Elasticsearch. Index template stored in `elasticsearch/templates`

```sh
cd elasticsearch/scripts
./upload-index-template.sh --host http://localhost:9201 --name template_video --file ../templates/video_.json
```

Add some data (requires VPN) 

```sh
npx elasticdump \
  --input=https://search-hammermuseum-7ugp6zl6uxoivh2ihpx56t7wxu.us-west-1.es.amazonaws.com/videos_dev \
  --output=http://localhost:9201/videos
```

Optional: [Setup a local harvester](../harvester/README.md).

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
