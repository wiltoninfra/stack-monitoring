<?php

namespace Promo\Documents;

use MongoDB\BSON\ObjectId;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Promo\Documents\Embeded\NotificationVariantPayload;

/**
 * @ODM\Document(collection="webhook")
 * @ODM\HasLifecycleCallbacks()
 */
class Webhook extends BaseDocument
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="string") */
    protected $campaign_id;

    /** @ODM\ReferenceOne(targetDocument="Promo\Documents\Campaign", storeAs="id") */
    protected $campaign;

    /** @ODM\Field(type="string", nullable=true) */
    protected $coupon_id;

    /** @ODM\ReferenceOne(targetDocument="Promo\Documents\Coupon", storeAs="id") */
    protected $coupon;

    /** @ODM\EmbedMany(targetDocument="Promo\Documents\Embeded\NotificationVariantPayload") */
    protected $variants = array();

    public function __construct()
    {
        $this->variants = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return Webhook
     */
    public function setId(string $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param mixed $campaign
     * @return Webhook
     */
    public function setCampaign(?Campaign $campaign)
    {
        $this->campaign = $campaign;
        return $this;
    }

    /**
     * @param mixed $coupon
     * @return Webhook
     */
    public function setCoupon(?Coupon $coupon)
    {
        $this->coupon = $coupon;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCampaign(): ?Campaign
    {
        return $this->campaign;
    }

    /**
     * @return mixed
     */
    public function getCoupon(): ?Coupon
    {
        return $this->coupon;
    }

    /**
     * @param mixed $coupon_id
     * @deprecated
     * @return Webhook
     */
    public function setCouponId(string $coupon_id)
    {
        $this->coupon_id = $coupon_id;
        return $this;
    }

    /**
     * @deprecated
     * @return string|null
     */
    public function getCouponId(): ?string
    {
        return $this->coupon_id;
    }

    /**
     * @return array
     */
    public function getVariants(): array
    {
        $variants = [];
        foreach ($this->variants as $variant){
            $variant->translator($this->getCampaign());
            $variants[] = $variant;
        }

        return $variants;
    }

    /**
     * @param NotificationVariantPayload $variant
     * @return $this
     */
    public function addVariant(NotificationVariantPayload $variant)
    {
        $this->variants->add($variant);

        return $this;
    }

    /**
     * remover todos os variants do webhook
     */
    public function removeAllVariants()
    {
        $this->variants = new ArrayCollection();
    }

    /**
     * @ODM\PostLoad()
     */
    private function postLoad()
    {
        if ($this->campaign_id !== null)
        {
            $this->campaign = new ObjectId($this->campaign_id);
            $this->campaign_id = null;
        }

        if ($this->coupon_id !== null)
        {
            $this->coupon = new ObjectId($this->coupon_id);
            $this->coupon_id = null;
        }
    }
}