<?php

namespace Promo\Documents\Enums;

use PicPay\Common\Services\Enums\BaseEnum;

/**
 * CampaignTypeEnum class
 * 
 * @package Promo\Documents\Enums
 */
class CampaignTypeEnum extends BaseEnum
{
    /**
     * Campanhas que devolvem dinheiro após uma transação
     */
    const CASHBACK    = 'cashback';

    /**
     * Campanhas que entregam dinheiro no momento da associação
     */
    const INSTANTCASH  = 'instantcash';

    /**
     * Campanhas para crédito para recargas
     */
    const CASHFRONT    = 'cashfront';
}