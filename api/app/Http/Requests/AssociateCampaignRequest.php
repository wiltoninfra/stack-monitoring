<?php

namespace Promo\Http\Requests;

/**
 * AssociateCampaignRequest class
 * 
 * @SWG\Definition(
 *      definition="AssociateCampaign",
 *      type="object",
 *      @SWG\Property(property="consumers", type="array", @SWG\Items(type="integer", example=3)),
 * )
 */
class AssociateCampaignRequest
{
    /**
     * Regras de validação para associação de campanha
     *
     * @return array
     */
    public static function rules()
    {
        return [
            'consumers'   => 'required|array|max:50',
            'consumers.*' => 'integer|integer_type',
        ];
    }
}