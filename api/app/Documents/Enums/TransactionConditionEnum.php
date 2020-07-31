<?php


namespace Promo\Documents\Enums;

use PicPay\Common\Services\Enums\BaseEnum;

/**
 * TransactionConditionEnum class
 *
 * @package Promo\Documents\Enums
 */
class TransactionConditionEnum extends BaseEnum
{

    /**
     * Condicao de pagamento a vista
     */
    const IN_CASH = 'in_cash';

    /**
     * Condicao de pagamento parcelado
     */
    const INSTALLMENTS = 'installments';
}
