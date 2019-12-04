# C(R)UD

The operations for creating, updating or deleting data from the Hammer Datastore:


### Create a new video
        
        curl -X POST http://datastore.rufio.office.cogapp.com/api/video/create \  
        -H "Accept: application/json" \
        -H "Content-type: application/json" \
        -d '{"asset_id": "6", "title": "test", "description": "test desc", "date_recorded": "2019-01-01", "duration": "01:01:33"}'


See `App\Controllers\ApiController->create()`

### Update a video in the datastore
        curl -X PUT http://datastore.rufio.office.cogapp.com/api/video/update/5 \                                                                             
        -H "Accept: application/json" \
        -H "Content-type: application/json" \
        -d '{"asset_id": "5", "title": "API TEST", "description": "An updated API request description", "date_recorded": "2019-12-04", "duration": "0:01:01"}'

This is updated by `asset_id`.

See `App\Controllers\ApiController->update()`


### Delete a video in the datastore

        curl -X DELETE http://datastore.rufio.office.cogapp.com/api/video/delete/6  
        
Where `6` is the value of `asset_id`.

The system utilises soft deletes.
