<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BannerConditionsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'area_codes'  => $this->when($this->getAreaCodes() !== null, $this->getAreaCodes()),
            'excluded_campaigns'  => $this->when($this->getExcludedCampaigns() !== null, $this->getExcludedCampaigns()),
        ];
    }
}