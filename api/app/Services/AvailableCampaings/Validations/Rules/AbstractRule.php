<?php


namespace Promo\Services\AvailableCampaings\Validations\Rules;


use Promo\Services\AvailableCampaings\Contracts\AvailableCampaignContract;

abstract class AbstractRule
{
    /**
     * @var bool
     */
    private $isValid;

    /**
     * @var AvailableCampaignContract
     */
    protected $availableCampaignContract;

    /**
     * FirstPaymentToSellerRule constructor.
     * @param AvailableCampaignContract $availableCampaignContract
     */
    public function __construct(AvailableCampaignContract $availableCampaignContract)
    {
        $this->availableCampaignContract = $availableCampaignContract;
        $this->isValid = true;
        $this->validate();
    }


    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * @param bool $isValid
     */
    public function setIsValid(bool $isValid): void
    {
        $this->isValid = $isValid;
    }

    abstract function validate(): void;
}
