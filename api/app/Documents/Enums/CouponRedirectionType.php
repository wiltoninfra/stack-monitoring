<?php

namespace Promo\Documents\Enums;

use PicPay\Common\Services\Enums\BaseEnum;

/**
 * CouponTypeEnum class
 *
 * @package Promo\Documents\Enums
 */
class CouponRedirectionType extends BaseEnum
{
    /**
     * redirecionamento para um URL web.
     */
    const WEBVIEW    = 'webview';

    /**
     * redirecimento para uma tela do aplicativo.
     */
    const APP_SCREEN  = 'app_screen';

    /**
     * Chama uma action de outro servico
     */
    const ACTION_URL  = 'action_url';


}