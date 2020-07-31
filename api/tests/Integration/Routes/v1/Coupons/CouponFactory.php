<?php

namespace Tests\Routes\v1\Coupons;

use Promo\Services\CouponService;
use Tests\FactoryBase;
use Tests\Routes\v1\Campaigns\CampaignFactory;

class CouponFactory extends FactoryBase
{
    private $campaignFactory = null;
    private $service = null;
    
    public function __construct($app)
    {
        parent::__construct($app);
        $this->campaignFactory = new CampaignFactory(($this->getApp()));
        $this->service = app()->make(CouponService::class);
    }

    public function getDocument(array $newer = []): array
    {
        $campaign_data = isset($newer['campaign']) ? $newer['campaign'] : [];
        $campaign = $this->campaignFactory->create($campaign_data);
        
        $data = [
            'code' => $this->generateName(),
            'redirection_type' => 'webview',
            'campaign_id' => $campaign->getId(),
            'global' => true,
            'max_associations' => 10,
            'webview_url' => 'http://cdn.aws.picpay.endereco-grande.com/termos.html',
            'conditions' => [
                'first_transaction_only' => false
            ]
        ];

        $data = $this->fillData($data, $newer);

        return $data;
    }

    public function getConsumers(int $quantity = 5): array
    {
        $consumers = [
            'consumers' => range(0,$quantity)
        ];

        return $consumers;
    }

    public function generateName(int $length = 5): string
    {
        $name = strtoupper(bin2hex(random_bytes($length)));

        return $name;
    }

    public function create(array $newer = [])
    {
        $data = $this->getDocument($newer);

        $document = $this->service->create($data);

        return $document;
    }

    public function invalidateCampaign(string $campaign_id): void
    {
        $this->campaignFactory->invalidate($campaign_id);
    }

    public function delete(string $coupon_id): void
    {  
        $coupon = $this->service->delete($coupon_id, false);
        $this->deleteDependencies($coupon);
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
            if ($params->getCampaign())
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