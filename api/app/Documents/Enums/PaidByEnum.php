<?php

namespace Promo\Documents\Enums;

use PicPay\Common\Services\Enums\BaseEnum;

/**
 * PaidByEnum class
 * 
 * @package Promo\Documents\Enums
 */
class PaidByEnum extends BaseEnum
{
    /**
     * Valor do cashback arcado pelo PicPay
     */
    const PICPAY = 'picpay';

    /**
     * Algum seller especificado arca com o valor
     */
    const SELLER = 'seller';
}