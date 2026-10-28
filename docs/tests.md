# Tests

The feature tests call each API endpoint against a test Elasticsearch index.

## Testing environment

Tests use the `.env.testing` environment file. It uses the
`video_testing` index by default.

Load the test index before running the tests:

    ./elasticsearch/scripts/load-test-index.py http://localhost:9200 video_testing

## Running tests

Run from the project root:

    ./vendor/bin/phpunit tests

## Creating tests

Run from the project root:

    php artisan make:test MyTestClass
