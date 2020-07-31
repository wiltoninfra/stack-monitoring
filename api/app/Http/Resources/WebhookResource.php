<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WebhookResource extends JsonResource
{
    public function toArray($request)
    {

        return [
            'id'          => $this->getId(),
            'campaign'    => $this->when((bool) $this->getCampaign(), new CampaignResource($this->getCampaign())),
            'coupon'      => $this->when((bool) $this->getCoupon(), new CouponResource($this->getCoupon())),
            'variants'    => NotificationVariantPayloadResource::collection(collect($this->getVariants()))
        ];
    }
}