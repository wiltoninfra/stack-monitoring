<?php

namespace Promo\Http\Requests;

use Promo\Documents\Enums\CouponRedirectionType;

/**
 * CouponRequest class
 *
 * @SWG\Definition(
 *      definition="Coupon",
 *      type="object",
 *      @SWG\Property(property="redirection_type", type="string", enum={"webview", "app_screen"}),
 *      @SWG\Property(property="code", type="string", example="UBER20"),
 *      @SWG\Property(property="campaign_id", type="string", example="5be41adc02655b0018142c7f"),
 *      @SWG\Property(property="global", type="boolean", example=false),
 *      @SWG\Property(property="max_associations", type="integer", example=300),
 *      @SWG\Property(property="webview_url", type="string", example="http://cdn.aws.picpay.endereco-grande.com/termos.html"),
 *      @SWG\Property(property="app_screen_path", type="string", example="termos"),
 *      @SWG\Property(property="conditions", ref="#/definitions/ConditionsObject"),
 * )
 *
 * @SWG\Definition(
 *      definition="ConditionsObject",
 *      type="object",
 *      @SWG\Property(property="first_transaction_only", type="boolean", example=false),
 *      @SWG\Property(property="area_codes", type="array", @SWG\Items(type="integer", example=27)),
 * )
 */
class CouponRequest
{
    /**
     * Regras de validação criação de cupons
     *
     * @param bool $isUpdate
     * @return array
     */
    public static function rules(bool $isUpdate = false)
    {
        $rules = [
            'redirection_type'                    => 'required|in:' . CouponRedirectionType::getFieldsListToCsv(),
            'global'                              => 'required|boolean',
            'max_associations'                    => 'nullable|integer',
            'app_screen_path'                     => 'required_if:redirection_type,' . CouponRedirectionType::APP_SCREEN . '|string',
            'webview_url'                         => 'required_if:redirection_type,' . CouponRedirectionType::WEBVIEW . '|string',

            'conditions.first_transaction_only'   => 'required_if:global,1|boolean',
            'conditions.area_codes'               => 'array',
            'conditions.area_codes.*'             => 'integer',
            'campaign_id'                         => 'required_if:redirection_type,' . CouponRedirectionType::WEBVIEW . '|string',
        ];

        if (!$isUpdate) {
            $rules['code']                      = 'required|min:7|alpha_num';
        }

        return $rules;
    }
}
