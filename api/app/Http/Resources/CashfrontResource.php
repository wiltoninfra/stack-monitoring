<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CashfrontResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'campaign' => new CampaignResource($this['campaign']),
            'cashfront' => $this['cashfront'],
        ];
    }
}