# Authenticating requests

This API is authenticated using a Bearer token.

Send your token in the `Authorization` header:

`Authorization: Bearer <token>`

You can retrieve the token by calling the login endpoint (`POST /api/login`). 
