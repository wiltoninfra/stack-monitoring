<?php
/** @var $router Laravel\Lumen\Routing\Router  */

$router->get('/', function () {
    return redirect('api/documentation');
});


$router->group(['prefix' => 'api/v1'], function () use ($router) {

    //kainan (retirar antes do deploy em prod)
    $router->get('/lambda-dispatch', [
        'uses' =>'CampaignController@testDispatch'
    ]);
    $router->get('/lambda-notification/{webhook_id}', [
        'uses' =>'CampaignController@testNotification'
    ]);
    //kainan (retirar antes do deploy em prod)

    // Endpoints relacionados a campanhas
    $router->group(['prefix' => 'campaigns', 'middleware'=> 'request'], function () use ($router) {
        $router->get('/types', [
            'uses' =>'CampaignController@getTypes',
            'as' => 'campaigns.getTypes'
        ]);
        $router->get('/app-availables', [
            'uses' => 'AppAvailableCampaignController@index',
            'as' => 'campaigns.index'
        ]);
        $router->post('/', [
            'uses' => 'CampaignController@create',
            'as' => 'campaigns.create'
        ]);
        $router->get('/{campaign_id}', [
            'uses' => 'CampaignController@get',
            'as' => 'campaign.get'
        ]);
        $router->get('/{campaign_id}/treated', [
            'uses' => 'CampaignController@getTreated',
            'as' => 'campaign.getTreated'
        ]);
        $router->get('/{campaign_id}/stats', [
            'uses' => 'CampaignController@getStats',
            'as' => 'campaign.getStats'
        ]);
        $router->put('/{campaign_id}', [
            'uses' => 'CampaignController@update',
            'as' => 'campaign.update'
        ]);
        $router->delete('/{campaign_id}', [
            'uses' => 'CampaignController@delete',
            'as' => 'campaign.delete'
        ]);
        $router->patch('/{campaign_id}/status', [
            'uses' => 'CampaignController@updateStatus',
            'as' => 'campaign.updateStatus'
        ]);
        $router->patch('/{campaign_id}/add-consumer/{consumer_id}', [
            'uses' => 'CampaignController@addConsumer',
            'as' => 'campaign.addConsumer'
        ]);
        $router->patch('/{campaign_id}/removeSellers', [
            'uses' => 'CampaignController@removeSellers',
            'as' => 'campaign.removeSellers'
        ]);
        $router->patch('/{campaign_id}/sellers', [
            'uses' => 'CampaignController@updateSellers',
            'as' => 'campaign.updateSellers'
        ]);
        $router->patch('/{campaign_id}/association', [
            'uses' => 'CampaignController@associate',
            'as' => 'campaign.associate'
        ]);
        $router->delete('/{campaign_id}/association', [
            'uses' => 'CampaignController@disassociate',
            'as' => 'campaign.disassociate'
        ]);
        $router->patch('/{campaign_id}/coupon', [
            'uses' => 'CampaignController@applyCoupon',
            'as' => 'campaign.applyCoupon'
        ]);
        $router->patch('/{campaign_id}/tags', [
            'uses' => 'CampaignController@replaceTags',
            'as' => 'campaign.replaceTags'
        ]);
        $router->patch('/{campaign_id}/permissions', [
            'uses' => 'CampaignController@applyPermissions',
            'as' => 'campaign.applyPermissions'
        ]);
        $router->patch('/{campaign_id}/publish', [
            'uses' => 'CampaignController@publish',
            'as' => 'campaign.publish'
        ]);
        $router->get('/{campaign_id}/notification-template-ruler', [
            'uses' => 'CampaignController@notificationTemplateRuler',
            'as' => 'campaign.notificationTemplateRuler'
        ]);
        $router->post('/{campaign_id}/checkSellers', [
            'uses' => 'CampaignController@checkSellers',
            'as' => 'campaign.checkSellers'
        ]);

        $router->group(['middleware' => ['pagination']], function () use ($router) {
            $router->get('/', [
                'uses' => 'CampaignController@getAll',
                'as' => 'campaign.getAll'
            ]);
        });
    });

    // Endpoints relacionados a usuários,
    $router->group(['prefix' => 'consumers', 'middleware'=> 'request'], function () use ($router) {

        $router->post('/{consumer_id}/blacklist', [
            'uses' => 'BlackListedConsumerController@create',
            'as' => 'consumers.blacklist.create'
        ]);
        $router->put('/{consumer_id}/blacklist', [
            'uses' => 'BlackListedConsumerController@update',
            'as' => 'consumers.blacklist.update'
        ]);
        $router->get('/{consumer_id}/blacklist', [
            'uses' => 'BlackListedConsumerController@findByConsumerId',
            'as' => 'consumers.blacklist.findByConsumerId'
        ]);

        $router->get('/{consumer_id}/banners', [
            'uses' => 'ConsumerController@getBanners',
            'as' => 'consumers.banners.getBanners'
        ]);

        $router->post('/cashback', [
            'uses' => 'ConsumerController@applyCashback',
            'as' => 'consumers.cashback.applyCashback'
        ]);

        $router->patch('/{consumer_id}/cashfront', [
            'uses' => 'ConsumerController@applyCashfront',
            'as' => 'consumers.cashfront.applyCashfront'
        ]);

        $router->patch('/{consumer_id}/coupon', [
            'uses' => 'ConsumerController@applyCoupon',
            'as' => 'consumers.coupon.applyCoupon'
        ]);
        $router->patch('/{consumer_id}/transactions/{transaction_type}/{transaction_id}/undo', [
            'uses' => 'ConsumerController@undoCashback',
            'as' => 'consumers.transactions.undoCashback'
        ]);
        $router->get('/{consumer_id}/transactions/{transaction_type}/{transaction_id}', [
            'uses' => 'ConsumerController@getTransaction',
            'as' => 'consumers.transactions.getTransaction'
        ]);

        $router->group(['middleware' => ['pagination']], function () use ($router) {
            $router->get('/{consumer_id}/transactions', [
                'uses' => 'ConsumerController@getAllTransactions',
                'as' => 'consumers.transactions.getAllTransactions'
            ]);
            $router->get('/{consumer_id}/campaigns/associations', [
                'uses' => 'ConsumerController@getAssociations',
                'as' => 'consumers.campaigns.getAssociations'
            ]);
        });

        $router->group(['prefix' => 'batch'], function () use ($router) {
            $router->post('/payments', [
                'uses' => 'ConsumerController@processBatchPayments',
                'as' => 'consumers.batch.processBatchPayments'
            ]);
        });
    });

    // Endpoints relacionados a cupons
    $router->group(['prefix' => 'coupons', 'middleware'=> 'request'], function () use ($router) {
        $router->post('/', [
            'uses' => 'CouponController@create',
            'as' => 'coupons.create'
        ]);
        $router->put('/{coupon_id}', [
            'uses' => 'CouponController@update',
            'as' => 'coupons.update'
        ]);

        $router->group(['middleware' => ['pagination']], function () use ($router) {
            $router->get('/', [
                'uses' => 'CouponController@getAll',
                'as' => 'coupons.getAll'
            ]);
        });

        $router->patch('/{banner_id}/status', [
            'uses' => 'CouponController@updateStatus',
            'as' => 'coupons.updateStatus'
        ]);
        $router->patch('/{coupon_id}/association', [
            'uses' => 'CouponController@associate',
            'as' => 'coupons.associate'
        ]);
    });

    // Endpoints relacionados a banners
    $router->group(['prefix' => 'banners', 'middleware'=> 'request'], function () use ($router) {
        $router->post('', [
            'uses' => 'BannerController@create',
            'as' => 'banners.create'
        ]);
        $router->patch('/publish', [
            'uses' => 'BannerController@publish',
            'as' => 'banners.publish'
        ]);

        $router->group(['middleware' => ['pagination']], function () use ($router) {
            $router->get('/', [
                'uses' => 'BannerController@getAll',
                'as' => 'banners.getAll'
            ]);
        });

        $router->get('/{banner_id}', [
            'uses' => 'BannerController@get',
            'as' => 'banners.get'
        ]);
        $router->put('/{banner_id}', [
            'uses' => 'BannerController@update',
            'as' => 'banners.update'
        ]);
        $router->delete('/{banner_id}', [
            'uses' => 'BannerController@delete',
            'as' => 'banners.delete'
        ]);
        $router->patch('/{banner_id}/status', [
            'uses' => 'BannerController@updateStatus',
            'as' => 'banners.status'
        ]);
    });

    // Endpoints de tags
    $router->group(['prefix' => 'tags', 'middleware'=> 'request'], function () use ($router) {
        $router->post('/', [
            'uses' => 'TagController@create',
            'as' => 'tags.create'
        ]);
        $router->get('/', [
            'uses' => 'TagController@getAll',
            'as' => 'tags.getAll'
        ]);
        $router->put('/{tag_id}', [
            'uses' => 'TagController@update',
            'as' => 'tags.update'
        ]);
        $router->delete('/{tag_id}', [
            'uses' => 'TagController@delete',
            'as' => 'tags.delete'
        ]);
    });

    $router->group(['prefix' => 'transactions', 'middleware' => 'request'], function () use ($router) {
        $router->post('/info', [
            'uses' => 'TransactionController@transactionInfo',
            'as' => 'transactions.transactionInfo'
        ]);
    });

    // Endpoints do webhook
    $router->group(['prefix' => 'webhooks', 'middleware'=> 'request'], function () use ($router) {
        $router->get('/{webhook_id}', [
            'uses' => 'WebhookController@get',
            'as' => 'webhooks.get'
        ]);
        $router->get('/', [
            'uses' => 'WebhookController@getAll',
            'as' => 'webhooks.getAll'
        ]);
        $router->post('/', [
            'uses' => 'WebhookController@create',
            'as' => 'webhooks.create'
        ]);
        $router->put('/{webhook_id}', [
            'uses' => 'WebhookController@update',
            'as' => 'webhooks.update'
        ]);
        $router->delete('/{webhook_id}', [
            'uses' => 'WebhookController@delete',
            'as' => 'webhooks.delete'
        ]);
    });

    // Endpoint Sellers
    $router->group(['prefix' => 'seller', 'middleware' => ['pagination']], function () use ($router) {
        $router->get('/{sellerId}/campaigns', [
            'uses' => 'CampaignController@getCampaignsBySeller',
            'as' => 'seller.campaigns'
        ]);
    });
});
