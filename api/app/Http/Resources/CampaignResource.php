<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
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
            'consumers'             => $this->when($this->getConsumers() !== null, $this->getConsumers()),
            'sellers'               => $this->when($this->getSellers() !== null, $this->getSellers()),
            'except_sellers'        => $this->when($this->getExceptSellers() !== null, $this->getExceptSellers()),
            'sellers_types'         => $this->when($this->getSellersTypes() !== null, $this->getSellersTypes()),
            'webhook_url'           => $this->when((bool) $this->getWebhookUrl(), $this->getWebhookUrl()),
            'webview_url'           => $this->when((bool) $this->getWebviewUrl(), $this->getWebviewUrl()),
            'created_at'            => $this->getCreatedAt()->format(\DateTime::ISO8601),
            'updated_at'            => $this->when((bool) $this->getUpdatedAt(), $formattedUpdateAt),
            'duration'              => new DurationDetailsResource($this->getDurationDetails()),
            'transaction'           => $this->when((bool) $this->getTransactionDetails(), new TransactionDetailsResource($this->getTransactionDetails())),
            'deposit'               => $this->when((bool) $this->getDepositDetails(), new DepositDetailsResource($this->getDepositDetails())),
            'cashback'              => $this->when((bool) $this->getCashbackDetails(), new CashbackDetailsResource($this->getCashbackDetails())),
            'cashfront'             => $this->when((bool) $this->getCashfrontDetails(), new CashfrontDetailsResource($this->getCashfrontDetails())),
            'instantcash'           => $this->when((bool) $this->getInstantcashDetails(), new InstantcashDetailsResource($this->getInstantcashDetails())),
            'limits'                => $this->when((bool) $this->getLimits(), new CampaignLimitsResource($this->getLimits())),
            'external_merchant'     => $this->when((bool) $this->getExternalMerchant(), new ExternalMerchantResource($this->getExternalMerchant())),
            'tags'                  => $this->when($this->getTags() !== null, TagResource::collection(collect($this->getTags()))),
            'permissions'           => $this->when((bool) $this->getPermissions(), new CampaignPermissionsResource($this->getPermissions())),
            'app'                   => $this->when((bool) $this->getAppDetails(), new AppDetailsResource($this->getAppDetails()))
        ];
    }
}
