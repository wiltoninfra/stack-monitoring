<?php


namespace Promo\Listeners;


use Carbon\Carbon;
use Promo\Events\Campaign\CampaignChangeEvent;
use Promo\Services\CampaignVersionService;

class GenerateCampaignVersionListener
{
    /**
     * @var CampaignVersionService
     */
    private $campaignVersionService;

    /**
     * GenerateCampaignVersionListener constructor.
     * @param CampaignVersionService $campaignService
     */
    public function __construct(CampaignVersionService $campaignService)
    {
        $this->campaignVersionService = $campaignService;
    }

    /**
     * @param CampaignChangeEvent $event
     * @throws \Exception
     */
    public function handle(CampaignChangeEvent $event)
    {
        $this->campaignVersionService->generateVersion($event->campaignId(), Carbon::now());
    }


}
