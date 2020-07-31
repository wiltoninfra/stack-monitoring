<?php

namespace Promo\Http\Requests;

/**
 *
 * @SWG\Definition(
 *     definition="BannerUpdate",
 *     type="object",
 *     @SWG\Property(property="name", type="string", example="Store 20% de amor"),
 *     @SWG\Property(property="start_date", type="string", example="2018-09-28T19:05:47.904Z"),
 *     @SWG\Property(property="end_date", type="string", example="2027-09-30T19:05:47.904Z"),
 *     @SWG\Property(property="campaign_id", type="string", example="5c18f2966f569300803f6573"),
 *     @SWG\Property(property="target", ref="#/definitions/TargetInfo"),
 *     @SWG\Property(property="ios_min_version", type="string", example="10.3"),
 *     @SWG\Property(property="android_min_version", type="string", example="10.3"),
 *     @SWG\Property(property="conditions", ref="#/definitions/BannerConditions"),
 *     @SWG\Property(property="info", ref="#/definitions/BannerInfo"),
 * )
 */
class BannerUpdateRequest
{
    public static function rules()
    {
        return [
            'name'                    => 'string',
            'start_date'              => 'date',
            'end_date'                => 'date',
            'campaign_id'             => 'string',
            'deeplink'                => 'string',
            'webview'                 => 'string',
            'ios_min_version'         => 'string',
            'android_min_version'     => 'string',
            'info.title'              => 'string',
            'info.description'        => 'string',
            'conditions.area_codes'   => 'array',
            'conditions.area_codes.*' => 'integer',
            'conditions.excluded_campaigns'  => 'array',
            'conditions.excluded_campaigns.*' => 'integer'
        ];
    }
}
