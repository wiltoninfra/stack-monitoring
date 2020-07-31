<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CampaignTreadResource extends JsonResource
{
    public function toArray($request)
    {
        $formattedUpdateAt = null;

        if ($this->getUpdatedAt() !== null) {
            $formattedUpdateAt = $this->getUpdatedAt()->format(\DateTime::ISO8601);
        }

        return [
            'id'                    => $this->getId(),
            'name'                  => $this->getName(),
            'description'           => $this->getDescription(),
            'type'                  => $this->getType(),
            'active'                => $this->isActive(),
            'communication'         => $this->isCommunication(),
            'global'                => $this->isGlobal(),
            'duration'              => new DurationDetailsResource($this->getDurationDetails()),
            'cashback'              => $this->when((bool) $this->getCashbackDetails(), new CashbackDetailsResource($this->getCashbackDetails())),
        ];
    }
}
