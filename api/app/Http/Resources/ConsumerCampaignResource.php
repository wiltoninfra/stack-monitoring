<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConsumerCampaignResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'active'                         => $this->isActive(),
            'consumer_id'                    => $this->getConsumerId(),
            'campaign'                       => new CampaignResource($this->getCampaign()),
            'completed_transactions'         => $this->getCompletedTransactions(),
            'restarted'                      => $this->when((bool) $this->isRestarted(), $this->isRestarted()),
            'type'                           => $this->getType(),
            'cancelled_by_transaction_type'  => $this->when((bool) $this->getCancelledByTransactionType(), $this->getCancelledByTransactionType()),
            'cancelled_by_transaction_id'    => $this->when((bool) $this->getCancelledByTransactionId(), $this->getCancelledByTransactionId()),
            'created_at'                     => $this->getCreatedAt()->format(\DateTime::ISO8601),
        ];
    }
}