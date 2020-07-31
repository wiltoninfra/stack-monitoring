<?php

namespace Promo\Exceptions;
use PicPay\Common\Exceptions\PicPayException;

class ValidationException extends PicPayException {
    public function __construct(string $message = "Verifique os dados inputados.", int $code = 422) {
        parent::__construct($message, $code);
    }
}