<?php

namespace Promo\Console\Commands;

use PicPay\Snspp\QueueClient;
use Illuminate\Console\Command;
use Promo\Services\CashfrontService;
use Promo\Services\Core\CoreService;

/**
 * Responsável por desfazer cashback de transações
 * problemáticas P2P
 *
 * @package Promo\Console\Commands
 */
class DepositCompletionListener extends Command
{
    /**
     * @var \Promo\Services\CashfrontService;
     */
    protected $cashfront_service;

    /**
     * @var \Promo\Services\Core\CoreService;
     */
    protected $core_service;

    protected $signature = 'promo:completed-deposits-listener';

    protected $queue_name = 'promo/deposit/deposit-completed';

    public function __construct()
    {
        parent::__construct();

        $this->cashfront_service = app()->make(CashfrontService::class);
        $this->core_service = app()->make(CoreService::class);
    }

    public function handle()
    {
        $this->info('Escutando a fila '. $this->queue_name);

        QueueClient::listen($this->queue_name, self::class);
    }

    /**
     * Método chamado em cada depósito concluído
     *
     * @param array $deposit
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function __invoke(array $deposit)
    {
        try
        {
            $data = [
                'id'              => (string) $deposit['id'],
                'recharge_method' => (string) $deposit['recharge_method'],
                'total'           => (float) $deposit['value'],
                'first_deposit'   => (bool) $deposit['first_recharge'] ?? false,
            ];

            $consumer_id = intval($deposit['consumer_id']);

            // Seleciona a campanha e gera o valor de cashfront
            $cashfront = $this->cashfront_service->cashfront($consumer_id, $data);
            $reward_value = (float) ($cashfront['cashfront'] ?? 0.0);

            if ($reward_value > 0)
            {
                // Gera movement de créditos de reward junto ao Core
                $this->core_service->addConsumerCredit($consumer_id, $reward_value);
            }

            // TODO enviar ao Mixpanel

            return;
        }
        catch (\Exception $e)
        {
            \Log::error('Ocorreu erro no listener de cashfront', $e->getMessage());

            return;
        }
    }
}