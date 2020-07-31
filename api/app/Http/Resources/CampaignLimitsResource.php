<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CampaignLimitsResource extends JsonResource
{
    public function toArray($request)
    {
        return $this->getCampaignLimits();
    }
}