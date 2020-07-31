<?php

namespace Promo\Exceptions;
use PicPay\Common\Exceptions\PicPayException;

class ExpiredCampaignException extends PicPayException {
    public function __construct(string $message = "A campanha está expirada. Verifique e atualize as condições dessa campanha.", int $code = 409) {
        parent::__construct($message, $code);
    }
}