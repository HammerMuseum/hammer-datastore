# Authentication

When setting up API authentication for the first time, run the following:

        curl -X POST http://YOURDATASTOREURL/api/register \
        -H "Accept: application/json" \
        -H "Content-type: application/json" \
        -d '{"name": "Your name", "email": "your.email@cogapp.com", "password": "yourPassword", "password_confirmation": "yourPassword"}'
        
The returned data will contain an `api_token`. This value will need to be appended to any payloads in requests using the `api_token` key.