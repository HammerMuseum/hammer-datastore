# API

The API is read-only.

All data comes from Elasticsearch, and the harvester writes to the index directly.

## Documentation

The API docs are generated from the code, so they stay in sync with the routes.

- Browse and try the endpoints at `/docs/api`, e.g. <https://hammer-datastore.ddev.site/docs/api>.
  The docs only load in the `local` environment.
- Get the OpenAPI spec as JSON at `/docs/api.json`.
- Export the spec to a file:

```sh
ddev artisan scramble:export --path=api.json
```
