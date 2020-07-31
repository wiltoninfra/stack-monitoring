<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CampaignStatsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'stats' => [
                'uses' => $this['uses'],
                'associations' => $this->when($this['associations'] !== null, $this['associations']),
                'transactions_sum_value' => $this['transactions_sum_value'] ?? 0,
                'cashback_sum_value' => $this['cashback_sum_value'] ?? 0,
            ]
        ];
    }
}
