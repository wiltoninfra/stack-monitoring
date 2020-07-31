<?php

namespace Promo\Events\Mixpanel;
use Promo\Events\Event;
use Promo\Helpers\StringHelper;

/**
 * Class CampaignAssociateMessageSentEvent
 * @package Promo\Events\Mixpanel
 */
class CampaignAssociateMessageSentEvent extends Event
{
    /**
     * @var int
     */
    private $distinct_id;
    /**
     * @var string
     */
    private $name_variant;
    /**
     * @var string
     */
    private $variant_received;
    /**
     * @var string
     */
    private $campaign_id;
    /**
     * @var string
     */
    private $campaign_name;

    /**
     * CampaignAssociateMessageSentEvent constructor.
     * @param int $distinct_id
     * @param string $name_variant
     * @param string $campaign_id
     * @param string $campaign_name
     */
    public function __construct
    (
        int $distinct_id,
        string $name_variant,
        string $campaign_id,
        string $campaign_name
    )
    {
        $this->distinct_id = $distinct_id;
        $this->name_variant = $name_variant;
        $this->variant_received = StringHelper::getStringBetween($name_variant, '[', ']');
        $this->campaign_id = $campaign_id;
        $this->campaign_name = $campaign_name;
    }

    /**
     * @return int
     */
    public function getDistinctId(): int
    {
        return $this->distinct_id;
    }

    /**
     * @return string
     */
    public function getNameVariant(): string
    {
        return $this->name_variant;
    }

    /**
     * @return string
     */
    public function getVariantReceived(): string
    {
        return $this->variant_received;
    }

    /**
     * @return string
     */
    public function getCampaignId(): string
    {
        return $this->campaign_id;
    }

    /**
     * @return string
     */
    public function getCampaignName(): string
    {
        return $this->campaign_name;
    }

}
