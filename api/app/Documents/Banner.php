<?php

namespace Promo\Documents;

use Carbon\Carbon;
use Promo\Documents\Embeded\BannerConditions;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="banners", repositoryClass="Promo\Repositories\BannerRepository")
 * @ODM\Index(keys={"active"="asc", "campaign"="desc", "priority"="desc"})
 */
class Banner extends BaseDocument
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="string") */
    protected $name;

    /** @ODM\Field(type="boolean") */
    protected $active;

    /** @ODM\Field(type="boolean") */
    protected $global;

    /** @ODM\Field(type="string") */
    protected $image_url;

    /** @ODM\Field(type="string") */
    protected $target;

    /** @ODM\Field(type="string") */
    protected $target_param;

    /** @ODM\Field(type="integer") */
    protected $priority;

    /** @ODM\Field(type="date") */
    protected $start_date;

    /** @ODM\Field(type="date") */
    protected $end_date;

    /** @ODM\Field(type="string") */
    protected $info_title;

    /** @ODM\Field(type="string") */
    protected $info_description;

    /** @ODM\Field(type="string") */
    protected $ios_min_version;

    /** @ODM\Field(type="string") */
    protected $android_min_version;

    /** @ODM\ReferenceOne(targetDocument="Promo\Documents\Campaign", storeAs="id") */
    protected $campaign;

    /** @ODM\EmbedOne(targetDocument="Promo\Documents\Embeded\BannerConditions") */
    protected $conditions;

    public function __construct(string $image_url, bool $global)
    {
        $this->active = false;
        $this->global = $global;
        $this->image_url = $image_url;
        $this->priority = 0;

        if ($global === true)
        {
            $this->conditions = new BannerConditions();
        }
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return Banner
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return bool
     */
    public function isGlobal()
    {
        return $this->global;
    }

    /**
     * Retorna se está ativo e desativa e caso de expirado
     *
     * @return bool
     */
    public function isActive(): bool
    {
        $result = ($this->isExpired() === false) && $this->active;

        $this->active = $result;

        return $result;
    }

    /**
     * Verifica se banner já expirou
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        if ($this->start_date !== null && $this->end_date !== null)
        {
            $now = Carbon::now();

            return ($this->end_date < $now);
        }

        return false;
    }

    /**
     * @param mixed $active
     * @return Banner
     */
    public function setActive(bool $active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return Banner
     */
    public function enable()
    {
        $this->active = true;

        return $this;
    }

    /**
     * @return Banner
     */
    public function disable()
    {
        $this->active = false;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getImageUrl(): ?string
    {
        return $this->image_url;
    }

    /**
     * @param mixed $image_url
     * @return Banner
     */
    public function setImageUrl(?string $image_url)
    {
        $this->image_url = $image_url;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param mixed $priority
     * @return Banner
     */
    public function setPriority(?int $priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @param null|string $start_date
     * @return $this
     */
    public function setStartDate(?string $start_date)
    {
        if ($start_date !== null)
        {
            $this->start_date = Carbon::parse($start_date);
        }
        else
        {
            $this->start_date = null;
        }

        return $this;
    }

    /**
     * @param null|string $end_date
     * @return $this
     */
    public function setEndDate(?string $end_date)
    {
        if ($end_date !== null)
        {
            $this->end_date = Carbon::parse($end_date);
        }
        else
        {
            $this->end_date = null;
        }

        return $this;
    }

    /**
     * Obtém o valor de start_date
     */
    public function getStartDate(): ?\DateTime
    {
        return $this->start_date;
    }

    /**
     * Obtém o valor de end_date
     */
    public function getEndDate(): ?\DateTime
    {
        return $this->end_date;
    }

    /**
     * @return mixed
     */
    public function getInfoTitle(): ?string
    {
        return $this->info_title;
    }

    /**
     * @param mixed $info_title
     * @return Banner
     */
    public function setInfoTitle(?string $info_title)
    {
        $this->info_title = $info_title;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getInfoDescription(): ?string
    {
        return $this->info_description;
    }

    /**
     * @param mixed $info_description
     * @return Banner
     */
    public function setInfoDescription(?string $info_description)
    {
        $this->info_description = $info_description;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getConditions(): ?BannerConditions
    {
        return $this->conditions;
    }

    /**
     * @return mixed
     */
    public function getCampaign(): ?Campaign
    {
        return $this->campaign;
    }

    /**
     * @param mixed $campaign
     * @return Banner
     */
    public function setCampaign(?Campaign $campaign)
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIosMinVersion()
    {
        return $this->ios_min_version;
    }

    /**
     * @param mixed $ios_min_version
     * @return Banner
     */
    public function setIosMinVersion(?string $ios_min_version)
    {
        $this->ios_min_version = $ios_min_version;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAndroidMinVersion()
    {
        return $this->android_min_version;
    }

    /**
     * @param mixed $android_min_version
     * @return Banner
     */
    public function setAndroidMinVersion(?string $android_min_version)
    {
        $this->android_min_version = $android_min_version;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTarget(): ?string
    {
        return $this->target;
    }

    /**
     * @param mixed $target
     * @return Banner
     */
    public function setTarget(?string $target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTargetParam(): ?string
    {
        return $this->target_param;
    }

    /**
     * @param mixed $target_param
     * @return Banner
     */
    public function setTargetParam(?string $target_param)
    {
        $this->target_param = $target_param;

        return $this;
    }
}