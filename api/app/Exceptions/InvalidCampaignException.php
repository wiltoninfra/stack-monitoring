<?php

namespace Promo\Exceptions;
use PicPay\Common\Exceptions\PicPayException;

class InvalidCampaignException extends PicPayException {
    public function __construct(string $message = "A campanha informada é inválida.", int $code = 409) {
        parent::__construct($message, $code);
    }
}