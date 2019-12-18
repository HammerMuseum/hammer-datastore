# API

## Authentication
When setting up API authentication for the first time, use the following command:

        php artisan api:register-user
        
Follow the steps to create your user.
        
The returned data will contain an `api_token`. This value will need to be appended to any payloads in requests using the `api_token` key.

To make unauthenticated requests, remove the `api_token` key from any payloads or URLs. 

To generate a new API token, run:

        php artisan api:refresh-token

and enter the email address you used to create the token.

Use the `api_token` parameter to authenticate API requests that require authentication.

## Endpoints

Methods to create, read, update and delete resources from the Hammer Datastore.

### GET /api/videos/:asset_id

Retrieve a video.

        curl -X GET https://datastore.url/api/videos/:asset_id

See `App\Controllers\VideoController->getById()`

**Responses**:
* `200` if successful
* `404` if failed (resource not found)

### GET /api/videos

Retrieve all videos.

        curl -X GET https://datastore.url/api/videos

See `App\Controllers\VideoController->getAllVideos()`

**Responses**:
* `200` if successful
* `404` if failed (resource not found)

### POST /api/videos

Add a video.

        curl -X POST https://datastore.url/api/videos \  
        -H "Accept: application/json" \
        -H "Content-type: application/json" \
        -d '{"asset_id": "6", "title": "test", "description": "test desc", "date_recorded": "2019-01-01", "duration": "01:01:33"}'
        
**Responses**:
* `201` if successful
* `400` if failed (resource not found)

#### Arguments

**Headers**: `"Accept": "application/json"`

api_token **required**

A valid API token.

See `App\Controllers\ApiController->create()`

### PUT /api/videos

Update a video.

        curl -X PUT https://datastore.url/api/videos/:asset_id \
        -H "Accept: application/json" \
        -H "Content-type: application/json" \
        -d '{"asset_id": "5", "title": "API TEST", "description": "An updated API request description", "date_recorded": "2019-12-04", "duration": "0:01:01"}'

#### Arguments

**Headers**: `"Accept": "application/json"`

api_token **required**

A valid API token.

See `App\Controllers\ApiController->update()`

**Responses**:
* `200` if successful
* `404` if failed (resource not found)

### DELETE /api/videos/:asset_id

Delete a video.

        curl -X DELETE https://datastore.url/api/videos/:asset_id  

#### Arguments

**Headers**: `"Accept": "application/json"`

api_token **required**

A valid API token.

See `App\Controllers\ApiController->delete()`

**Responses**:
* `200` if successful
* `404` if failed (resource not found)
* `400` if failed (unable to delete)


