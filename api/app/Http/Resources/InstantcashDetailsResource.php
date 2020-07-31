<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InstantcashDetailsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'value' => $this->getInstantcash(),
        ];
    }
}