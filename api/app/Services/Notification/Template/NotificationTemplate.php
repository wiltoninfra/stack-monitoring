<?php

namespace Promo\Services\Notification\Template;

use PicPay\Common\Services\NotificationService;

/**
 * Class NotificationTemplate
 * @package App\Services\Notification\Template
 */
class NotificationTemplate
{

    /**
     * @param $payloadWebhook
     * @param $mixPanelProperties
     * @param $consumer_id
     * @param $campaign_id
     * @return array
     */
    public static function getAppNotificationTemplate($payloadWebhook, $mixPanelProperties, $consumer_id, $campaign_id): array
    {

        $notification = [
            'template_id' => $payloadWebhook['target']['model'],
            'sender' => 'Reward',
            'receiver_type' => 'CONSUMER',
            'receiver' => $consumer_id,
            'parameters' => [
                'link_href' => $payloadWebhook['target']['href'],
                'link_params' => $payloadWebhook['target']['params']
            ],
            'resource' => null,
            'push' => false,
            'campaign_id' => $campaign_id,
            'schedule' => null,
            'created_at' => null,
            'event' => NotificationService::NOTIFICATION_MASS_EVENT_NAME,
            'priority' => null
        ];

        if ($payloadWebhook['target']['model'] === 'generic_to_webview') {
            $notification['parameters']['link_href'] = NotificationTemplate::getWebviewURL(
                $payloadWebhook['target']['href'],
                $payloadWebhook['target']['user_properties'],
                $payloadWebhook['target']['mixpanel_properties'],
                $mixPanelProperties
            );

        }

        $notification['parameters']['mensagem'] = $payloadWebhook['in_app']['message'];


        return $notification;
    }

    /**
     * @param $payloadWebhook
     * @param $consumer_id
     * @param $campaign_id
     * @return array
     */
    public static function getPushNotificationTemplate($payloadWebhook, $mixPanelProperties, $consumer_id, $campaign_id): array
    {
        $notification = [
            'template_id' => $payloadWebhook['target']['model'],
            'sender' => 'Reward',
            'receiver_type' => 'CONSUMER',
            'receiver' => $consumer_id,
            'parameters' => [
                'link_href' => $payloadWebhook['target']['href'],
                'link_params' => $payloadWebhook['target']['params']
            ],
            'resource' => null,
            'campaign_id' => $campaign_id,
            'schedule' => null,
            'created_at' => null,
            'event' => NotificationService::NOTIFICATION_MASS_EVENT_NAME,
            'priority' => null
        ];

        if ($payloadWebhook['target']['model'] === 'generic_to_webview') {

            $notification['parameters']['link_href'] = NotificationTemplate::getWebviewURL(
                $payloadWebhook['target']['href'],
                $payloadWebhook['target']['user_properties'],
                $payloadWebhook['target']['mixpanel_properties'],
                $mixPanelProperties
            );

        }

        $notification['parameters']['titulo'] = $payloadWebhook['push']['title'];
        $notification['parameters']['mensagem_alert_android'] = $payloadWebhook['push']['message'];
        $notification['parameters']['mensagem_alert_ios'] = $payloadWebhook['push']['message'];

        return $notification;
    }

    /**
     * @param $phone
     * @param $payloadWebhook
     * @param null $prefix
     * @return array
     */
    public static function getSmsNotificationTemplate($phone, $payloadWebhook, $consumer_id, $prefix = null): array
    {
        return [
            'phone' => str_replace('+', '', $phone),
            'message' => $payloadWebhook['sms']['message'],
            'prefix' => $prefix,
            'template_id' => ($payloadWebhook['target']['model'] ? : 'generic'),
            'receiver' => $consumer_id,
            'campaign_id' => $payloadWebhook['campaign']['id']
        ];
    }

    /**
     * @param $href
     * @param $userProperties
     * @param $mixpanelWebhookProperties
     * @param $mixPanelProperties
     * @return string
     */
    private static function getWebviewURL($href, $userProperties, $mixpanelWebhookProperties, $mixPanelProperties)
    {
        $mixpanelWebhookProperties = explode(',', $mixpanelWebhookProperties);

        $url = '?';
        if (!empty($userProperties)) {

            $parameters = explode('&', parse_url($userProperties[0], PHP_URL_QUERY));
            foreach ($parameters as $index => $value) {
                $valueParams = explode('=', $value);
                if (isset($valueParams[0]) && isset($valueParams[1])) {
                    $url .= $valueParams[0] . '=' . $valueParams[1] . '&';
                }

                if(isset($mixPanelProperties[$valueParams[0]])) {
                    $url .= $valueParams[0] . '=' . $mixPanelProperties[$valueParams[0]] . '&';
                }
            }
        }

        if (!empty($mixpanelWebhookProperties)) {

            foreach ($mixpanelWebhookProperties as $propertie) {

                if (isset($mixPanelProperties[$propertie])) {
                    $url .= $propertie . '=' . $mixPanelProperties[$propertie] . '&';
                }
            }

            $url = $href . substr($url, 0, -1);
        }else{
            $url = $href . substr($url, 0, -1);
        }

        return $url;

    }

}
