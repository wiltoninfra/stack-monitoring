<?php


namespace Promo\Events\Campaign;

use Promo\Events\Event;

/**
 * Class CampaignChangeEvent
 * @package Promo\Events\Campaign
 */
class CampaignChangeEvent extends Event
{

    /**
     * @var string
     */
    private $campaignId;

    public function __construct($campaignId)
    {
        $this->campaignId = $campaignId;
    }

    /**
     * @return mixed
     */
    public function campaignId()
    {
        return $this->campaignId;
    }

}
