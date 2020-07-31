<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CouponStatsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'uses'  => $this->getCurrentUses(),
            'associations' => $this->when($this->getCurrentAssociations() !== null, $this->getCurrentAssociations()),
        ];
    }
}
