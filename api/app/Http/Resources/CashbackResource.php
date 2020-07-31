<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class CashbackResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'campaign' => new CampaignResource($this['campaign']),
            'cashback' => $this['cashback'],
        ];
    }
}