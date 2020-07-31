<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class NotificationTemplateRulerResource extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection
        ];
    }

}