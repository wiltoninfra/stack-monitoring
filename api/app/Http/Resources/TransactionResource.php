<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class TransactionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'             => $this->getTransactionId(),
            'type'           => $this->getTransactionType(),
            'cashback_given' => $this->getCashbackGiven(),
            'value'          => $this->getTransactionValue(),
            'details'        => $this->getDetails(),
            'campaign'       => new CampaignResource($this->getCampaign()),
            'created_at'     => Carbon::instance($this->getCreatedAt())->toIso8601String(),
        ];
    }
}