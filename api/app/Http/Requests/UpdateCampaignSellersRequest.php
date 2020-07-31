<?php

namespace Promo\Http\Requests;

/**
 * UpdateCampaignSellersRequest class
 * 
 * @SWG\Definition(
 *      definition="UpdateCampaignSellers",
 *      type="object",
 *      @SWG\Property(property="sellers", type="array", @SWG\Items(type="integer", example=3)),
 * )
 */
class UpdateCampaignSellersRequest
{
    /**
     * Regras de validação para atualização de sellers da campanha
     *
     * @return array
     */
    public static function rules()
    {
        return [
            'sellers'   => 'required|array',
        ];
    }
}
