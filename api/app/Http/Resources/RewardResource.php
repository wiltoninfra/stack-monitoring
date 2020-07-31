<?php

namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class RewardResource
 * @package App\Http\Resources
 */
class RewardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            collect([
                'transaction_id' => (int) $request['transaction']['id'],
                'transaction_type' => (string) $request['transaction']['type'],
                'consumer_id' => (int) $request['transaction']['consumer_id'],
                'amount' => $this['cashback'],
                'response' => new CashbackResource($this)
          ])
        ];
    }
}
