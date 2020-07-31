<?php

namespace Promo\Documents\Enums;

use PicPay\Common\Services\Enums\BaseEnum;

class AssociationTypeEnum extends BaseEnum
{
    /**
     * Sobre associações segmentadas, geralmente a partir
     * de chamadas de webhook do Mixpanel
     */
    const SEGMENTATION = 'segmentation';

    /**
     * O usuário foi associado a partir da aplicação
     * de um cupom
     */
    const COUPON = 'coupon';

    /**
     * Quando o usuário recebe associação a campanhas Instantcash (que dão crédito simplesmente
     * ao ser associado a ela)
     */
    const INSTANTCASH = 'instantcash';
}