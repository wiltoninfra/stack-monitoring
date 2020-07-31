<?php

namespace Promo\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Support\Facades\Validator;
use Promo\Rules\HasUniqueField;
use Promo\Rules\InstallmentsConditions;
use Promo\Rules\IntegerType;
use Promo\Rules\UniqueField;
use Promo\Validation\PresenceVerifierProvider;
use Promo\Rules\NotificationMessage;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        Resource::withoutWrapping();

        Validator::extendImplicit('this_or_that', function ($attribute, $value, $parameters, $validator) {
            return (bool) (!empty($value) ^ (array_key_exists($parameters[0], $validator->getData()) && !empty($validator->getData()[$parameters[0]])));
        });
    }
    
    /**
     * Register any application services.
     * @codeCoverageIgnore
     * @return void
     */
    public function register()
    {
        UniqueField::validate();
        HasUniqueField::validate();
        IntegerType::validate();
        NotificationMessage::validate();
        InstallmentsConditions::validate();
    }
}
