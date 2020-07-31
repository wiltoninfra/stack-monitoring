<?php

namespace Promo\Logging;

use Monolog\Logger;
use PicPay\Common\Util\FileUtils;
use Monolog\Handler\StreamHandler;
use PicPay\Common\Laravel\LogFormatter\LogFormatter;

class Graylog
{
    /**
     * Graylog class
     *
     * Responsável por adaptar o logger para o formato do Graylog do PicPay
     *
     * @param  array $config
     * @return \Monolog\Logger
     * @throws \Exception
     */
    public function __invoke(array $config)
    {
        $monolog = new Logger('promo');
        $handler = new StreamHandler( FileUtils::join_paths(storage_path('logs/app.log')) );
        $handler->setFormatter(new LogFormatter(env('APP_NAME', 'promo')));

        return $monolog->pushHandler($handler);
    }
}