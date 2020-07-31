<?php

namespace Promo\Http\Requests;

use Promo\Documents\Enums\CampaignTypeEnum;

class InstantCampaignRequest
{
    /**
     * Regras de validação para criação e atualização de campanha instantcashß
     *
     * @return array
     */
    public static function rules()
    {
        return [
            'name'                   => 'required|string',
            'description'            => 'required|string',
            'type'                   => 'required|in:' . CampaignTypeEnum::INSTANTCASH,
            'webhook_url'            => 'nullable|url',
            'webview_url'            => 'nullable|url',
            'tags'                   => 'nullable|array',
            'tags.*'                 => 'string',

            'duration.start_date'    => 'date',
            'duration.end_date'      => 'date',

            'instantcash.value'      => 'required|numeric',
        ];
    }
}