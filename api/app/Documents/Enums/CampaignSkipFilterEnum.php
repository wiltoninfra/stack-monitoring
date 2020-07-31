<?php

namespace Promo\Documents\Enums;

use PicPay\Common\Services\Enums\BaseEnum;

/**
 * CampaignSkipFilterEnum class
 *
 * @package Promo\Documents\Enums
 */
class CampaignSkipFilterEnum extends BaseEnum
{
    /**
     * Esta no limite de transações.
     */
    const MAX_TRANSACTIONS = 'max_transactions';

    /**
     * Consumer já passou do limite de transações para um dia.
     */
    const MAX_TRANSACTIONS_PER_DAY = 'max_transactions_per_day';

    /**
     * Pagamento somente com cartao.
     */
    const PAYMENT_ONLY_CARD = 'payment_only_card';

    /**
     * Se a campanha é para pagamento somente saldo
     */
    const WALLET_BALANCE_ONLY = 'wallet_balance_only';

    /**
     * Somente para uma bandeira específica de cartão
     */
    const CREDIT_CARD_BRAND_SPECIFIC = 'credit_card_brand_specific';

    /**
     * Limites de campanha por período
     */
    const CAMPAIGN_LIMITS_BY_PERIOD = 'campaign_limits_by_period';

    /**
     * Campanha exige que pagamento seja para alguém que nunca recebeu transação alguma
     */
    const PAYEE_RECEIVED_PAYMENT = 'payee_received_payment';

    /**
     * Restrição de external merchant (Cielo etc), mas a transação não combina
     */
    const RESTRICTED_CAMPAIGN = 'restricted_campaign';

    /**
     * Boleto é autofinanciado e a campanha não permite esse tipo de boleto
     */
    const SELF_FINANCED = 'self_financed';

    /**
     * Se o valor da transação atingiu não o limite mínimo requerido pela campanha
     */
    const MIN_TRANSACTION_VALUE = 'min_transaction_value';

    /**
     * Impedir que consumers fiquem gerando cashback entre si
     */
    const CASHBACK_FARM = 'cashback_farm';

    /**
     * Se o número mínimo de parcelas não for atingido
     */
    const MIN_INSTALLMENTS = 'min_installments';

    /**
     * Se achar um erro que nao atenda alguma das regras de skip
     */
    const MIN_INSTALLMENTS_AND_IN_CASH = 'min_installments_and_in_cash';

    /**
     * Se pagamentos a vista tem parcelas maior que 1
     */
    const IN_CASH_WITH_INSTALLMENTS = 'in_cash_with_installments';

    /**
     * Se o recharge_method do deposito for diferente da campanha
     */
    const INVALID_RECHARGE_METHOD = 'invalid_recharge_method';
}
