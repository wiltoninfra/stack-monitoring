<?php

namespace Promo\Documents\Enums;

use PicPay\Common\Services\Enums\BaseEnum;

class BannerEnum extends BaseEnum
{
    const MOBILE_LINK = 'link';

    const MOBILE_WEBVIEW = 'web_view';

    const MOBILE_STORE = 'STORE';

    const MOBILE_HIGHLIGHT = 'highlight';

    const MOBILE_BUTTON_LABEL = 'ENTENDI';

    const DIGITAL_GOOD = 'digital_good';

    const BOLETO = 'boleto';

    const FINANCIAL_SERVICE = 'financial_service';

    const RECHARGES = 'phonerecharges';

    const CODES = 'digitalcodes';

    const TV_RECHARGES = 'tvrecharges';

    const TRANSPORT_PASS = 'transportpass';

    const PARKINGS = 'parkings';
}