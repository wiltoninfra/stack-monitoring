<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CampaignHistoryResource extends JsonResource
{
    public function toArray($request)
    {
        $formattedCreatedAt = null;

        if ($this->getCreatedAt() !== null) {
            $formattedCreatedAt = $this->getCreatedAt()->format(\DateTime::ISO8601);
        }

        return [
            'document'      => $this->when($this->getDocument() !== null, $this->getDocument()),
            'user_id'       => $this->getUserId(),
            'status'        => $this->getStatus(),
            'created_at'    => $formattedCreatedAt
        ];
    }
}