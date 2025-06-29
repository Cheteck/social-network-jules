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

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'login', // Ajouter les routes Fortify si nécessaire
        'register',
        'logout',
        // 'forgot-password', // etc.
        // 'reset-password',
    ],

    'allowed_methods' => ['*'], // Peut être restreint si besoin (GET, POST, PUT, DELETE, etc.)

    'allowed_origins' => ['http://localhost:3000', 'http://localhost:5173'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'], // Peut être restreint (Content-Type, X-XSRF-TOKEN, X-Requested-With, Accept, Authorization)

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, // DOIT être true pour Sanctum SPA

];
