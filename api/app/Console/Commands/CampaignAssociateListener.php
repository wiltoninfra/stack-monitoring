<?php

namespace Promo\Console\Commands;

use Enqueue\Consumption\Result;
use Illuminate\Support\Facades\Log;
use Interop\Queue\Context;
use Interop\Queue\Message;
use PicPay\Brokers\Console\Command\EnqueueProcessorCommand;
use PicPay\Brokers\Contracts\ConsumerInterface;
use Promo\Constants\TopicConstants;
use Promo\Documents\Webhook;
use Promo\Events\Mixpanel\CampaignAssociateMessageSentEvent;
use Promo\Services\ConsumerCampaignService;
use Promo\Services\WebhookService;
use Promo\Services\Notification\Template\NotificationTemplate;
use Promo\Services\Notification\NotificationService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

/**
 * Class CampaignAssociateListener
 * @package App\Console\Commands
 */
class CampaignAssociateListener extends EnqueueProcessorCommand
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promo:campaign-associate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command get consumer to associate or if campaign is communication, just send to ms-notification';

    /**
     * Name of queue/topic
     * @var string
     */
    protected $queueName = TopicConstants::CAMPAIGN_ASSOCIATE;

    /**
     * Name of dlq-queue/topic
     * @var string
     */
    protected $dlqQueueName = TopicConstants::CAMPAIGN_ASSOCIATE_DQL;

    /**
     * Number of retry attempts to downstream each message
     * @var int
     */
    protected $attempts = 1;

    /**
     * @var ConsumerCampaignService
     */
    private $consumerCampaignService;

    /**
     * @var WebhookService
     */
    private $webhookService;

    /**
     * @var NotificationService
     */
    private $notificationService;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var mixed
     */
    private $log;

    /**
     * Time to save webhook payload on redis
     */
    const REDIS_MINUTES = 1;

    /**
     *
     */
    const PERCENTAGE = 100;

    /**
     * CampaignAssociateListener constructor.
     * @param ConsumerInterface $consumer
     * @param LoggerInterface $logger
     * @param ConsumerCampaignService $consumerCampaignService
     * @param WebhookService $webhookService
     * @param NotificationService $NotificationService
     * @param DocumentManager $documentManager
     */
    public function __construct
    (
        ConsumerInterface $consumer,
        LoggerInterface $logger,
        ConsumerCampaignService $consumerCampaignService,
        WebhookService $webhookService,
        NotificationService $NotificationService,
        DocumentManager $documentManager
    ){
        parent::__construct($consumer, $logger);
        $this->consumerCampaignService = $consumerCampaignService;
        $this->webhookService = $webhookService;
        $this->notificationService = $NotificationService;
        $this->documentManager = $documentManager;
        $this->log = Log::channel('log_php_command_stdout');
    }

    /**
     * @throws \Exception
     */
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

            $associations = json_decode($message->getBody(), true);
            $this->log->info("associate-campaign-payload", [
                'payload_received' => $associations
            ]);
            $associations = $associations['data'];

            $payloadWebhook = [];

            if (isset($associations[0]['webhook_id'])) {
                $webhook_id = $associations[0]['webhook_id'];
                $payloadWebhook = $this->getWebhook($webhook_id);
            }

            $consumers = $this->prepareConsumers($associations);

            if (empty($payloadWebhook)) {
                return Result::REJECT;
            }

            try{

                if ($payloadWebhook['campaign']['communication']) {
                    $this->sendNotificationsByVariant($payloadWebhook, $associations);
                    return Result::ACK;
                }

                $consumersCampaignToRestart = $this->consumerCampaignService->getConsumersAssociatedByCampaign(
                    $consumers,
                    $payloadWebhook['campaign']['id']
                );

                $consumersToAssociate = array_diff(
                    $consumers,
                    $this->restartAssociateConsumerCampaign($consumersCampaignToRestart, $payloadWebhook)
                );

                if (!empty($consumersToAssociate)) {
                    $this->consumerCampaignService->associateConsumers($consumersToAssociate, $payloadWebhook['campaign']['id']);
                }

                $this->sendNotificationsByVariant($payloadWebhook, $associations);
                $this->documentManager->flush();

                foreach ($consumersCampaignToRestart as $consumer_campaign) {
                    $this->documentManager->detach($consumer_campaign);
                }

                return Result::ACK;

            }catch (\Exception $exception) {

                $this->log->info("associate-campaign", [
                    'status_job' => 'false',
                    'message_job' => "Fail to associate consumers to campaign",
                    'campaign_id' => $payloadWebhook['campaign']['id'],
                    'exception_message' => $exception->getMessage()
                ]);

                return Result::REJECT;
            }
    }

    /**
     * @param $consumersCampaignToRestart
     * @param $payloadWebhook
     * @return array
     */
    private function restartAssociateConsumerCampaign($consumersCampaignToRestart, $payloadWebhook)
    {
        $consumerCampaignRestarted = [];

        foreach ($consumersCampaignToRestart as $consumer_campaign) {
            $consumerCampaignRestarted[] = $consumer_campaign->getConsumerId();
            $consumer_campaign->restartRelationship();
            $consumer_campaign->setCampaignActive($payloadWebhook['campaign']['active']);

            $this->log->info("associate-campaign", [
                'status_job' => 'success',
                'message_job' => "Success to restart consumer to campaign",
                'associate_type' => 'restart',
                'consumer_id' => $consumer_campaign->getConsumerId(),
                'campaign_id' => $payloadWebhook['campaign']['id']
            ]);
        }

        return $consumerCampaignRestarted;
    }

    /**
     * @param $payloadWebhook
     * @param $associations
     */
    private function sendNotificationsByVariant($payloadWebhook, $associations)
    {
        $percentage = self::PERCENTAGE;
        foreach ($payloadWebhook['variants'] as $variant) {
            $ceil = ( ceil(($variant['webhook_percentage'] / $percentage) * count($associations) ));
            $percentage = $percentage - $variant['webhook_percentage'];
            $notificationsDispatch = array_slice($associations, 0, $ceil);

            foreach ($notificationsDispatch as $notification) {

                $this->dispatchNotifications($variant, $notification);

                $this->mixpanelMessageSent($variant, $notification);

                $associations = array_filter($associations, function($entry) use ($notification) {
                    return $entry != $notification;
                });

            }
        }
    }

    /**
     * @param $variant
     * @param $notification
     */
    private function dispatchNotifications($variant, $notification)
    {

        if (!empty($variant['in_app']) ) {
            $this->notificationService->sendMassAppNotification(
                NotificationTemplate::getAppNotificationTemplate(
                    $variant,
                    $notification['properties'],
                    $notification['consumer_id'],
                    $variant['campaign']['id']
                )
            );
        }

        if (!empty($variant['push']) ) {
            $this->notificationService->sendMassPushNotification(
                NotificationTemplate::getPushNotificationTemplate(
                    $variant,
                    $notification['properties'],
                    $notification['consumer_id'],
                    $variant['campaign']['id']
                )
            );
        }

        if (!empty($variant['sms']) && !empty($notification['properties']['$phone'])) {
            $this->notificationService->sendSmsNotification(
                NotificationTemplate::getSmsNotificationTemplate(
                    $notification['properties']['$phone'],
                    $variant,
                    $notification['consumer_id']
                )
            );
        }

    }

    /**
     * @param $variant
     * @param $notification
     */
    private function mixpanelMessageSent($variant, $notification)
    {
        event(new CampaignAssociateMessageSentEvent(
            $notification['consumer_id'],
            $variant['webhook_variant_name'],
            $variant['campaign']['id'],
            $variant['campaign']['name']
        ));
    }

    /**
     * @param Webhook $webhook
     * @return array
     */
    private function createVariantsData(Webhook $webhook) : array
    {
        $variantsData = [];
        foreach ($webhook->getVariants() as $variant){

            $variantsData['campaign']['id'] = $webhook->getCampaign()->getId();
            $variantsData['campaign']['name'] = $webhook->getCampaign()->getName();
            $variantsData['campaign']['active'] = $webhook->getCampaign()->isActive();
            $variantsData['campaign']['communication'] = $webhook->getCampaign()->isCommunication();

            $variantPayload = [
                'webhook_id' => $webhook->getId(),
                'webhook_percentage' => $variant->getPercentage(),
                'webhook_variant_name' => $variant->getName(),
                'campaign' => [
                    'id' => $webhook->getCampaign()->getId(),
                    'name' => $webhook->getCampaign()->getName(),
                ],
                'target' => [
                    'model' => $variant->getTarget()->getModel(),
                    'href' => $variant->getTarget()->getHref(),
                    'params' => $variant->getTarget()->getParams(),
                    'user_properties' => $variant->getTarget()->getUserProperties(),
                    'mixpanel_properties' => $variant->getTarget()->getMixpanelProperties()
                ]
            ];

            if ($variant->getPush()) {
                $variantPayload['push'] = [
                    'title' => $variant->getPush()->getTitle(),
                    'message' => $variant->getPush()->getMessage(),
                ];
            }

            if ($variant->getInApp()) {
                $variantPayload['in_app'] = [
                    'message' => $variant->getInApp()->getMessage(),
                ];
            }

            if ($variant->getSMS()) {
                $variantPayload['sms'] = [
                    'message' => $variant->getSMS()->getMessage(),
                ];
            }

            $variantsData['variants'][] = $variantPayload;
        }

        return $variantsData;
    }

    /**
     * @param $webhook_id
     * @return mixed
     */
    private function getWebhook($webhook_id)
    {
        $payloadWebhook = Cache::remember($webhook_id, self::REDIS_MINUTES, function () use ($webhook_id) {
            try{
                $webhook = $this->webhookService->get($webhook_id);
                $this->documentManager->detach($webhook);
                return $this->createVariantsData($webhook);
            }catch (\Exception $exception){

                $this->log->info("associate-campaign", [
                    'status_job' => 'false',
                    'message_job' => "Fail to associate consumer to campaign",
                    'webhook_id' => $webhook_id,
                    'exception_message' => $exception->getMessage()
                ]);

                return [];
            }
        });

        return $payloadWebhook;
    }

    /**
     * @param $data
     * @return array
     */
    private function prepareConsumers($data)
    {
        return array_map(function ($array){
            return $array['consumer_id'];
        }, $data);

    }
}
