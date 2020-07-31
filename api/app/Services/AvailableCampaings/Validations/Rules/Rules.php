<?php


namespace Promo\Services\AvailableCampaings\Validations\Rules;

use Promo\Services\AvailableCampaings\Validations\Rules\Available\FirstPaymentRule;
use Promo\Services\AvailableCampaings\Validations\Rules\Available\FirstPaymentToSellerRule;
use Promo\Services\AvailableCampaings\Validations\Rules\Available\UsesPerConsumerRule;
use Promo\Services\AvailableCampaings\Validations\Rules\Enable\SkipRule;

class Rules
{

    const ENABLE = [
        SkipRule::class
    ];
    const AVAILABLE = [
        FirstPaymentRule::class,
        FirstPaymentToSellerRule::class,
        UsesPerConsumerRule::class,
    ];
}
