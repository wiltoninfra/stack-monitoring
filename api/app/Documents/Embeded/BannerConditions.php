<?php

namespace Promo\Documents\Embeded;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class BannerConditions
{
    /**
     * @ODM\Field(type="collection")
     * @ODM\Index
     */
    protected $area_codes;

    /**
     * @ODM\Field(type="collection")
     * @ODM\Index
     */
    protected $excluded_campaigns;

    /**
     * Seta os códigos de área (DDD) que o banner
     * atingirá
     *
     * @param array|null $codes
     * @return $this
     */
    public function setAreaCodes(?array $codes)
    {
        $this->area_codes = $codes;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAreaCodes()
    {
        return $this->area_codes;
    }

    /**
     * Seta os ids das campanhas que não exibirão o banner
     *
     * @param array|null $campaigns
     * @return $this
     */
    public function setExcludedCampaigns(?array $campaigns)
    {
        $this->excluded_campaigns = $campaigns;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getExcludedCampaigns()
    {
        return $this->excluded_campaigns;
    }
}