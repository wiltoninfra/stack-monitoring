<?php
return [
    'default' => env('FILESYSTEM_DRIVER', 'local'),

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],
        's3' => [
            'driver' => 's3',
            'key' => env('PICPAY_S3_KEY'),
            'secret' => env('PICPAY_S3_SECRET'),
            'bucket' => env('PICPAY_S3_BUCKET'),
            'region' => 'sa-east-1',
        ],
    ],
];