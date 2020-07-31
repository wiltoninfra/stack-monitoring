<?php


namespace Promo\Jobs;
use Promo\Services\Core\CoreService;

class ListenManualPayments extends Job
{
    const MANUAL_CREDIT_HIDDEN = 'manual_credit_hidden';

    private $consumer;
    private $payment;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $consumer,float $payment)
    {
        $this->consumer = $consumer;
        $this->payment = $payment;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $coreService = new CoreService();
            $coreService->addConsumerCredit(
                $this->consumer,
                $this->payment,
                self::MANUAL_CREDIT_HIDDEN
            );

            $info = array(
                "Consumer" => $this->consumer,
                "Valor" => $this->payment,
            );

            \Log::info("Pagamento manual solicitado ao core", $info);
            //todo Verificar trava pra deposito acidental em campanha
        }
        catch(\Exception $e) {
            \Log::error("Erro ao solicitar pagamento manual ao core:", $e->getMessage());

            throw $e;
        }
    }
}