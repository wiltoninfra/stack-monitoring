<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CampaignSellerResource extends JsonResource
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
            'global'                => $this->isGlobal(),
            'active'                => $this->isActive(),
            'communication'         => $this->isCommunication(),
            'sellers_types'         => $this->when($this->getSellersTypes() !== null, $this->getSellersTypes()),
            'consumers'             => $this->when($this->getConsumers() !== null, $this->getConsumers()),
            'transaction'           => $this->when((bool) $this->getTransactionDetails(), new TransactionDetailsResource($this->getTransactionDetails())),
            'limits'                => $this->when((bool) $this->getLimits(), new CampaignLimitsResource($this->getLimits())),
            'duration'              => new DurationDetailsResource($this->getDurationDetails()),
            'cashback'              => $this->when((bool) $this->getCashbackDetails(), new CashbackDetailsResource($this->getCashbackDetails())),
            'cashfront'             => $this->when((bool) $this->getCashfrontDetails(), new CashfrontDetailsResource($this->getCashfrontDetails())),
            'instantcash'           => $this->when((bool) $this->getInstantcashDetails(), new InstantcashDetailsResource($this->getInstantcashDetails())),
            'deposit'               => $this->when((bool) $this->getDepositDetails(), new DepositDetailsResource($this->getDepositDetails())),
            'tags'                  => $this->when($this->getTags() !== null, TagResource::collection(collect($this->getTags()))),
            'created_at'            => $this->getCreatedAt()->format(\DateTime::ISO8601),
            'updated_at'            => $this->when((bool) $this->getUpdatedAt(), $formattedUpdateAt),
        ];
    }
}