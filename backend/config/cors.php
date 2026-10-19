<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // Only the SPA may call the API from a browser.
    'allowed_origins' => array_filter(explode(',', (string) env('FRONTEND_URLS', 'http://localhost:5173,http://127.0.0.1:5173'))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    // The SPA reads it to show/report the id of a failed request.
    'exposed_headers' => ['X-Request-Id'],

    'max_age' => 0,

    'supports_credentials' => false,

];
