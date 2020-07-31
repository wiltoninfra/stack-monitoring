<?php

namespace Promo\Http\Requests;

use Promo\Documents\Enums\PaidByEnum;
use Promo\Documents\Enums\CampaignTypeEnum;

/**
 * UpdateCampaignStatusRequest class
 * 
 * @SWG\Definition(
 *      definition="UpdateCampaignStatus",
 *      type="object",
 *      @SWG\Property(property="active", type="boolean", example=true),
 * )
 */
class UpdateCampaignStatusRequest
{
    /**
     * Regras de validação para atualização de status de campanha
     *
     * @return array
     */
    public static function rules()
    {
        return [
            'active' => 'required|boolean',
        ];
    }
}