<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CouponConditionsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'first_transaction_only'  => $this->when($this->getFirstTransactionOnly() !== null, $this->getFirstTransactionOnly()),
            'area_codes'              => $this->when($this->getAreaCodes() !== null, $this->getAreaCodes()),
        ];
    }
}