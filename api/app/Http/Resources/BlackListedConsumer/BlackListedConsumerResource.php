<?php

namespace Promo\Http\Resources\BlackListedConsumer;

use Illuminate\Http\Resources\Json\JsonResource;

class BlackListedConsumerResource extends JsonResource
{
    public function toArray($param)
    {
        $formattedUpdateAt = null;

        if ($this->getUpdatedAt() !== null) {
            $formattedUpdateAt = $this->getUpdatedAt()->format(\DateTime::ISO8601);
        }

        return [
            'consumer_id'       => $this->when($this->getConsumerId() !== 0,$this->getConsumerId()),
            'active'            => $this->when($this->isActive() !== null, $this->isActive()),
            'campaign_types'    => $this->when($this->getCampaignTypes() !== null, $this->getCampaignTypes()),
            'transaction_types' => $this->when($this->getTransactionTypes() !== null, $this->getTransactionTypes()),
            'details'           => $this->when(count($this->getDetails()) !==  0,
                BlackListedConsumerDetailResource::collection(collect($this->getDetails())->slice(-20,20))),
        ];
    }
}
