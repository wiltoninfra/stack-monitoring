<?php


namespace Promo\Services\AvailableCampaings\Contracts;




use Illuminate\Support\Collection;
use Promo\Documents\Campaign;
use Promo\Services\AvailableCampaings\Validations\Rules\Rules;

class AvailableCampaignContract
{
    /**
     * @var bool
     */
    public $available;
    /**
     * @var bool
     */
    public $enabled;
    /**
     * @var Campaign
     */
    public $campaign;
    /**
     * @var Collection
     */
    public $transactions;
    /**
     * @var
     */
    public $consumerId;

    /**
     * AvailableCampaingContract constructor.
     * @param Campaign $campaign
     * @param Collection $transactions
     * @param $consumerId
     */
    public function __construct(Campaign $campaign, Collection $transactions, $consumerId)
    {
        $this->campaign = $campaign;
        $this->consumerId = $consumerId;
        $this->transactions = $transactions;
        $this->available = true;
        $this->enabled = true;
        $this->makeValidation();
    }

    /**
     * @param bool $available
     */
    private function setAvailable(bool $available): void
    {
        $this->available = $available;
    }

    /**
     * @param mixed $enabled
     */
    private function setEnabled($enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     *
     */
    private function makeValidation()
    {
        $this->vailidateAvailableRules();
        $this->validateEnabledRules();
    }

    /**
     *
     */
    private function vailidateAvailableRules(): void
    {
        $rules = Rules::AVAILABLE;
        foreach ($rules as $rule) {
            $validate = new $rule($this);
            if ($validate->isValid() === false) {
                $this->setAvailable(false);
                return;
            }
        }
    }

    /**
     *
     */
    private function validateEnabledRules()
    {
        if ($this->available === false) {
            return;
        }

        $rules = Rules::ENABLE;
        foreach ($rules as $rule) {
            $validate = new $rule($this);
            if ($validate->isValid() === false) {
                $this->setEnabled(false);
                return;
            }
        }
    }


}
