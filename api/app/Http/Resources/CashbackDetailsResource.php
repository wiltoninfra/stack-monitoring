<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CashbackDetailsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'percentage' => $this->getCashback(),
            'max_value'  => $this->getCeiling(),
            'paid_by'    => $this->getPaidBy(),
        ];
    }
}