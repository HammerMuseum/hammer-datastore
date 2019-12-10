<!-- Generate a new file using -->
<!-- sed -e "s/\Video Endpoint/My story/" -e "s/\170053908/156128780/" -e "s/\video-endpoint-170053908/`git_current_branch`/g" spec-template.md | tee "`git_current_branch`.md" -->

# Video Endpoint

A spec for the initial individual video endpoint.

## Pivotal story

[Video Endpoint](https://www.pivotaltracker.com/story/show/170053908)

## Git branch

[video-endpoint-170053908](https://github.com/HammerMuseum/hammer-datastore/video-endpoint-170053908)

## Story description

**Datastore (structure and API schema)**

**NOTE The server is in place at:**

- https://datastore.hammer.cogapp.com
- https://stage.datastore.hammer.cogapp.com

### Application structure

**Adding or updating data**

- The API should accept input data via authenticated POST requests and save to its database.
- Returns data to the front end application via GET (not authenticated).
- Create a model for storing videos

**Getting data out**

- Using something like https://laravel.com/docs/latest/eloquent-resources to get data from the database and conveniently send it back in JSON format. This may also help make things like paginating responses easier (if required).

*Application Deployment*

- Tests should be written for the API endpoints. Test will be run via CircleCI.


**Schema**

POST

GET

- **video/{id}**
    - id (as defined by DAMS)
    - title
    - description
    - video URL (DAMS content URL)
    - date (date of recording)
    - duration


### Acceptance criteria

- Can a video be added to the database via a POST request to /video/{id}?
- Does trying to add a video via POST from public internet return a 404 not found error?
- Does going /video/{id} return JSON with relevant information about the correct video.


- Is ID returned?
- Is description returned, if populated?
- Is title returned?
- Is a URL returned?
- Is a date returned?
- Is a duration returned?
- Do videos use the ID as assigned to the asset in the DAM?

## Implementation
- Create a model with the following command to also generate a migration:

        artisan make:model Video -m
- Write a migration for the `video` table
    - Add the following columns:
    
            id / INT / auto increment / foreign key
            asset_id / INT / use the ID from the DAMS to construct the content URL
            title / VARCHAR / 255
            description / TEXT
            date / DATETIME
            duration / TEXT
            

- Add fillable fields to Video model


### GET operations
- Use Artisan to generate a resource for Video
    - Fill out the `toArray` method with the expected data structure
- Generate and populate a new controller to handle the API calls
    - Return appropriate response codes
- Add api routes for controller methods.
- Write tests for API calls

### POST/CRUD operations
- Generate a new controller
- Create methods for create/read/update/delete
- Add api routes for controller methods.
    - Return appropriate response codes
- Add authentication
- Write tests for CRUD methods
- Write tests for authentication


## Documentation required
- Documentation is required for each individual endpoint.
- Authentication documentation
