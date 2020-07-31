<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationVariantPayloadResource extends JsonResource
{
    public function toArray($request)
    {
        return $this->getVariants();
    }
}