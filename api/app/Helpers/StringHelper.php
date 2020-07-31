<?php

namespace Promo\Helpers;

/**
 * Class StringHelper
 * @package Promo\Helper
 */
class StringHelper
{

    /**
     * @param $string
     * @param $start
     * @param $end
     * @return false|string
     */
     public static function getStringBetween($string, $start, $end)
     {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }

}
