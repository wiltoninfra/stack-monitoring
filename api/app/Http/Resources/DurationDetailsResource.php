<?php

namespace Promo\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class DurationDetailsResource extends JsonResource
{
    public function toArray($request)
    {
        $result = [
            'fixed' => $this->isFixed()
        ];

        if ($result['fixed'])
        {
            $result['start_date'] = Carbon::instance($this->getStartDate())->toIso8601String();
            $result['end_date']  = Carbon::instance($this->getEndDate())->toIso8601String();
        }
        else
        {
            $result['hours'] = $this->getHours();
            $result['days'] = $this->getDays();
            $result['weeks'] = $this->getWeeks();
            $result['months'] = $this->getMonths();
        }

        return $result;
    }
}