# Authentication

When setting up API authentication for the first time, use the following command:

        php artisan register:user
        
Follow the steps to create your user.
        
The returned data will contain an `api_token`. This value will need to be appended to any payloads in requests using the `api_token` key.

To make unauthenticated requests, remove the `api_token` key from any payloads or URLs. 

To generate a new API token, run:

        php artisan token:refresh
        
and enter the email address you used to create the token.