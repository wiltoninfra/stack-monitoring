<?php

namespace Promo\Services;

use Illuminate\Support\Facades\Log;
use Promo\Documents\BlackListedConsumer;

use PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager;
use Promo\Documents\Enums\TransactionTypeEnum;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class BlackListedConsumerService extends BaseService
{

    /**
     * BlackListedConsumerService constructor.
     * @param BlackListedConsumer $document
     */
    public function __construct(BlackListedConsumer $document)
    {
        $this->document = $document;
        $this->repository = DocumentManager::getRepository(BlackListedConsumer::class);
    }

    /**
     * @param $data
     * @param $consumer_id
     * @return mixed
     */
    public function updateByConsumerId($data, $consumer_id){
        $consumerBlacklistUpdated = $this->repository->updateByConsumerId($data,$consumer_id);
        if ($consumerBlacklistUpdated){
            return $consumerBlacklistUpdated;
        }
        throw new NotFoundHttpException('Usuário nao encontrado');
    }

    public function filterCampaignsAllowed($campaigns, $consumerId): array
    {
        $associated = $this->repository->findOneBy(['consumer_id' => $consumerId]);

        if ($associated) {
            $campaignsAllowed = [];
            foreach ($campaigns as $campaign) {
                $transactionType = $campaign->getTransactionDetails()->getType();
                $sameTransactionType = in_array($transactionType, $associated->getTransactionTypes());
                $sameCampaignType = in_array($campaign->getType(), $associated->getCampaignTypes());
                if (empty($associated->getCampaignTypes())) {
                    $sameCampaignType = true;
                }
                if ($transactionType == TransactionTypeEnum::MIXED){
                    $sameTransactionType = true;
                }
                if (!($sameTransactionType && $sameCampaignType)) {
                    $campaignsAllowed[] = $campaign;
                }
            }

            return $campaignsAllowed;
        }

        if(is_array($campaigns)) {
            return $campaigns;
        }

        return $campaigns->toArray();
    }
}
