<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AppDetailsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'description' => $this->getDescription(),
            'image' => $this->getImage(),
            'category' => $this->getCategory(),
            'action_type' => $this->getActionType(),
            'action_data' => $this->getActionData(),
            'tracking' => $this->getTracking(),
        ];
    }
}
