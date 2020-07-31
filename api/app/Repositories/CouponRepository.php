<?php

namespace Promo\Repositories;

use Promo\Documents\Coupon;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Promo\Exceptions\InvalidCouponException;

/**
 * CouponRepository class
 */
class CouponRepository extends DocumentRepository
{
    const LIMIT_ONE = 1;

    /**
     * @param string $code
     * @return Coupon
     * @throws InvalidCouponException
     */
    public function getOne(string $code): Coupon
    {
        $coupon = $this->findBy(['code' => strtoupper($code), 'active' => true], ['created_at' => 'desc'], self::LIMIT_ONE);

        if (count($coupon) < 1 || $coupon[0] === null)
        {
            throw new InvalidCouponException('Cupom inexistente.', 404);
        }

        return $coupon[0];
    }
}