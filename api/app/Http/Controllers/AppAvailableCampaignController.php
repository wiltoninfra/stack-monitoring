<?php

namespace Promo\Http\Controllers;


use Illuminate\Http\Request;
use Promo\Http\Resources\AvailableCampaignResource;
use Promo\Services\AvailableCampaings\AvailableCampaignService;

class AppAvailableCampaignController extends Controller
{

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function index(Request $request)
    {
        $consumerId = $request->get("consumer_id");
        $availableCampaigns = (new AvailableCampaignService())->all($consumerId);
        return AvailableCampaignResource::collection($availableCampaigns)
            ->response();
    }
}
