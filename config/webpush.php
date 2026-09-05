<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WebPush VAPID Keys & Configuration
    |--------------------------------------------------------------------------
    |
    | VAPID keys allow WebPush notifications to be sent safely from your server
    | to browser clients (Chrome, Firefox, Safari, Edge).
    |
    */

    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:admin@drasthabest.com'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
        'pem_file' => env('VAPID_PEM_FILE'),
    ],

    'model' => \NotificationChannels\WebPush\PushSubscription::class,

    'table_name' => env('WEBPUSH_DB_TABLE', 'push_subscriptions'),

    'database_connection' => env('WEBPUSH_DB_CONNECTION'),

];
