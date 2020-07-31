<?php

namespace Promo\Rules;

use Illuminate\Support\Facades\Validator;
use Promo\Documents\Enums\PaymentMethodsEnum;

class InstallmentsConditions
{
    public static function validate()
    {
        Validator::extend('installments_conditions', function ($attribute, $value, $parameters, $validator) {
            $data = $validator->getData();
            $paymentMethods = $data['transaction']['payment_methods'];

            foreach ($paymentMethods as $method) {
                if (empty($data['transaction']['conditions']) && $method == PaymentMethodsEnum::CREDIT_CARD) {
                    return false;
                }
            }
            return true;
        });
    }
}
