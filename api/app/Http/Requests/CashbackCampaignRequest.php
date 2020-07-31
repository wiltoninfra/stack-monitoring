<?php

namespace Promo\Http\Requests;

use Promo\Documents\Enums\PaidByEnum;
use Promo\Documents\Enums\CampaignTypeEnum;
use Promo\Documents\Enums\TransactionConditionEnum;
use Promo\Documents\Enums\TransactionTypeEnum;

class CashbackCampaignRequest
{
    /**
     * Regras de validação para criação e atualização de campanha de cashback
     *
     * @return array
     */
    public static function rules()
    {
        return [
            'name'                                              => 'required|string',
            'description'                                       => 'required|string',
            'type'                                              => 'required|in:' . CampaignTypeEnum::CASHBACK,
            'global'                                            => 'required|boolean',
            'communication'                                     => 'boolean',
            'consumers'                                         => 'nullable|array',
            'consumers.*'                                       => 'nullable|integer',
            'sellers'                                           => 'nullable|array',
            'sellers.*'                                         => 'nullable|integer',
            'except_sellers'                                    => 'nullable|array',
            'except_sellers.*'                                  => 'nullable|integer',
            'sellers_types'                                     => 'nullable|array',
            'sellers_types.*'                                   => 'nullable|string',
            'webhook_url'                                       => 'nullable|url',
            'webview_url'                                       => 'nullable|url',
            'tags'                                              => 'nullable|array',
            'tags.*'                                            => 'string',

            'duration.fixed'                                    => 'required|boolean',
            'duration.start_date'                               => 'date',
            'duration.end_date'                                 => 'date',
            'duration.hours'                                    => 'integer',
            'duration.days'                                     => 'integer',
            'duration.weeks'                                    => 'integer',
            'duration.months'                                   => 'integer',


            'cashback.percentage'                               => 'required|numeric',
            'cashback.max_value'                                => 'required|numeric',
            'cashback.paid_by'                                  => 'required|in:' . PaidByEnum::getFieldsListToCsv(),

            'external_merchant.type'                            => 'string',
            'external_merchant.ids'                             => 'array',
            'external_merchant.ids.*'                           => 'string',

            'transaction.type'                                  => 'required|in:' . TransactionTypeEnum::getFieldsListToCsv(),
            'transaction.payment_methods'                       => 'required|array|min:1',
            'transaction.payment_methods.*'                     => 'string',
            'transaction.min_transaction_value'                 => 'nullable|numeric',
            'transaction.max_transactions'                      => 'nullable|integer',
            'transaction.max_transactions_per_consumer'         => 'nullable|integer',
            'transaction.max_transactions_per_consumer_per_day' => 'nullable|integer',
            'transaction.first_payment'                         => 'nullable|boolean',
            'transaction.first_payment_to_seller'               => 'nullable|boolean',
            'transaction.first_payee_received_payment_only'     => 'nullable|boolean',
            'transaction.required_message'                      => 'nullable|string',
            'transaction.credit_card_brands'                    => 'nullable|array',
            'transaction.credit_card_brands.*'                  => 'nullable|string',
            'transaction.min_installments'                      => 'required|integer|min:1',
            'transaction.conditions'                            => 'installments_conditions|array',

            'limits.uses_per_consumer'                          => 'nullable|array',
            'limits.uses_per_consumer.type'                     => 'string|in:sum,count',
            'limits.uses_per_consumer.uses'                     => 'numeric',

            'limits.uses_per_consumer_per_period.type'          => 'string|in:sum,count',
            'limits.uses_per_consumer_per_period.period'        => 'string|in:day,week,month',
            'limits.uses_per_consumer_per_period.uses'          => 'numeric',
        ];
    }
}
