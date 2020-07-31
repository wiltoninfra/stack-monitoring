<?php

namespace Promo\Documents;

use Carbon\Carbon;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="campaigns_versions", repositoryClass="Promo\Repositories\CampaignVersionRepository")
 * @ODM\HasLifecycleCallbacks
 */
class CampaignVersion extends BaseDocument
{

    /** @ODM\Id */
    private $id;

    /**
     * @ODM\Field(type="date")
     * @ODM\Index
     */
    private $permanenceStartDate;

    /**
     * @ODM\Field(type="date")
     */
    private $permanenceEndDate;

    /** @ODM\Field(type="raw") */
    private $campaign;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getPermanenceStartDate(): \DateTime
    {
        return $this->permanenceStartDate;
    }

    /**
     * @param $permanenceStartDate
     * @return $this
     */
    public function setPermanenceStartDate($permanenceStartDate): self
    {
        $this->permanenceStartDate = $permanenceStartDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getPermanenceEndDate(): ?\DateTime
    {
        return $this->permanenceEndDate;
    }

    /**
     * @param $permanenceStartDate
     * @return $this
     */
    public function setPermanenceEndDate($permanenceStartDate): self
    {
        $this->permanenceEndDate = $permanenceStartDate;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * @param $campaign
     * @return $this
     */
    public function setCampaign($campaign): self
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function fill(array $data)
    {
        $this->setPermanenceEndDate($data['permanenceEndDate'] ?? null);
        $this->setPermanenceStartDate($data['permanenceStartDate'] ?? Carbon::now());
        $this->setCampaign($data['campaign'] ?? null);
        return $this;
    }

}
