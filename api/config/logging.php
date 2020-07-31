<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => 'stack',

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "custom", "stack"
    |
    */

    'channels' => [
        'kibana' => [
            'driver' => 'custom',
            'via' => Promo\Logging\Kibana::class,
            'level' => 'info',
        ],
        'log_php_command_stdout' => [
            'driver' => 'stack',
            'channels' => ['kibana', 'syslog', 'log_php_stdout']
        ],
        'log_php_stdout' => [
            'driver' => 'custom',
            'via' => Promo\Logging\LogPhpCommandStdout::class,
            'level' => 'info',
        ],
        'stack' => [
            'driver' => 'stack',
            'channels' => ['kibana', 'syslog'],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => 'debug',
        ],

        'graylog' => [
            'driver' => 'custom',
            'via' => Promo\Logging\Graylog::class,
            'level' => 'info',
        ],
    ],

];
