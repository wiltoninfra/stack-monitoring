<?php

namespace Promo\Services;

use GuzzleHttp\Client;
use Promo\Services\CampaignService;
use Promo\Services\Core\CoreService;

/**
 * Class ActionUrlService
 * Class ActionUrlService
 * @package Promo\Services
 */
class ActionUrlService
{

    /**
     * @var Client
     */
    private $client;

    /** @var \Promo\Services\CampaignService */
    protected $campaign_service;

    private $core_service;

    /**
     * ActionUrlService constructor.
     * @param Client $client
     */
    public function __construct(Client $client, CampaignService $campaignService, CoreService $coreService)
    {
        $this->client = $client;
        $this->campaign_service = $campaignService;
        $this->core_service = $coreService;
    }

    /**
     * @param $consumer_id
     * @return bool
     */
    public function summerCampaign2019($consumer_id)
    {
        $this->core_service->upgradeUserProWithoutFee($consumer_id);
        $this->core_service->addLabelToConsumer($consumer_id);
        $this->campaign_service->addConsumer((int) $consumer_id, (string) config('microservices.acceleration_campaign_id'));

        return true;
    }

    /**
     * @param $consumer_id
     * @return bool
     */
    public function reveillonSalvador2020($consumer_id)
    {
        $this->core_service->upgradeUserProWithoutFee($consumer_id);
        $this->core_service->addSalvadorLabelToConsumer($consumer_id);

        return true;
    }


}