<?php

namespace Promo\Http\Requests;

/**
 *
 * @SWG\Definition(
 *     definition="Banner",
 *     type="object",
 *     @SWG\Property(property="image", type="string", example="data:image/png;base64,iVBORw0KGgoAAAAN..."),
 *     @SWG\Property(property="name", type="string", example="Store 20% de amor"),
 *     @SWG\Property(property="global", type="boolean", example=true),
 *     @SWG\Property(property="start_date", type="string", example="2018-09-28T19:05:47.904Z"),
 *     @SWG\Property(property="end_date", type="string", example="2027-09-30T19:05:47.904Z"),
 *     @SWG\Property(property="campaign_id", type="string", example="5c18f2966f569300803f6573"),
 *     @SWG\Property(property="target", ref="#/definitions/TargetInfo"),
 *     @SWG\Property(property="ios_min_version", type="string", example="10.3"),
 *     @SWG\Property(property="android_min_version", type="string", example="10.3"),
 *     @SWG\Property(property="conditions", ref="#/definitions/BannerConditions"),
 *     @SWG\Property(property="info", ref="#/definitions/BannerInfo"),
 * ),
 *
 * @SWG\Definition(
 *     definition="BannerInfo",
 *     type="object",
 *     @SWG\Property(property="title", type="string", example="Título legal"),
 *     @SWG\Property(property="description", type="string", example="Informação mais legal ainda"),
 * )
 *
 * @SWG\Definition(
 *     definition="BannerConditions",
 *     type="object",
 *     @SWG\Property(property="area_codes", type="array", @SWG\Items(type="integer", example=27)),
 * )
 *
 * @SWG\Definition(
 *     definition="TargetInfo",
 *     type="object",
 *     @SWG\Property(property="name", type="string", example="financial_service"),
 *     @SWG\Property(property="param", type="string", example="boleto")
 * )
 *
 *  * @SWG\Definition(
 *     definition="TargetConditions",
 *     type="object",
 *     @SWG\Property(property="excluded_campaigns", type="array", @SWG\Items(type="string", example="5e53d1d011ec5d00470bd852")),
 * )
 */
class BannerRequest
{
    public static function rules()
    {
        return [
            'image'                   => 'required|string',
            'name'                    => 'required|string',
            'global'                  => 'required|boolean',
            'start_date'              => 'date',
            'end_date'                => 'date',
            'campaign_id'             => 'string',
            'target.name'             => 'required|string',
            'target.param'            => 'required|string',
            'info.description'        => 'string',
            'info.title'              => 'string',
            'ios_min_version'         => 'string',
            'android_min_version'     => 'string',
            'conditions.area_codes'   => 'array',
            'conditions.area_codes.*' => 'integer',
            'conditions.excluded_campaigns' => 'array',
            'conditions.excluded_campaigns.*' => 'string'
        ];
    }
}
