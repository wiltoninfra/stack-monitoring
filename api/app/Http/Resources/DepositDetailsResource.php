<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DepositDetailsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'max_deposits'                      => $this->when((bool) $this->getMaxDeposits(), $this->getMaxDeposits()),
            'max_deposits_per_consumer'         => $this->when((bool) $this->getMaxDepositsPerConsumer(), $this->getMaxDepositsPerConsumer()),
            'max_deposits_per_consumer_per_day' => $this->when((bool) $this->getMaxDepositsPerConsumerPerDay(), $this->getMaxDepositsPerConsumerPerDay()),
            'min_deposit_value'                 => $this->when((bool) $this->getMinDepositValue(), $this->getMinDepositValue()),
            'first_deposit_only'                => $this->isFirstDepositOnly(),
        ];
    }
}