<?php


namespace Promo\Console\Commands;
use Enqueue\Consumption\Result;
use Illuminate\Support\Facades\Log;
use Interop\Queue\Context;
use Interop\Queue\Message;
use PicPay\Brokers\Console\Command\EnqueueProcessorCommand;
use PicPay\Brokers\Contracts\ConsumerInterface;
use Promo\Clients\MixPanelClient;
use Promo\Constants\TopicConstants;
use Promo\Documents\Enums\MixPanelEventsEnum;
use Psr\Log\LoggerInterface;

/**
 * Class MixpanelMessageSentCommand
 * @package Promo\Console\Commands
 */
class MixpanelMessageSentCommand extends EnqueueProcessorCommand
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promo:mixpanel-message-sent';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will get data from consumer to send to mixpanel-message';

    /**
     * Name from queue to be executed
     *
     * @var string
     */
    protected $queueName = TopicConstants::MIXPANEL_MESSAGE_SENT;

    /**
     * Name of dlq-queue/topic
     * @var string
     */
    protected $dlqQueueName = TopicConstants::MIXPANEL_MESSAGE_SENT_DLQ;

    /**
     * Number of retry attempts to downstream each message
     * @var int
     */
    protected $attempts = 1;

    /**
     * @var mixed
     */
    private $log;

    /**
     * @var MixPanelClient
     */
    private $mixPanelClient;

    /**
     * MixpanelMessageSentCommand constructor.
     * @param ConsumerInterface $consumer
     * @param LoggerInterface $logger
     * @param MixPanelClient $mixPanelClient
     */
    public function __construct
    (
        ConsumerInterface $consumer,
        LoggerInterface $logger,
        MixPanelClient $mixPanelClient
    )
    {
        parent::__construct($consumer, $logger);
        $this->log = Log::channel('log_php_command_stdout');
        $this->mixPanelClient = $mixPanelClient;
    }

    /**
     * The method has to return either self::ACK, self::REJECT, self::REQUEUE string.
     *
     * The method also can return an object.
     * It must implement __toString method and the method must return one of the constants from above.
     *
     * @param Message $message
     * @param Context $context
     *
     * @return string|object with __toString method implemented
     */
    public function process(Message $message, Context $context)
    {
        $this->log->info("Listening to queue: {$this->queueName}");

        try{

            $message = json_decode($message->getBody(), true);
            $this->mixPanelClient->track(
                $message['distinct_id'],
                MixPanelEventsEnum::CAMPAIGN_DELIVERY,
                $message
            );

            $this->log->info("mixpanel-message-sent", [
                'status_job' => 'success',
                'message_job' => "Success to send message to mixpanel",
                'distinct_id' => $message['distinct_id'],
                'Nome da Variacao' => $message['Nome da Variacao'],
                'Variacao Recebida' => $message['Variacao Recebida'],
                'ID da Campanha' => $message['ID da Campanha'],
                'Nome da Campanha' => $message['Nome da Campanha']
            ]);

            return Result::ACK;

        }catch (\Exception $exception) {

            $this->log->error("mixpanel-message-sent", [
                'status_job' => 'success',
                'message_job' => "fail to send message to mixpanel",
                'distinct_id' => $message['distinct_id'],
                'Nome da Variacao' => $message['Nome da Variacao'],
                'Variacao Recebida' => $message['Variacao Recebida'],
                'ID da Campanha' => $message['ID da Campanha'],
                'Nome da Campanha' => $message['Nome da Campanha'],
                'exception_message' => $exception->getMessage()
            ]);

            return Result::REJECT;

        }
    }

}
