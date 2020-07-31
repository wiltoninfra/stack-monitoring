<?php

namespace Promo\Exceptions;
use PicPay\Common\Exceptions\PicPayException;

class GlobalCampaignException extends PicPayException {
    public function __construct(string $message = "Uma campanha global não pode ser atrelada a usuários, por valer para todos.", int $code = 409) {
        parent::__construct($message, $code);
    }
}