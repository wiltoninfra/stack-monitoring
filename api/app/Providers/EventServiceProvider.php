<?php

namespace Promo\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;
use Promo\Events\Campaign\CampaignChangeEvent;
use Promo\Events\Mixpanel\CampaignAssociateMessageSentEvent;
use Promo\Listeners\CampaignAssociateMessageSentListener;
use Promo\Listeners\GenerateCampaignVersionListener;

/**
 * Class EventServiceProvider
 * @package Promo\Providers
 * @codeCoverageIgnore
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        CampaignChangeEvent::class => [
            GenerateCampaignVersionListener::class
        ],
        CampaignAssociateMessageSentEvent::class => [
            CampaignAssociateMessageSentListener::class
        ]
    ];
}
