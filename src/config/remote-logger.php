<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Remote Logging Server URL
    |--------------------------------------------------------------------------
    |
    | The URL of your remote logging server API endpoint.
    |
    */
    'url' => env('REMOTE_LOGGER_URL', 'http://localhost:8000/api/logs'),

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | The name of your application. This will be sent with each log entry
    | to identify which application the log came from.
    |
    */
    'application' => env('REMOTE_LOGGER_APP_NAME', env('APP_NAME', 'laravel')),

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | The Bearer token sent with each request to authenticate
    | with your remote logging server.
    |
    */
    'api_key' => env('REMOTE_LOGGER_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Log Level
    |--------------------------------------------------------------------------
    |
    | The minimum log level to send to the remote server.
    | Available levels: debug, info, notice, warning, error, critical, alert, emergency
    |
    */
    'level' => env('REMOTE_LOGGER_LEVEL', 'debug'),

    /*
    |--------------------------------------------------------------------------
    | Async Logging
    |--------------------------------------------------------------------------
    |
    | When true, logs will be queued and sent asynchronously to avoid
    | slowing down your application. Requires queue configuration.
    |
    */
    'async' => env('REMOTE_LOGGER_ASYNC', true),

    /*
    |--------------------------------------------------------------------------
    | Queue Name
    |--------------------------------------------------------------------------
    |
    | The queue name to use for async logging jobs.
    | Set to null to use the default queue.
    |
    */
    'queue' => env('REMOTE_LOGGER_QUEUE', null),
];
