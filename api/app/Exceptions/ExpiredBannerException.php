<?php

namespace Promo\Exceptions;

use PicPay\Common\Exceptions\PicPayException;

class ExpiredBannerException extends PicPayException {
    public function __construct(string $message = "O banner está expirado.", int $code = 409) {
        parent::__construct($message, $code);
    }
}