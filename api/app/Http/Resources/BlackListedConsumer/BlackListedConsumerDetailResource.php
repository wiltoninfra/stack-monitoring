<?php

namespace Promo\Http\Resources\BlackListedConsumer;

use Illuminate\Http\Resources\Json\JsonResource;

class BlackListedConsumerDetailResource extends JsonResource
{
    public function toArray($black_listed_consumer)
    {
        $formattedCreatedAt = null;

        if ($this->getCreatedAt() !== null) {
            $formattedCreatedAt = $this->getCreatedAt()->format(\DateTime::ISO8601);
        }

        return [
            'created_at' => $formattedCreatedAt,
            'origin' => $this->getOrigin(),
            'created_by' => $this->getCreatedBy(),
            'description' => $this->getDescription(),

        ];
    }
}
