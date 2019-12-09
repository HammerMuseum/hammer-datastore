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

Add the `api_token` parameter to `POST` and `PUT` requests.

## C(R)UD

The operations for creating, updating or deleting data from the Hammer Datastore:


### Create a new video
        
        curl -X POST http://YOURDATASTOREURL/api/videos \  
        -H "Accept: application/json" \
        -H "Content-type: application/json" \
        -d '{"asset_id": "6", "title": "test", "description": "test desc", "date_recorded": "2019-01-01", "duration": "01:01:33"}'


See `App\Controllers\ApiController->create()`

### Update a video in the datastore
        curl -X PUT http://YOURDATASTOREURL/api/videos/5 \                                                                             
        -H "Accept: application/json" \
        -H "Content-type: application/json" \
        -d '{"asset_id": "5", "title": "API TEST", "description": "An updated API request description", "date_recorded": "2019-12-04", "duration": "0:01:01"}'

This is updated by `asset_id`.

See `App\Controllers\ApiController->update()`


### Delete a video in the datastore

        curl -X DELETE http://YOURDATASTOREURL/api/videos/6  
        
Where `6` is the value of `asset_id`.

The system utilises soft deletes.


## GET

The two main operations for retrieving data from the Hammer Datastore:


### Get a video by its datastore ID
        /api/videos/1
        
where 1 is the datastore ID.

See `App\Controllers\VideoController->getById()`

### Get all videos from the datastore
        /api/videos
        
As of the time of writing this, results are not paginated, but should be in the future.

See `App\Controllers\VideoController->getAllVideos()`
