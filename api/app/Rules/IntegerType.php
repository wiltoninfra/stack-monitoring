<?php


namespace Promo\Rules;


use PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager;
use Validator;

class IntegerType
{
    public static function validate()
    {
        //Extending the custom validation rule.
        Validator::extend('integer_type', function ($attribute, $value, $parameters) {
            return is_int($value);
        });
    }
}