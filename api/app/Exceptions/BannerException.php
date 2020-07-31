<?php

namespace Promo\Exceptions;

use PicPay\Common\Exceptions\PicPayException;

class BannerException extends PicPayException {
    public function __construct(string $message = "Exceção ao processar o banner. Tente novamente.", int $code = 409) {
        parent::__construct($message, $code);
    }
}