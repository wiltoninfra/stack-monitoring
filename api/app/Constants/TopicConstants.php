<?php

namespace Promo\Constants;

/**
 * Class TopicConstants
 * @package Promo\Constants
 */
class TopicConstants
{

    /**
     * Topic to associate consumer
     */
    const CAMPAIGN_ASSOCIATE = 'lambda_mixpanel_webhook_promo_associate-campaign-associate';

    const CAMPAIGN_ASSOCIATE_DQL = 'lambda_mixpanel_webhook_promo_associate-campaign-associate-DLQ';

    /**
     * Topic to send message information to Mixpanel
     */
    const MIXPANEL_MESSAGE_SENT = 'promo_mixpanel-message-sent';

    const MIXPANEL_MESSAGE_SENT_DLQ = 'promo_mixpanel-message-sent-DLQ';
}
