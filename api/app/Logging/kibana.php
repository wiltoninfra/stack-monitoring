<?php

namespace Promo\Logging;

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PicPay\Common\Laravel\LogFormatter\LogFormatter;
use Psr\Log\LoggerInterface;

class Kibana
{

    /**
     * @var RequestId
     */
    private $requestId;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Graylog constructor.
     * @param RequestId $requestId
     */
    public function __construct(RequestId $requestId)
    {
        $this->requestId = $requestId;
    }

    /**
     * Graylog class
     *
     * Responsável por adaptar o logger para o formato do Kibana do PicPay
     *
     * @return \Monolog\Logger
     * @throws \Exception
     */
    public function __invoke(array $config): Logger
    {
        $this->logger = new Logger('promo');
        $handler = new StreamHandler(storage_path('logs/app.log'));
        $formatter = new LogFormatter('promo');
        $formatter::$requestGuid = $this->requestId->get();
        $handler->setFormatter($formatter);

        return $this->logger->pushHandler($handler);
    }
}
