<?php

namespace Tests\Routes\v1\Banners;

use Carbon\Carbon;
use Promo\Services\BannerService;
use Promo\Services\RewardService;
use Tests\FactoryBase;
use Tests\Routes\v1\Campaigns\CampaignFactory;

class BannerFactory extends FactoryBase
{
    private $campaignFactory = null;
    private $service = null;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->campaignFactory = new CampaignFactory(($this->getApp()));
        $this->service = new BannerService($this->getApp()->make(RewardService::class));
    }
    
    public function getDocument(array $newer = []): array
    {
        $now = Carbon::now();
        $now_plus_2_days = $now->copy()->addDays(2);

        $data = [
            "image"=> "data:image/png;base64,iVBORw0KGgoAAAAN...",
            "name"=> "Store 20% de amor",
            "global"=> false,
            "start_date" => $now->toIso8601String(),
            "end_date" => $now_plus_2_days->toIso8601String(),
            "target"=> [
                "name"=> "financial_service",
                "param"=> "boleto"
            ],
            "ios_min_version"=> "10.3",
            "android_min_version"=> "10.3",
            "info" => [
                "title" => "Título legal",
                "description" => "Informação mais legal ainda"
            ]
        ];
        
        $data = $this->fillData($data, $newer);

        if(!$data['global'])
        {
            $campaign_data = isset($newer['campaign']) ? $newer['campaign'] : [];
            
            $campaign = $this->campaignFactory->create($campaign_data);

            $data['campaign_id'] = $campaign->getId();
        }
        else
        {
            $data['conditions'] = [
                "area_codes"=> [
                    27
                ],
                "excluded_campaigns"=> [
                    "5e53d1d011ec5d00470bd852"
                ]
            ];
        }


        return $data;
    }

    public function create(array $newer = [])
    {
        $data = $this->getDocument($newer);

        $document = $this->service->create($data);

        return $document;
    }

    public function delete(string $banner_id): void
    {  
        $banner = $this->service->delete($banner_id, false);
        $this->deleteDependencies($banner);
    }

    public function deleteDependencies($params): void
    {
        $campaign_id = null;

        if(is_array($params))
        {
            if(array_key_exists('campaign_id', $params))
            {
                $campaign_id = $params['campaign_id'];
            }
        }
        else
        {
            if($params->getCampaign())
            {
                $campaign_id = $params->getCampaign()->getId();
            }
        }

        if($campaign_id)
        {
            $this->campaignFactory->delete($campaign_id);
        }
        
    }
}