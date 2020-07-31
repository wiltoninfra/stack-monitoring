<?php


namespace Promo\Services\AvailableCampaings\Validations\Rules\Available;


use Illuminate\Support\Arr;
use Promo\Services\AvailableCampaings\Validations\Rules\AbstractRule;

class FirstPaymentToSellerRule extends AbstractRule
{

    /**
     * @void
     */
    public function validate(): void
    {
        $campaign = $this->availableCampaignContract->campaign;
        $transactionsDetails = $campaign->getTransactionDetails();

        if (isset($transactionsDetails) && !$transactionsDetails->isFirstPaymentToSeller()) {
            return;
        }

        $transactions = $this->availableCampaignContract->transactions->whereIn("seller_id", $campaign->getSellers());

        if ($transactions->count() > 0) {
            $this->setIsValid(false);
        }
    }
}
