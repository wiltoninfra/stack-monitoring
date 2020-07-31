<?php


namespace Promo\Services\AvailableCampaings\Validations\Rules\Available;

use Promo\Services\AvailableCampaings\Validations\Rules\AbstractRule;

class FirstPaymentRule extends AbstractRule
{

    /**
     * @void
     */
    public function validate(): void
    {
        $transactionDatils = $this->availableCampaignContract->campaign->getTransactionDetails();
        if (isset($transactionDatils) && $transactionDatils->isFirstPayment() === null) {
            return;
        }

        if ($this->availableCampaignContract->transactions->count() > 0) {
            $this->setIsValid(false);
        }
    }
}
