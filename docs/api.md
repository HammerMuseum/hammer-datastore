# API

## Authentication

When setting up API authentication for the first time, use the following command:

### Creating an API user

```sh
php artisan api:register-user
```

#### Generating an API token for the user

The returned data will contain an `api_token`. This value should be used as a
bearer token for all endpoints requiring authentication.

#### Regenerating API tokens

1. Run `php artisan api:refresh-token`.
2. Enter the email address used to create the token.

## Authenticated endpoints

### DELETE /api/videos/:asset_id

Deletes a video from the Elasticsearch index.

## Developing and testing

Using Insomnia for development and testing is recommended. There is an insomnia file
in the `.insomnia` directory at the root of this repository.

## Documentation

To generate the documentation, go to the `.insomnia` folder in your terminal and run:

```sh
npx serve ./docs
```

### Updating the documentation

First export the updated Insomnia JSON config file into `.insomnia` and then:

```sh
cd .insomnia
npx insomnia-documenter -o docs --config <path-to-insomnia-config>`
```
