<?php

namespace Promo\Http\Requests;

/**
 * WebhookRequest class
 *
 * @SWG\Definition(
 *      definition="Webhook",
 *      type="object",
 *
 *      @SWG\Property(property="campaign_id", type="string", example="5d1b7faa983e151c6a42d911"),
 *      @SWG\Property(property="coupon_id", type="string", example="1"),
 *      @SWG\Property(property="variants", type="array", @SWG\Items(ref="#/definitions/WebhookVariant")),
 * )
 *
 * @SWG\Definition(
 *      definition="WebhookVariant",
 *      type="object",
 *      @SWG\Property(property="name", type="string", example="Nome"),
 *      @SWG\Property(property="percentage", type="float", example="2"),
 *      @SWG\Property(property="target", ref="#/definitions/WebhookTargetInfo"),
 *      @SWG\Property(property="push", ref="#/definitions/PushInfo"),
 *      @SWG\Property(property="in_app", ref="#/definitions/InAppInfo"),
 *      @SWG\Property(property="sms", ref="#/definitions/SMSInfo"),
 * )
 *
 * @SWG\Definition(
 *     definition="WebhookTargetInfo",
 *     type="object",
 *     @SWG\Property(property="model", type="string", enum={"generic", "generic_to_screen", "generic_to_webview"}, example="generic"),
 *     @SWG\Property(property="href", type="string", example=""),
 *     @SWG\Property(property="params", type="string", example=""),
 *     @SWG\Property(property="user_properties", type="array", @SWG\Items(type="string")),
 * )
 *
 * @SWG\Definition(
 *      definition="PushInfo",
 *      type="object",
 *      @SWG\Property(property="title", type="string", example=""),
 *      @SWG\Property(property="message", type="string", example=""),
 * )
 *
 * @SWG\Definition(
 *      definition="InAppInfo",
 *      type="object",
 *      @SWG\Property(property="message", type="string", example=""),
 * )
 *
 * @SWG\Definition(
 *      definition="SMSInfo",
 *      type="object",
 *      @SWG\Property(property="message", type="string", example=""),
 * )
 */
class WebhookRequest
{
    /**
     * Regras de validação criação do webhook
     *
     * @return array
     */
    public static function rules($campaign_id)
    {

        return [
            'campaign_id'              => 'this_or_that:coupon_id|string',
            'coupon_id'                => 'this_or_that:campaign_id|string',
            'variants'                 => 'required|array',
            'variants.*.name'          => 'required|string',
            'variants.*.percentage'    => 'required|numeric',
            'variants.*.target.model'  => 'required|string|in:generic,generic_to_screen,generic_to_webview',
            'variants.*.target.href'   => 'string',
            'variants.*.target.params' => 'string',

            'variants.*.target.user_properties'     => 'array',
            'variants.*.target.user_properties.*'   => 'string',

            'variants.*.push'           => 'array',
            'variants.*.push.title'     => 'string',
            'variants.*.push.message'   => 'string',
            'variants.*.push.message_template' => 'string|notification_template:' . $campaign_id['campaign_id'],
            'variants.*.push.title_template' => 'string|notification_template:' . $campaign_id['campaign_id'],

            'variants.*.in_app'           => 'array',
            'variants.*.in_app.message'   => 'string',
            'variants.*.in_app.message_template' => 'string|notification_template:' . $campaign_id['campaign_id'],

            'variants.*.sms'           => 'array',
            'variants.*.sms.message'   => 'string',
            'variants.*.sms.message_template' => 'string|notification_template:' . $campaign_id['campaign_id'],
        ];
    }
}
