<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Broadcasting
    |--------------------------------------------------------------------------
    |
    | By uncommenting the Laravel Echo configuration, you may connect Filament
    | to any Pusher-compatible websockets server.
    |
    | This will allow your users to receive real-time notifications.
    |
    */

    'broadcasting' => [

        'echo' => [
            'broadcaster' => 'reverb',
            'key' => env('VITE_REVERB_APP_KEY'),
            'wsHost' => env('VITE_REVERB_HOST'),
            'wsPort' => env('VITE_REVERB_PORT', 80),
            'wssPort' => env('VITE_REVERB_PORT', 443),
            'forceTLS' => (env('VITE_REVERB_SCHEME', 'https') === 'https'),
            'enabledTransports' => ['ws', 'wss'],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | This is the storage disk Filament will use to put media. You may use any
    | of the disks defined in the `config/filesystems.php`.
    |
    */

    'default_filesystem_disk' => env('FILAMENT_FILESYSTEM_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Assets Path
    |--------------------------------------------------------------------------
    |
    | This is the directory where Filament's assets will be published to. It
    | is relative to the `public` directory of your Laravel application.
    |
    | After changing the path, you should run `php artisan filament:assets`.
    |
    */

    'assets_path' => null,

];
