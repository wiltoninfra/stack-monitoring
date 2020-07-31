<?php

return [

    'digital_goods' => env('PICPAY_DIGITALGOODS_URL'),
    'core_address'  => env('PICPAY_CORE_URL'),
    'core_token'    => env('PICPAY_CORE_TOKEN', 'abc'),
    'digital_account' => env('PICPAY_DIGITAL_ACCOUNT_URL'),
    'reward_api' => env('PICPAY_REWARD_API_URL', "http://192.168.15.62:9046/api/v1"), // Adicionado somente para executar campanha de verao 2019
    'acceleration_campaign_id' => env('PICPAY_REWARD_ACCELERATION_CAMPAIGN_ID', '5deeb2b7083896cd85225f7a'),
];

