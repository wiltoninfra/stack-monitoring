<?php


namespace Promo\Rules;


use Doctrine\MongoDB\Query\Builder;
use PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager;
use Validator;

class HasUniqueField
{
    public static function validate()
    {
        //Extending the custom validation rule.
        Validator::extend('has_unique_field', function ($attribute, $value, $parameters) {
            $queryBuilder = DocumentManager::createQueryBuilder($parameters[0]);
            $queryBuilder->field($parameters[1])->equals(is_numeric($value) ? (int)$value : $value);
            $result = $queryBuilder->count()->getQuery()->execute();
            if ($result > 0) {
                return true;
            }

            return false;
        });
    }
}