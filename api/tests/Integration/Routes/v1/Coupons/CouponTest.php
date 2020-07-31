<?php

namespace Tests\Routes\v1\Coupons;

use Promo\Exceptions\InvalidCampaignException;
use Tests\TestCase;
use Illuminate\Http\Response;

class CouponTest extends TestCase
{
    const PREFIX_V1 = 'api/v1/';

    public $factory = null;

    public function setUp()
    {
        parent::setUp();
        $this->factory = new CouponFactory($this->app);
    }

    /**
     * @group coupon
     */
    public function testShouldCreateCoupon()
    {
        $parameters = $this->factory->getDocument();

        $this->post(route('coupons.create'), $parameters, []);

        $this->seeStatusCode(Response::HTTP_CREATED);

        $this->seeJsonStructure([
            'id',
            'code',
            'active',
            'global',
            'conditions'
        ]);

        unset($parameters['campaign_id']);

        $this->seeJson($parameters);

        $this->factory->delete($this->response->getOriginalContent()->getId());
    }

    /**
     * @group coupon
     */
    public function testShouldNotCreateCouponNonAlphaNumericCode()
    {
        $parameters = $this->factory->getDocument();
        $parameters['code'] = 'NON ALPHA NUMERIC CODE';

        $this->post(route('coupons.create'), $parameters, []);

        $this->seeStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);

        $this->factory->deleteDependencies($parameters);
    }

    /**
     * @group coupon
     */
    public function testShouldNotCreateCouponDuplicatedCode()
    {
        $coupon = $this->factory->create();

        $parameters = $this->factory->getDocument();

        $this->post(route('coupons.create'), $parameters, []);

        $this->seeStatusCode(Response::HTTP_CREATED);

        $this->factory->delete($coupon->getId());
        $this->factory->deleteDependencies($parameters);
    }

    /**
     * @group coupon
     */
    public function testShouldNotCreateCouponOnGlobalyCampaing()
    {
        $parameters = $this->factory->getDocument([
            'campaign' => [
                'global' => true
            ]
        ]);

        $this->post(route('coupons.create'), $parameters, []);

        $this->seeStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);

        $this->factory->deleteDependencies($parameters);
    }

    /**
     * @group coupon
     */
    public function testShouldReturnAllCoupons()
    {
        $coupon = $this->factory->create();

        $this->get(route('coupons.getAll'));

        $this->seeStatusCode(Response::HTTP_OK);

        $this->seeJsonStructure(['*' =>
            [
                'id',
                'code',
                'active',
                'global',
                'conditions'
            ]
        ]);

        $this->factory->delete($coupon->getId());
    }

    /**
     * @group coupon
     */
    public function testShouldUpdateCupon ()
    {
        $coupon = $this->factory->create();

        $parameters = [
            'global' => false,
            'max_associations' => 300,
            'webview_url' => 'http://test.com',
            'conditions' => [
                'first_transaction_only' => true
            ]
        ];

        $this->put(route('coupons.update', ['coupon_id' => $coupon->getId()]), $parameters, []);

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonStructure([
            'id',
            'code',
            'active',
            'global',
            'conditions'
        ]);

        $this->seeJson($parameters);

        $this->factory->delete($coupon->getId());
    }

    /**
     * @group coupon
     */
    public function testShouldNotUpdateCuponCode ()
    {
        $coupon = $this->factory->create();

        $parameters = [
            'code' => 'UPDATED'
        ];

        $this->put(route('coupons.update', ['coupon_id' => $coupon->getId()]), $parameters, []);

        $this->seeStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);

        $this->factory->delete($coupon->getId());
    }

    /**
     * @group coupon
     */
    public function testShouldAssociateConsumersOnCupon ()
    {
        $coupon = $this->factory->create([
            'global'=> false
        ]);

        $parameters = $this->factory->getConsumers();

        $this->patch(route('coupons.associate', ['coupon_id' => $coupon->getId()]), $parameters, []);
        
        $this->seeStatusCode(Response::HTTP_ACCEPTED);
        
        $this->factory->delete($coupon->getId());
    }

    /**
     * @group coupon
     */
    public function testShouldNotAssociateConsumersOnGlobalyCupon ()
    {
        $coupon = $this->factory->create([
            'global'=> true
        ]);

        $parameters = $this->factory->getConsumers();

        $this->patch(route('coupons.associate', ['coupon_id' => $coupon->getId()]), $parameters, []);
        
        $this->seeStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        
        $this->factory->delete($coupon->getId());
    }

    /**
     * @group coupon
     */
    public function testShouldNotAssociateMoreThen50ConsumersOnCuponAtOnce ()
    {
        $coupon = $this->factory->create([
            'global'=> false
        ]);

        $parameters = $this->factory->getConsumers(51);

        $this->patch(route('coupons.associate', ['coupon_id' => $coupon->getId()]), $parameters, []);
        
        $this->seeStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        
        $this->factory->delete($coupon->getId());
    }

    /**
     * @group coupon
     */
    public function testShouldUpdateCuponStatus ()
    {
        $coupon = $this->factory->create();

        $parameters = [
            'active' => false
        ];

        $this->patch(route('coupons.updateStatus', ['coupon_id' => $coupon->getId()]), $parameters, []);
        
        $this->seeStatusCode(Response::HTTP_ACCEPTED);
        
        $this->factory->delete($coupon->getId());
    }
    
    /**
     * @group coupon
     */
    public function testShouldNotUpdateInvalidCuponStatus ()
    {
        $coupon = $this->factory->create();

        $this->factory->invalidateCampaign($coupon->getCampaign()->getId());

        $parameters = [
            'active' => true
        ];

        $this->patch(route('coupons.updateStatus', ['coupon_id' => $coupon->getId()]), $parameters, []);
        
        $this->seeStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        
        $this->factory->delete($coupon->getId());
    }
}

