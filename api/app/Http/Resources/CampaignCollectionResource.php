<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CampaignCollectionResource extends JsonResource
{
    public function toArray($request)
    {
        $formattedUpdateAt = null;
        $formattedCreatedAt = null;

        if ($this->getUpdatedAt() !== null) {
            $formattedUpdateAt = $this->getUpdatedAt()->format(\DateTime::ISO8601);
        }

        if ($this->getCreatedAt() !== null) {
            $formattedCreatedAt = $this->getCreatedAt()->format(\DateTime::ISO8601);
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
            'created_at'            => $formattedCreatedAt,
            'updated_at'            => $this->when((bool) $this->getUpdatedAt(), $formattedUpdateAt),
            'duration'              => collect(new DurationDetailsResource($this->getDurationDetails())),
            'transaction'           => empty($this->getTransactionDetails()) ? null : collect(new TransactionDetailsResource($this->getTransactionDetails())),
            'deposit'               => empty($this->getDepositDetails()) ? null : collect(new DepositDetailsResource($this->getDepositDetails())),
            'cashback'              => empty($this->getCashbackDetails()) ? null : collect(new CashbackDetailsResource($this->getCashbackDetails())),
            'cashfront'             => empty($this->getCashfrontDetails()) ? null : collect(new CashfrontDetailsResource($this->getCashfrontDetails())),
            'instantcash'           => empty($this->getInstantcashDetails()) ? null : collect(new InstantcashDetailsResource($this->getInstantcashDetails())),
            'limits'                => empty($this->getLimits()) ? null : collect(new CampaignLimitsResource($this->getLimits())),
            'external_merchant'     => empty($this->getExternalMerchant()) ? null : collect(new ExternalMerchantResource($this->getExternalMerchant())),
            'tags'                  => empty($this->getTags()) ? null : TagResource::collection(collect($this->getTags())),
            'permissions'           => empty($this->getPermissions()) ? null : collect(new CampaignPermissionsResource($this->getPermissions())),
            'app'                   => empty($this->getAppDetails()) ? null : collect(new AppDetailsResource($this->getAppDetails()))
        ];
    }
}
