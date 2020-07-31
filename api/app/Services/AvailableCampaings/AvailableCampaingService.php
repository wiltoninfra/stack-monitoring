<?php


namespace Promo\Services\AvailableCampaings;


use Illuminate\Support\Facades\DB;
use Promo\Documents\Enums\CampaignTypeEnum;
use Promo\Services\AvailableCampaings\Contracts\AvailableCampaignContract;
use Promo\Services\CampaignService;
use Promo\Services\ConsumerCampaignService;

class AvailableCampaignService
{

    public function all($consumerId)
    {
        return $this->getAvailableCampaigns($consumerId);
    }

    public function getAvailableCampaigns($consumerId)
    {
        $campaigns = $this->getCampaigns($consumerId);

        $transactions = $this->getTransactionsByConsumerId($consumerId);
        $data = [];
        foreach ($campaigns as $campaign) {
            $data[] = new AvailableCampaignContract($campaign, $transactions, $consumerId);
        }
        return collect($data)->where("available", true);
    }

    /**
     * @return mixed
     */
    private function getCampaigns($consumerId)
    {
        $campaignService = app(CampaignService::class);
        $consumerCampaignService = app(ConsumerCampaignService::class);

        $globalCampaigns = $campaignService->getAllQuery(["active" => true, "type" => CampaignTypeEnum::CASHBACK, "global" => true, 'transaction.type' => 'pav'])->getQuery()->execute()->toArray();


        $globalCampaigns = array_filter($globalCampaigns, function ($campaign) {
            return $campaign->isActive();
        });

        $consumerCampaignAssociation = $consumerCampaignService->getConsumerAssociationsQuery($consumerId, ["active" => true, 'campaign_active' => true])->getQuery()->execute()->toArray();
        $consumerCampaigns = [];
        foreach ($consumerCampaignAssociation as $consumerCampagin) {
            $campaign = $consumerCampagin->getCampaign();
            if ($campaign->isActive()) {
                $consumerCampaigns[] = $campaign;
            }

        }

        $campaigns = array_merge($globalCampaigns, $consumerCampaigns);
        return collect($campaigns)->unique(function ($campaign) {
            return $campaign->getId();
        })->toArray();
    }


    /**
     * @param $consumerId
     * @return \Illuminate\Support\Collection
     */
    private function getTransactionsByConsumerId($consumerId)
    {
        $transactions = DB::connection("legacy")->table("transactions");

        return $transactions
            ->select('seller_id')
            ->where("consumer_id", $consumerId)
            ->get();
    }


}
