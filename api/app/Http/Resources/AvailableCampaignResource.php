<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AvailableCampaignResource extends JsonResource
{
    public function toArray($request)
    {
        $campaign = $this->campaign;
        return [
            'id' => $campaign->getId(),
            'name' => $campaign->getName(),
            'description' => $campaign->getDescription(),
            'type' => $campaign->getType(),
            'active' => $campaign->isActive(),
            'global' => $campaign->isGlobal(),
            'consumer_as_seller' => $campaign->isConsumerAsSeller(),
            'consumers' => $campaign->getConsumers(),
            'global_seller' => $campaign->isGlobalSeller(),
            'sellers' => $campaign->getSellers(),
            'except_sellers' =>  $campaign->getExceptSellers(),
            'sellers_types' => $campaign->getSellersTypes(),
            'created_at' => $campaign->getCreatedAt()->format(\DateTime::ISO8601),
            'duration' => new DurationDetailsResource($campaign->getDurationDetails()),
            'transaction' => new TransactionDetailsResource($campaign->getTransactionDetails()),
            'deposit' => new DepositDetailsResource($campaign->getDepositDetails()),
            'cashback' =>new CashbackDetailsResource($campaign->getCashbackDetails()),
            'limits' => new CampaignLimitsResource($campaign->getLimits()),
            'app' => new AppDetailsResource($campaign->getAppDetails()),
            'enabled' => $this->enabled
        ];
    }

}
