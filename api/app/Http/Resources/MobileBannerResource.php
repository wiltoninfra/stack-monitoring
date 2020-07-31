<?php

namespace Promo\Http\Resources;

use Promo\Documents\Enums\BannerEnum;
use Illuminate\Http\Resources\Json\JsonResource;
use Promo\Services\DigitalGoods\DigitalGoodsService;

class MobileBannerResource extends JsonResource
{
    private $digital_goods_service;

    public function __construct($resource)
    {
        parent::__construct($resource);
        $this->digital_goods_service = new DigitalGoodsService();
    }

    public function toArray($request)
    {
        $info = null;

        // Trata o popup de informações que opcionalmente é exibido
        if ($this->getInfoTitle() !== null && $this->getInfoDescription() !== null)
        {
            $info = [];
            $info['title'] = $this->getInfoTitle();
            $info['description'] = $this->getInfoDescription();
            $info['button'] = BannerEnum::MOBILE_BUTTON_LABEL;
        }

        $final = [
            'type'                    => BannerEnum::MOBILE_HIGHLIGHT,
            'data'                    => [
                'id'                  => $this->getId(),
                'name'                => $this->getName(),
                'group'               => BannerEnum::MOBILE_STORE,
                'order'               => $this->getPriority(),
                'enabled'             => true,
                'image_url'           => $this->getImageUrl(),
                'ios_min_version'     => $this->when((bool) $this->getIosMinVersion(), $this->getIosMinVersion()),
                'android_min_version' => $this->when((bool) $this->getAndroidMinVersion(), $this->getAndroidMinVersion()),
                'info'                => $this->when((bool) $info, $info)
            ]
        ];

        switch($this->getTarget())
        {
            // Vai para a tela de boleto
            case BannerEnum::FINANCIAL_SERVICE:
                $final['data']['type'] = BannerEnum::FINANCIAL_SERVICE;
                $final['data']['data'] = [
                    'service' => $this->getTargetParam()
                ];
            break;

            // Abre webview
            case BannerEnum::MOBILE_WEBVIEW:
                $final['data']['type'] = BannerEnum::MOBILE_WEBVIEW;
                $final['data']['data'] = [
                    'url' => $this->getTargetParam()
                ];
            break;

            // Query de filtro do Search
            case BannerEnum::MOBILE_LINK:
                $final['data']['type'] = BannerEnum::MOBILE_LINK;
                $final['data']['data'] = [
                    'link_to' => $this->getTargetParam()
                ];
            break;

            // Casos de serviços Digital Goods que têm comportamento similar
            case BannerEnum::RECHARGES:
            case BannerEnum::CODES:
            case BannerEnum::TV_RECHARGES:
            case BannerEnum::TRANSPORT_PASS:
            case BannerEnum::PARKINGS:
                $final['data']['type'] = BannerEnum::DIGITAL_GOOD;

                $final['data']['data'] = $this->digital_goods_service
                    ->getServiceDetails($this->getTargetParam());

                $final['data']['data']['service'] = $this->getTarget();
            break;
        }

        return $final;
    }
}