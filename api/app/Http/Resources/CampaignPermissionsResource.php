<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CampaignPermissionsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'update' => $this->getUpdate(),
            'delete' => $this->getDelete(),
        ];
    }
}