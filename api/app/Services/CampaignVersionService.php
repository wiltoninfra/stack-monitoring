<?php

namespace Promo\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager;
use Promo\Documents\Campaign;
use Promo\Documents\CampaignVersion;
use Promo\Http\Resources\CampaignCollectionResource;
use Promo\Repositories\CampaignVersionRepository;

class CampaignVersionService extends BaseService
{
    private $campaignRepository;

    /**
     * CampaignVersionService constructor.
     * @param CampaignVersion $document
     */
    public function __construct(CampaignVersion $document)
    {
        $this->repository = DocumentManager::getRepository(CampaignVersion::class);
        $this->campaignRepository = DocumentManager::getRepository(Campaign::class);
        $this->document = $document;
    }

    /**
     * @param $associated
     * @param $disassociated
     * @param $transaction
     * @return array
     */
    public function getAllCompatibleCashbackRetroativeCampaigns($associated, $disassociated, $transaction)
    {
        Log::info('starting_retroative_cashback', [
            'context' => 'cashback',
            'status' => 'success',
            'transaction' => $transaction,
            'campaigns_associated' => $associated,
            'campaigns_disassociated' => $disassociated
        ]);

        $versions = $this->repository->getAllCompatibleCashbackRetroativeCampaigns($associated, $disassociated, $transaction);

        Log::info('retroative_cashback_versions', [
            'context' => 'cashback',
            'status' => 'success',
            'transaction' => $transaction
        ]);


        $campaignsVersions = [];

        foreach ($versions as $version) {
            $campaign = $version->getCampaign();

            if(empty($campaignsVersions[$campaign['id']])){
                $campaignsVersions[$campaign['id']] = $version;
            }else if($campaignsVersions[$campaign['id']]->getCreatedAt() < $version->getCreatedAt() ){
                $campaignsVersions[$campaign['id']] = $version;
            }
        }

        $service = app(CampaignService::class);
        $campaigns = [];
        foreach ($campaignsVersions as $version) {
            Log::info(
                'campaign_version_found',
                [
                    'context' => 'cashback',
                    'status' => 'success',
                    'campaign_version_id' => $version->getId(),
                    'transaction' => $transaction
                ]
            );
            $campaign = (array)$version->getCampaign();
            $campaignObject = $service->campaignVersionToCampaign($campaign);
            $campaignObject->transactionVersion = $version->getId();
            $campaigns[] = $campaignObject;
        }

        return $campaigns;
    }

    /**
     * @param string $campaignId
     * @param null $permanenceEndDate
     * @throws \Exception
     */
    public function generateVersion($campaignId, $permanenceEndDate = null)
    {
        $campaign = $this->campaignRepository->find($campaignId);

        $this->setEndDateLastVersion($campaign, $permanenceEndDate);

        $version = new CampaignVersion();

        $document = collect(new CampaignCollectionResource($campaign));

        unset($document['version']);

        $version->setCampaign($document);
        $version->setPermanenceStartDate(Carbon::now());

        DocumentManager::persist($version);

        $versionList = $campaign->getVersions() !== null ? $campaign->getVersions() : [];

        array_unshift($versionList, $version->getId());

        $campaign->setVersions($versionList);

        DocumentManager::persist($campaign);
    }

    /**
     * @param Campaign $campaign
     * @param $permanenceEndDate
     */
    public function setEndDateLastVersion(Campaign $campaign, $permanenceEndDate)
    {
        $versions = $campaign->getVersions();

        if (empty($versions)) {
            return;
        }

        $lastVersion = $this->repository->find(current($versions));
        $lastVersion->setPermanenceEndDate($permanenceEndDate);
        DocumentManager::persist($lastVersion);
    }
}
