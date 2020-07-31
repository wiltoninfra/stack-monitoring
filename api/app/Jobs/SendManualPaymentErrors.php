<?php


namespace Promo\Jobs;

use PicPay\Common\Slack\SlackClient;


class SendManualPaymentErrors extends Job
{
    private $message;
    private $errors;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $errors, string $message)
    {
        $this->errors = $errors;
        $this->message = $message;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $channel = config('app.payment-slack-channel');
            $client = new SlackClient($channel, 'Pagamentos Manuais');
            $client->send("*{$this->message}*");
            $client->send(implode( "\n", $this->errors));

            \Log::info("Pendencias de pagamento manual enviados ao Slack");
        }
        catch(\Exception $e) {
            \Log::error("Erro ao enviar pendencias de pagamento manual ao core:", $e->getMessage());

            throw $e;
        }
    }
}