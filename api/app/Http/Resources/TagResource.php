<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TagResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'           => $this->getId(),
            'name'         => $this->getName(),
            'abbreviation' => $this->getAbbreviation(),
            'color'        => $this->getColor(),
        ];
    }
}