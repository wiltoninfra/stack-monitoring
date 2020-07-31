<?php

namespace Promo\Documents\Enums;

use PicPay\Common\Services\Enums\BaseEnum;

/**
 * CampaignDiscardFilterEnum class
 *
 * @package Promo\Documents\Enums
 */
class CampaignDiscardFilterEnum extends BaseEnum
{
    /**
     * Se a campanha não está ativa (expirada ou desativada manualmente)
     */
    const INACTIVE    = 'inactive';

    /**
     * Exigê o primeiro depósito do usuário
     */
    const NEED_FIRST_DEPOSIT = 'need_first_deposit';

    /**
     * Expirada para o consumer, a partir da data de associação/push.
     */
    const EXPIRED    = 'expired';

    /**
     * Esta no limite de transações.
     */
    const MAX_TRANSACTIONS = 'max_transactions';

    /**
     * Limites de campanha (gerais)
     */
    const LIMITS = 'limits';

    /**
     * Expirada para o consumer, a partir da data de associação/push
     */
    const EXPIRED_FOR_CONSUMER = 'expired_for_consumer';

    /**
     * Campanha pede que o cashback seja válido apenas para o primeiro pagamento ao seller
     */
    const FIRST_PAYMENT_TO_SELLER = 'first_payment_to_seller';

    /**
     * Campanha pede que o cashback seja válido apenas para o primeiro pagamento ao seller (retroativo)
     */
    const FIRST_PAYMENT_TO_SELLER_RETROATIVE = 'first_payment_to_seller_retroative';

    /**
     * Mensagem exigida na transação
     */
    const REQUIRED_MESSAGE = 'required_message';

    /**
     * Campanha pede que o cashback seja válido apenas para o primeiro pagamento
     */
    const FIRST_PAYMENT = 'first_payment';

    /**
     * Campanha pede que o cashback seja válido apenas para o primeiro pagamento (retroativo)
     */
    const FIRST_PAYMENT_RETROATIVE = 'first_payment_retroative';
}
