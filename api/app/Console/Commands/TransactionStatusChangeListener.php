<?php

namespace Promo\Console\Commands;

use PicPay\Snspp\QueueClient;
use Illuminate\Console\Command;
use Promo\Services\CashbackService;
use Doctrine\ODM\MongoDB\MongoDBException;
use Promo\Documents\Enums\TransactionTypeEnum;
use PicPay\Common\Services\Enums\TransactionStatusEnum;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Responsável por desfazer cashback de transações
 * problemáticas
 *
 * @package Promo\Console\Commands
 */
class TransactionStatusChangeListener extends Command
{
    /**
     * @var \Promo\Services\CashbackService;
     */
    protected $cashback_service;

    protected $signature = 'promo:transactions-listener';

    protected $queue_name = 'promo/core/transaction-status-change';

    public function __construct()
    {
        parent::__construct();

        $this->cashback_service = app()->make(CashbackService::class);
    }

    public function handle()
    {
        $this->info('Escutando a fila '. $this->queue_name);

        QueueClient::listen($this->queue_name, self::class);
    }

    /**
     * Método chamado em cada transação com erro
     *
     * @param array $data
     * @return void
     *
     * @throws MongoDBException
     */
    public function __invoke(array $data)
    {
        $new_status = $data['Transaction.new_status'];

        if (in_array($new_status, TransactionStatusEnum::getUnauthorizedStatuses()))
        {
            $consumer_id = (int) $data['Transaction']['consumer_id'];
            $id = (int) $data['Transaction']['id'];

            try
            {
                $this->cashback_service->undoCashback($consumer_id, TransactionTypeEnum::PAV, $id);
            }
            catch (NotFoundHttpException $e)
            {
                return;
            }
        }
    }

}