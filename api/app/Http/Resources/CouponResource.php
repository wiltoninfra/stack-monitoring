<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Promo\Documents\Enums\CouponRedirectionType;

class CouponResource extends JsonResource
{
    public function toArray($request)
    {
        $result = [
            'id'                => $this->getId(),
            'code'              => $this->getCode(),
            'active'            => $this->isActive(),
            'global'            => $this->isGlobal(),
            'max_associations'  => $this->when((bool) $this->getMaxAssociations(), $this->getMaxAssociations()),
            'conditions'        => new CouponConditionsResource($this->getConditions()),
            'stats'             => new CouponStatsResource($this->getStats()),
            'redirection_type'  => $this->getRedirectionType()
        ];

        switch ($result['redirection_type'])
        {
            case CouponRedirectionType::WEBVIEW:
                $result['webview_url'] = $this->when((bool) $this->getWebviewUrl(), $this->getWebviewUrl());
                $result['campaign']    = $this->when((bool) $this->getCampaign(), new CampaignResource($this->getCampaign()));
                break;
            case CouponRedirectionType::ACTION_URL:
                $result['webview_url'] = $this->when((bool) $this->getWebviewUrl(), $this->getWebviewUrl());
                break;
            case CouponRedirectionType::APP_SCREEN:
                $result['app_screen_path'] = $this->getAppScreenPath();
                break;
        }

        return $result;
    }
}