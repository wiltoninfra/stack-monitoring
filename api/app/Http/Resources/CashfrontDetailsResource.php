<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CashfrontDetailsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'percentage' => $this->getCashfront(),
            'max_value'  => $this->getCeiling(),
            'recharge_method'  => $this->getRechargeMethod(),
        ];
    }
}