# Setting up a development environment

### Requirements

- MySQL
- PHP 7.2
- [Composer](https://getcomposer.org/)

### Setup

Setup you your local virtual hosts or equivalent.

From the project root, run:

```sh
ln -s .env.example .env
```

Create a database called `laravel-datastore`

Populate your database credentials in the `.env` file e.g:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel-datastore
DB_USERNAME=<your-db-username>
DB_PASSWORD=<your-db-password>
```

Then from the project root run:

```sh
composer install
php artisan key:generate
php artisan migrate
```

## Using Docker

Install the correct version of Docker for your operating system. 

```sh
ln -s .env.example.docker .env
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
docker-compose exec php php artisan key:generate
```

Note the double `php` in the second command above. The first `php` refers to the name of the Docker service, the second refers to the command to invoke `php` on the command line.
