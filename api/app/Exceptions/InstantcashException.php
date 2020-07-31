<?php

namespace Promo\Exceptions;
use PicPay\Common\Exceptions\PicPayException;

class InstantcashException extends PicPayException {
    public function __construct(string $message = "Problema ao aplicar dinheiro a carteira do usuário.", int $code = 409) {
        parent::__construct($message, $code);
    }
}