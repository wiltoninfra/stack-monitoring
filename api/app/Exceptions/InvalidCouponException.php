<?php

namespace Promo\Exceptions;
use PicPay\Common\Exceptions\PicPayException;

class InvalidCouponException extends PicPayException {
    public function __construct(string $message = "O cupom promocional não é válido.", int $code = 409) {
        parent::__construct($message, $code);
    }
}