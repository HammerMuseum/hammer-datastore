<!-- Generate a new file using -->
<!-- sed -e "s/\Video Endpoint/My story/" -e "s/\170053908/156128780/" -e "s/\video-endpoint-170053908/`git_current_branch`/g" template.md | tee "`git_current_branch`.md" -->

# Video Endpoint

## Related documentation
- Docs in repo about endpoints and authentication.

## Pivotal Story

* [Video Endpoint](https://www.pivotaltracker.com/story/show/170053908)

## Git branch

* [video-endpoint-170053908](https://github.com/HammerMuseum/hammer-datastore/tree/video-endpoint-170053908)

## Description
A test plan for the initial datastore API endpoints. The tester should prove the following:
- Videos can be added to the datastore with an authenticated API request
- Attempts to add video over public internet (unathenticated) return a 404
- Calling the API endpoint with a specific video ID returns the correct metadata 

## Requirements to test
- A local setup of the `hammer-datastore` repository with a database attached (follow the [getting started](../getting-started.md) guide to ensure your setup is complete)
- Any setup to allow the tester to make API POST requests e.g cURL or Postman.

## Test Plan
- Using the documentation available in the repository, make an authenticated POST request to the datastore, adding a new video.
    - You should receive a response indicating success.
    - The status code should be 201
    - Make a GET request to your video by its `asset_id`
        - The data returned should correctly match what you added.


- Using the documentation available in the repository, make an *unauthenticated* POST request to the datastore, attempting to add a new video.
    - You should receive an error.
    
- Using the documentation available in the repository, make an authenticated POST request to the datastore, updating an existing video by its asset ID.
    - You should receive a response indicating success.
    - The status code should be 200
    - Check in the `video` table in the database
        - Your video's data should be updated.    
    
- Using the documentation available in the repository, make an *unauthenticated* POST request to the datastore, attempting to update an existing video.
    - You should receive an unauthenticated error.
    
    
- Using the documentation available in the repository, make an authenticated GET request to the datastore, to retrieve your video.
    - You should receive a response in form of a JSON representation of the exact data associated with your video.
        - Is ID returned?
        - Is description returned?
        - Is title returned?
        - Is a URL returned?
        - Is a date returned?
        - Is a duration returned?
    - The status code should be 200
    
- Save the JSON result of the GET request to a file and attach it to the Pivotal story with testing results.


- As above, make an authenticated request to delete a video.
    - You should receive a confirmation of success.
- As above, make an **unauthenticated** request to delete a video.
    - You should receive an unauthenticated error.
    
    
- Attempt to create a new video with an asset ID that already exists in the database.
    - You should be warned that the video already exists.
- Attempt to update a video with an asset ID that doesn't exist in the database.
    - You should be warned that no such video exists.
- Attempt to delete a video with an asset ID that doesn't exist in the database.
    - You should be warned that the video doesn't exist.