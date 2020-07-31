<?php

namespace Promo\Logging;

use Promo\Logging\RequestId;
use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PicPay\Common\Laravel\LogFormatter\LogFormatter;

/**
 * Class Kibana
 * @package App\Logging
 */
class LogPhpCommandStdout
{
    protected $requestId;

    public function __construct(RequestId $request)
    {
        $this->requestId = $request;
    }

    /**
     * Kibana class
     *
     * Responsável por adaptar o logger para o formato do Kibana do PicPay
     *
     * @param array $config
     * @return Logger
     * @throws Exception
     */
    public function __invoke(array $config): Logger
    {
        $logger = new Logger('promo');
        $handler = new StreamHandler('php://stdout');
        $formatter = new LogFormatter('promo');
        $formatter::$requestGuid = $this->requestId->get();
        $handler->setFormatter($formatter);
        return $logger->pushHandler($handler);
    }
}

