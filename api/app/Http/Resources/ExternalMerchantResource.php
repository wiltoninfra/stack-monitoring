<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExternalMerchantResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'type' => $this->getType(),
            'ids'  => $this->getIds(),
        ];
    }
}