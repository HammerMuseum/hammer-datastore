# Setting up a development environment

Make a copy of the file `.env.example` named `.env`

Populate your database credentials in the `.env` file e.g:

        DB_CONNECTION=mysql
        DB_HOST=127.0.0.1
        DB_PORT=3306
        DB_DATABASE=datastore
        DB_USERNAME=*
        DB_PASSWORD=*

Check that all of the database credentials in `config/database.php` are pointing to the correct place.

## Using Docker

Install the correct version of Docker for your operating system. 

```sh
tee .env.example.docker .env
```
Check that the correct php image is selected in .env (choice depends on host operating system).

```sh
make up
```

```sh
# When running php-based tools and Docker, prefix commands with:
docker-compose exec php <command>

# e.g.
docker-compose exec php composer install

# and
docker-compose exec php php artisan key: generate
```

Note the double `php` in the second command above. The first `php` refers to the name of the Docker service, the second refers to the command to invoke `php` on the command line.


## Not using Docker

### Requirements

- PHP 7.2
- [Composer](https://getcomposer.org/) must be installed

## Setup

From the project root, run:

        composer install
        php artisan migrate
        
Add the project to your vhosts file or equivalent.