<?php


namespace Promo\Services\AvailableCampaings\Validations\Rules\Available;


use PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager;
use Promo\Documents\ConsumerCampaign;
use Promo\Documents\Transaction;
use Promo\Services\AvailableCampaings\Validations\Rules\AbstractRule;

class UsesPerConsumerRule extends AbstractRule
{
    /**
     * @void
     */
    public function validate(): void
    {
        $campaign = $this->availableCampaignContract->campaign;
        $consumerId = $this->availableCampaignContract->consumerId;

        $limits = $campaign->getLimits();
        if (!$limits) {
            return;
        }

        $usesPerConsumer = $limits->getUsesPerConsumer();

        if ($limits == null || $usesPerConsumer == null) {
            return;
        }

        $transactionRepository = DocumentManager::getRepository(Transaction::class);
        $consumerCampaignRepository = DocumentManager::getRepository(ConsumerCampaign::class);
        $consumerCampaign = $consumerCampaignRepository->getOne($consumerId, $campaign);

        if (!$consumerCampaign) {
            return;
        }

        if ($usesPerConsumer->getUses() !== null
            && $usesPerConsumer->getUses() <=
            $transactionRepository->processTransactionsOfPeriod($consumerCampaign,
                $usesPerConsumer->getType())
        ){
            $this->setIsValid(false);
        }
    }
}
