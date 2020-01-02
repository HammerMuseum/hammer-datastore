# Unit tests

The Datastore system utilises Laravel Dusk browser tests.

## Testing environment
When running tests, Dusk will use the `.env.testing` environment file.

The `config/database.php` file defines the credentials for the testing database to be used under the `mysql_testing` key.

## Running tests
Run from the project root:

    phpunit

## Creating tests
Run from the project root:

    php artisan make:test MyTestClass
    

## Current tests
There are tests for each API endpoint:

    GetVideoTest
    GetAllVideosTest
    CreateVideoTest
    UpdateVideoTest
    DeleteVideoTest
    
These tests utilise two factory classes, which can be found under `database/factories`.

`UserFactory`: Creates a fake user

`VideoFactory`: Creates a fake video asset
