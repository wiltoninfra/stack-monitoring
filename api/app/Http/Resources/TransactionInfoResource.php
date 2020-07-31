<?php


namespace Promo\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Promo\Documents\Transaction;

/**
 * Class TransactionInfoResource
 * @package Promo\Http\Resources
 *
 * @SWG\Definition(
 *     definition="TransactionInfo",
 *     type="object",
 *     @SWG\Property(property="transactions", type="array", @SWG\Items(type="transactions", example=1)),
 * )
 *
 */
class   TransactionInfoResource extends JsonResource
{

    /**
     * @param \Illuminate\Http\Request $request
     * @return array|void
     */
    public function toArray($request)
    {
        // SIM HORRIVEL, mas é o que tem p hoje.
        if ($this->resource instanceof Transaction) {
            return [
                'transaction_id' => $this->getTransactionId(),
                'type' => $this->getTransactionType(),
                'reward_given' => $this->getCashbackGiven(),
                'total'=>$this->getTransactionValue(),
                'campaign_name' => $this->getCampaign()->getName(),
                'campaign_id' => $this->getCampaign()->getId(),
                'campaign_max_value' =>
                    $this->getCampaign()->getCashbackDetails()->getCeiling() * ($this->getCampaign()->getCashbackDetails()->getCashback() / 100)
            ];
        }

        return [
            'transaction_id' => (int)$this->getDetails()['id'],
            'type' => '',
            'reward_given' => $this->getCashfrontGiven(),
            'campaign_name' => $this->getCampaign()->getName(),
            'campaign_id' => $this->getCampaign()->getId(),
            'total'=>$this->getDetails()['total'],
            'campaign_max_value' =>
                $this->getCampaign()->getCashfrontDetails()->getCeiling() * ($this->getCampaign()->getCashfrontDetails()->getCashfront() / 100)
        ];
    }
}
