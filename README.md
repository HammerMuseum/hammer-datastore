# Data store

To set up:
 
 - Copy the `.env.example` file and rename to `.env`
 - Add database credentials to the `.env` file
 - Run:

        composer install
        php artisan migrate
To run tests:

        phpunit
New API endpoints can be added in `routes/api.php`