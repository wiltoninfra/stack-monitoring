<?php

namespace Promo\Http\Requests;

use Promo\Documents\Enums\TransactionTypeEnum;

/**
 * CashbackRequest class
 * 
 * @SWG\Definition(
 *      definition="TransactionCorePayload",
 *      @SWG\Property(property="transaction", ref="#/definitions/TransactionCorePayloadDetails"),
 * )
 * 
 * @SWG\Definition(
 *      definition="TransactionCorePayloadDetails",
 *      type="object",
 *      @SWG\Property(property="consumer_id", type="integer", example="17"),
 *      @SWG\Property(property="type", type="string", example="p2p"),
 *      @SWG\Property(property="id", type="integer", example="34346"),
 *      @SWG\Property(property="message", type="string", example="#VaiBrasil"),
 *      @SWG\Property(property="credit_card", type="number", example=30.50),
 *      @SWG\Property(property="wallet", type="number", example=10.12),
 *      @SWG\Property(property="total", type="number", example=40.62),
 *      @SWG\Property(property="seller_id", type="integer", example=356),
 *      @SWG\Property(property="seller_type", type="string", example="membership"),
 *      @SWG\Property(property="credit_card_brand", type="string", example="picpay"),
 *      @SWG\Property(property="bill_id", type="integer", example=7465745),
 *      @SWG\Property(property="first_payment_to_seller", type="boolean", example=true),
 *      @SWG\Property(property="first_payee_received_payment", type="boolean", example=true),
 *      @SWG\Property(property="external_merchant", ref="#/definitions/ExternalMerchantCorePayload"),
 *      @SWG\Property(property="consumer_id_payee", type="integer", example=17),
 *      @SWG\Property(property="transaction_date", type="date", example="2020-02-07 22:00:00"),
 *      @SWG\Property(property="first_payment", type="boolean", example=true),
 *      @SWG\Property(property="installments", type="integer", example=3),

 * )
 * 
 * @SWG\Definition(
 *      definition="ExternalMerchantCorePayload",
 *      type="object",
 *      @SWG\Property(property="type", type="string", example="cielo"),
 *      @SWG\Property(property="id", type="string", example="3436567946"),
 * )
 */
class CashbackRequest
{
    /**
     * Regras de validação para atualização de status de campanha
     *
     * @return array
     */
    public static function rules()
    {
        return [
            'transaction.consumer_id'                  => 'required|integer',
            'transaction.type'                         => 'required|in:' . TransactionTypeEnum::getFieldsListToCsv(),
            'transaction.id'                           => 'required|integer',
            'transaction.message'                      => 'string',
            'transaction.credit_card'                  => 'required|numeric',
            'transaction.credit_card_brand'            => 'string',
            'transaction.wallet'                       => 'required|numeric',
            'transaction.total'                        => 'required|numeric',
            'transaction.seller_id'                    => 'required_if:transaction.type,' . TransactionTypeEnum::PAV .'|integer',
            'transaction.seller_type'                  => 'required_if:transaction.type,' . TransactionTypeEnum::PAV .'|string',
            'transaction.first_payment_to_seller'      => 'boolean',
            'transaction.bill_id'                      => 'numeric',
            'transaction.external_merchant.type'       => 'nullable|string',
            'transaction.external_merchant.id'         => 'nullable|string',
            'transaction.first_payee_received_payment' => 'boolean',
            'transaction.consumer_id_payee'            => 'required_if:transaction.type,' . TransactionTypeEnum::P2P .'|integer',  // consumer
            'transaction.transaction_date'             => 'date',
            'transaction.first_payment'                => 'boolean',
            'transaction.installments'                 => 'integer'
        ];
    }
}
