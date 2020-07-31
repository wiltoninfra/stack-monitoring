<?php

namespace Promo\Rules;

use Promo\Services\CampaignService;
use Illuminate\Support\Facades\Validator;

class NotificationMessage
{

    public static function validate()
    {

        Validator::extend('notification_template', function ($attribute, $value, $parameters, $validator) {

            $campaign = app(CampaignService::class);
            preg_match_all('%{{campaign.(.*?)}}%i', $value, $methods, PREG_PATTERN_ORDER);
            $notificationData = collect($campaign->notificationTemplateRuler($parameters[0]));

            $notificationDataKeys = $notificationData->keys()->map(function ($value, $key){
                return sprintf("%s%s%s", "{{", $value,"}}");
            })->toArray();

            $methods = collect($methods[0]);
            $methods->each(function ($value, $key) use ($notificationDataKeys) {
                if(!in_array($value, $notificationDataKeys)){
                    return false;
                }
            });

            return true;
            
        }, "Verifique os campos");
    }

}