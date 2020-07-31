<?php

return [
    'default' => env('PICPAY_PROMO_DRIVE', 'rdkafka'),

    'connections' => [

        /**
         * @link https://php-enqueue.github.io/transport/kafka/
         * @link https://github.com/edenhill/librdkafka/blob/master/CONFIGURATION.md
         * @link https://github.com/edenhill/librdkafka/issues/826
         */
        'rdkafka' => [
            'global' => [
                'group.id' => env('PICPAY_PROMO_KAFKA_GROUP_ID', ''),
                'metadata.broker.list' => env('PICPAY_PROMO_KAFKA_BROKER', ''),
                'enable.auto.offset.store' => env('PICPAY_PROMO_KAFKA_AUTO_OFFSET_STORE', 'false'),
                'message.send.max.retries' => env('PICPAY_PROMO_KAFKA_MESSAGE_MAX_RETRIES', '2'),
                'request.required.acks' => env('PICPAY_PROMO_KAFKA_REQUEST_REQUIRED_ACKS', '-1'),
                'max.poll.interval.ms' => env('PICPAY_PROMO_KAFKA_MAX_POOL_INTERVAL_MS', '300000'),
            ],
            'topic' => [
                'auto.offset.reset' => env('PICPAY_PROMO_KAFKA_AUTO_OFFSET_RESET', 'earliest'),
            ],
            /**
             * @link https://arnaud-lb.github.io/php-rdkafka/phpdoc/rdkafka-topicconf.setpartitioner.html
             */
            'partitioner' => null,
            'log_level' => 0, // 0 .. 7
            'commit_async' => true,
        ],

        /**
         * @link https://php-enqueue.github.io/transport/pheanstalk/
         */
        'beanstalkd' => [
            'host' => env('PICPAY_BROKER_BEANSTALKD_HOST', 'localhost'),
            'port' => env('PICPAY_BROKER_BEANSTALKD_PORT', '11300'),
            'timeout' => env('PICPAY_BROKER_BEANSTALKD_TIMEOUT', null),
        ],

        /**
         * @link https://php-enqueue.github.io/transport/redis/
         */
        'redis' => [
            'host' => env('PICPAY_BROKER_REDIS_HOST', 'localhost'),
            'port' => env('PICPAY_BROKER_REDIS_PORT', 6379),
            'scheme_extensions' => [
                env('PICPAY_BROKER_REDIS_SCHEME_EXTENSIONS', 'predis')
            ],
            'predis_options' => [
                'prefix'  => env('PICPAY_BROKER_REDIS_PREFIX', '')
            ]
        ],

    ]

];
