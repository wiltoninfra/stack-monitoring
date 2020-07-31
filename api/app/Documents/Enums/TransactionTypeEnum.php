<?php

namespace Promo\Documents\Enums;

use PicPay\Common\Services\Enums\BaseEnum;

/**
 * TransactionTypeEnum class
 * 
 * @package Promo\Documents\Enums
 */
class TransactionTypeEnum extends BaseEnum
{
    /**
     * Campanhas para transações entre usuários
     */
    const P2P       = 'p2p';

    /**
     * Campanhas para transações do tipo PAV, para qualquer seller
     */
    const PAV       = 'pav';

    /**
     * Para transações PAV e P2P
     */
    const MIXED       = 'mixed';
}