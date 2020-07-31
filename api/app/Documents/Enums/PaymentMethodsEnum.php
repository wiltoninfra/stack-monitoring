<?php

namespace Promo\Documents\Enums;

use PicPay\Common\Services\Enums\BaseEnum;

class PaymentMethodsEnum extends BaseEnum
{
    const WALLET = 'wallet';

    const CREDIT_CARD = 'credit-card';

    // Constantes para comparação de método exclusivo de pagamento
    const CREDIT_CARD_ONLY = [self::CREDIT_CARD];

    const WALLET_ONLY = [self::WALLET];

    const ALL = [self::CREDIT_CARD, self::WALLET];
}