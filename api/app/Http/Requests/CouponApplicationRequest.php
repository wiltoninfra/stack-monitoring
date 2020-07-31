<?php

namespace Promo\Http\Requests;

/**
 * CouponRequest class
 *
 * @SWG\Definition(
 *      definition="CouponApplicationPayload",
 *      @SWG\Property(property="coupon", ref="#/definitions/CouponApplicationPayloadDetails"),
 * )
 * 
 * @SWG\Definition(
 *      definition="CouponApplicationPayloadDetails",
 *      @SWG\Property(property="code", type="string", example="UBER20"),
 *      @SWG\Property(property="conditions", ref="#/definitions/CouponApplicationPayloadConditions"),
 * )
 *
 * @SWG\Definition(
 *      definition="CouponApplicationPayloadConditions",
 *      @SWG\Property(property="first_transaction", type="boolean", example=true),
 *      @SWG\Property(property="area_code", type="integer", example=27),
 * )
 */
class CouponApplicationRequest
{
    /**
     * Regras de validação criação de cupons
     *
     * @return array
     */
    public static function rules()
    {
        return [
            'coupon.code'                           => 'required|string',
            'coupon.conditions.first_transaction'   => 'required|boolean',
            'coupon.conditions.area_code'           => 'required|integer',
        ];
    }
}