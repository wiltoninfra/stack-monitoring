<?php

namespace Promo\Repositories;

use Promo\Documents\Coupon;
use Promo\Documents\ConsumerCoupon;
use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * ConsumerCouponRepository class
 */
class ConsumerCouponRepository extends DocumentRepository
{
    public function getOne(int $consumer_id, Coupon $coupon): ?ConsumerCoupon
    {
        $cc = $this->findOneBy([
            'consumer_id' => $consumer_id,
            'coupon' => $coupon
        ]);

        return $cc;
    }
}