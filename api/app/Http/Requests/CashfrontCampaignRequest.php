<?php

namespace Promo\Http\Requests;

use Promo\Documents\Enums\CampaignTypeEnum;

class CashfrontCampaignRequest
{
    /**
     * Regras de validação para criação e atualização de campanha de cashfront
     *
     * @return array
     */
    public static function rules()
    {
        return [
            'name'                                              => 'required|string',
            'description'                                       => 'required|string',
            'type'                                              => 'required|in:' . CampaignTypeEnum::CASHFRONT,
            'global'                                            => 'required|boolean',
            'communication'                                     => 'boolean',
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

            'cashfront.percentage'                              => 'required|numeric',
            'cashfront.max_value'                               => 'required|numeric',

            'deposit.min_transaction_value'                     => 'nullable|numeric',
            'deposit.max_transactions'                          => 'nullable|integer',
            'deposit.max_transactions_per_consumer'             => 'nullable|integer',
            'deposit.max_transactions_per_consumer_per_day'     => 'nullable|integer',
            'deposit.first_deposit_only'                        => 'required|boolean'

        ];
    }
}