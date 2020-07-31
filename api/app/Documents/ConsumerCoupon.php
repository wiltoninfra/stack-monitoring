<?php

namespace Promo\Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="consumers_coupons", repositoryClass="Promo\Repositories\ConsumerCouponRepository")
 * @ODM\UniqueIndex(keys={"consumer_id"="asc", "coupon"="asc"})
 * @ODM\Index(keys={"active"="asc"})
 */
class ConsumerCoupon extends BaseDocument
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="integer") */
    protected $consumer_id;

    /** @ODM\ReferenceOne(targetDocument="Promo\Documents\Coupon", storeAs="id") */
    protected $coupon;

    /** @ODM\Field(type="boolean") */
    protected $active;

    public function __construct(int $consumer_id, Coupon $coupon)
    {
        $this->consumer_id = $consumer_id;
        $this->coupon = $coupon;
        $this->active = true;
    }

    /**
     * Obtém o coupon
     *
     * @return Coupon
     */
    public function getCoupon()
    {
        return $this->coupon;
    }

    /**
     * Aponta para um cupom
     *
     * @param Coupon $coupon
     * @return void
     */
    public function setCoupon(Coupon $coupon)
    {
        $this->coupon = $coupon;
    }

    /**
     * Se a relação está ativa ou não
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * Ativa relação
     *
     * @return void
     */
    public function enable()
    {
        $this->active = true;
    }

    /**
     * Desativa relação
     *
     * @return void
     */
    public function disable()
    {
        $this->active = false;
    }

    /**
     * Obtém o consumer_id
     */ 
    public function getConsumerId()
    {
        return $this->consumer_id;
    }
}