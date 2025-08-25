<?php
// Config file for Mllexx/IFS
return [
    'base_uri' => env('IFS_BASE_URI'),
    'api_key' => env('IFS_API_KEY'),
    'client_id'=>env('IFS_CLIENT_ID'),
    'client_secret'=>env('IFS_CLIENT_SECRET'),
    'token_endpoint'=>env('IFS_TOKEN_ENDPOINT'),
    'timeout' => env('IFS_TIMEOUT', 30),
    'version' => env('IFS_VERSION', 'v1'),
    'command_output' => 'Another thing is coming out again!',
];