<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Custom Logging Channels
    |--------------------------------------------------------------------------
    */

    'api' => [
        'driver' => 'daily',
        'path' => storage_path('logs/api.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
    ],

    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => 'warning',
        'days' => 30,
    ],

    'performance' => [
        'driver' => 'daily',
        'path' => storage_path('logs/performance.log'),
        'level' => 'info',
        'days' => 7,
    ],

    'notifications' => [
        'driver' => 'daily',
        'path' => storage_path('logs/notifications.log'),
        'level' => 'info',
        'days' => 14,
    ],
];
