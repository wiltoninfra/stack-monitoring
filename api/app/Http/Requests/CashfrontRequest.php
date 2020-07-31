<?php

namespace Promo\Http\Requests;

/**
 * CashfrontRequest class
 * 
 * @SWG\Definition(
 *      definition="DepositCorePayload",
 *      @SWG\Property(property="deposit", ref="#/definitions/DepositCorePayloadDetails"),
 * )
 * 
 * @SWG\Definition(
 *      definition="DepositCorePayloadDetails",
 *      type="object",
 *      @SWG\Property(property="id", type="integer", example="34346"),
 *      @SWG\Property(property="recharge_method", type="string", example="conta-corrente"),
 *      @SWG\Property(property="total", type="number", example=40.62),
 *      @SWG\Property(property="first_deposit", type="boolean", example=true),
 * )
 */
class CashfrontRequest
{
    /**
     * Regras de validação para requisição de cashfront
     *
     * @return array
     */
    public static function rules()
    {
        return [
            'deposit.id'               => 'required|string',
            'deposit.recharge_method'  => 'required|string',
            'deposit.total'            => 'required|numeric',
            'deposit.first_deposit'    => 'boolean',
        ];
    }
}