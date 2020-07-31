<?php
return [
    'fetch' => PDO::FETCH_CLASS,

    'default' => 'main',

    'connections' => [
        'main' => [
            'driver' => 'mongodb',
            'dsn' => env('PICPAY_PROMO_MONGODB_DSN', 'mongo.promo.dev'),
            'database' => env('PICPAY_PROMO_MONGODB_DATABASE', 'promo')
        ],
        'legacy' => [
            'driver' => 'mysql',
            'host' => env('PICPAY_DB_HOST'),
            'database' => env('PICPAY_DB_NAME'),
            'username' => env('PICPAY_DB_LOGIN'),
            'password' => env('PICPAY_DB_PASSWORD'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'strict' => false,
        ]
    ],

    'redis' => [

        'client' => 'predis',

        'default' => [
            'host' => env('PICPAY_REDIS_HOST'),
            'port' => env('PICPAY_REDIS_PORT'),
            'read_write_timeout' => -1,
            'persistent' => 1
        ],

    ],

    'migrations' => 'migrations',
];