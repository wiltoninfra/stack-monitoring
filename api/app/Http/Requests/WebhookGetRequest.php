<?php

namespace Promo\Http\Requests;

/**
 * WebhookGetRequest class
 */
class WebhookGetRequest
{
    /**
     * Regras de validação para obter lista
     *
     * @return array
     */
    public static function rules()
    {
        return [
            'campaign_id'   => 'this_or_that:coupon_id|string',
            'coupon_id'     => 'this_or_that:campaign_id|string',
        ];
    }
}
