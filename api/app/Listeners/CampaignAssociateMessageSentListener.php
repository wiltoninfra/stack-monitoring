<?php

namespace Promo\Listeners;

use Illuminate\Support\Facades\Log;
use Promo\Constants\TopicConstants;
use Promo\Events\Mixpanel\CampaignAssociateMessageSentEvent;
use PicPay\Brokers\Facades\Producer;

/**
 * Class CampaignAssociateMessageSentListener
 * @package Promo\Listeners
 */
class CampaignAssociateMessageSentListener
{

    /**
     * CampaignAssociateMessageSentListener constructor.
     */
    public function __construct()
    {
        $this->log = Log::channel('log_php_command_stdout');
    }

    /**
     * @param CampaignAssociateMessageSentEvent $campaignAssociateMessageSentEvent
     */
    public function handle(CampaignAssociateMessageSentEvent $campaignAssociateMessageSentEvent)
    {
        try{

            Producer::produce(TopicConstants::MIXPANEL_MESSAGE_SENT, [
                    'distinct_id' => $campaignAssociateMessageSentEvent->getDistinctId(),
                    'Nome da Variacao' => $campaignAssociateMessageSentEvent->getNameVariant(),
                    'Variacao Recebida' => $campaignAssociateMessageSentEvent->getVariantReceived(),
                    'ID da Campanha' => $campaignAssociateMessageSentEvent->getCampaignId(),
                    'Nome da Campanha' => $campaignAssociateMessageSentEvent->getCampaignName(),
                ]
            );

            $this->log->info("mixpanel-message-sent-queue", [
                'status_job' => 'success',
                'message_job' => "Success to dispatch to topic",
                'distinct_id' => $campaignAssociateMessageSentEvent->getDistinctId(),
                'Nome da Variacao' => $campaignAssociateMessageSentEvent->getNameVariant(),
                'Variacao Recebida' => $campaignAssociateMessageSentEvent->getVariantReceived(),
                'ID da Campanha' => $campaignAssociateMessageSentEvent->getCampaignId(),
                'Nome da Campanha' => $campaignAssociateMessageSentEvent->getCampaignName()
            ]);

        }catch (\Exception $exception){

            $this->log->error("mixpanel-message-sent-queue", [
                'status_job' => 'fail',
                'message_job' => "Fail to dispatch to topic",
                'distinct_id' => $campaignAssociateMessageSentEvent->getDistinctId(),
                'Nome da Variacao' => $campaignAssociateMessageSentEvent->getNameVariant(),
                'Variacao Recebida' => $campaignAssociateMessageSentEvent->getVariantReceived(),
                'ID da Campanha' => $campaignAssociateMessageSentEvent->getCampaignId(),
                'Nome da Campanha' => $campaignAssociateMessageSentEvent->getCampaignName(),
                'exception_message' => $exception->getMessage()
            ]);
        }

    }

}
