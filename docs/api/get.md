# GET requests

The two main operations for retrieving data from the Hammer Datastore:


### Get a video by its datastore ID
        /api/video/id/1
        
where 1 is the datastore ID.

See `App\Controllers\VideoController->getById()`

### Get all videos from the datastore
        /api/video/all
        
As of the time of writing this, results are not paginated, but should be in the future.

See `App\Controllers\VideoController->getAllVideos()`
