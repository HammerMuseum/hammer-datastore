# Setting up a development environment

## Requirements

- PHP 7.2
- [Composer](https://getcomposer.org/) must be installed

## Setup
Make a copy of the file `.env.example` named `.env`

Populate your database credentials in the `.env` file:

        DB_CONNECTION=mysql
        DB_HOST=127.0.0.1
        DB_PORT=3306
        DB_DATABASE=datastore
        DB_USERNAME=*
        DB_PASSWORD=*

From the project root, run:

        composer install
        php artisan migrate
        
Add the project to your vhosts file or equivalent.