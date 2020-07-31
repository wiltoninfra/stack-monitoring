<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CampaignVersionResource extends JsonResource
{
    public function toArray($request)
    {
        $formattedPermanenceStartDate = null;
        $formattedPermanenceEndDate = null;

        if ($this->getPermanenceStartDate() !== null) {
            $formattedPermanenceStartDate = $this->getPermanenceStartDate()->format(\DateTime::ISO8601);
        }

        if ($this->getPermanenceEndDate() !== null) {
            $formattedPermanenceEndDate = $this->getPermanenceEndDate()->format(\DateTime::ISO8601);
        }

        return [
            'id' => $this->getId(),
            'permanenceStartDate' => $formattedPermanenceStartDate,
            'permanenceEndDate' => $formattedPermanenceEndDate,
            'campaign' => $this->when($this->getCampaign() !== null, $this->getCampaign())
        ];
    }
}
