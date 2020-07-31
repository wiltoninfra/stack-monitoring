<?php

namespace Promo\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    public function toArray($request)
    {
        $final = [
            'id'                  => $this->getId(),
            'active'              => $this->isActive(),
            'name'                => $this->getName(),
            'global'              => $this->isGlobal(),
            'image_url'           => $this->getImageUrl(),
            'conditions'          => $this->when((bool) $this->getConditions(), new BannerConditionsResource($this->getConditions())),
            'campaign'            => $this->when((bool) $this->getCampaign(), new CampaignResource($this->getCampaign())),
            'ios_min_version'     => $this->when((bool) $this->getIosMinVersion(), $this->getIosMinVersion()),
            'android_min_version' => $this->when((bool) $this->getAndroidMinVersion(), $this->getAndroidMinVersion()),
            'created_at'          => Carbon::instance($this->getCreatedAt())->toIso8601String(),
            'target'              => [
                'name' => $this->getTarget(),
                'param' => $this->getTargetParam()
            ],
        ];

        $info = null;

        if ($this->getInfoTitle() !== null && $this->getInfoDescription() !== null)
        {
            $info = ['info' => []];
            $info['info']['title'] = $this->getInfoTitle();
            $info['info']['description'] = $this->getInfoDescription();

            $final = array_merge($final, $info);
        }

        $dates = null;

        if ($this->getStartDate() !== null && $this->getEndDate() !== null)
        {
            $dates = [];
            $dates['start_date'] = Carbon::instance($this->getStartDate())->toIso8601String();
            $dates['end_date'] = Carbon::instance($this->getEndDate())->toIso8601String();

            $final = array_merge($final, $dates);
        }

        return $final;
    }
}