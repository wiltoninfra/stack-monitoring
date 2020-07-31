<?php


namespace Promo\Services\AvailableCampaings\Validations\Rules\Enable;


use PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager;
use Promo\Documents\ConsumerCampaign;
use Promo\Documents\Transaction;
use Promo\Services\AvailableCampaings\Validations\Rules\AbstractRule;

class SkipRule extends AbstractRule
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

        $usesPerConsumerPerPeriod = $limits->getUsesPerConsumerPerPeriod();

        if ($limits == null || $usesPerConsumerPerPeriod == null) {
            return;
        }

        $uses = $limits->getUsesPerConsumerPerPeriod()->getUses();

        $transactionRepository = DocumentManager::getRepository(Transaction::class);
        $consumerCampaignRepository = DocumentManager::getRepository(ConsumerCampaign::class);
        $consumerCampaign = $consumerCampaignRepository->getOne($consumerId, $campaign);

        if (!$consumerCampaign) {
            return;
        }

        if ($uses <= $transactionRepository->processTransactionsOfPeriod($consumerCampaign,
                $usesPerConsumerPerPeriod->getType(),
                $usesPerConsumerPerPeriod->getPeriod())
        ) {
            $this->setIsValid(false);
        }
    }
}
