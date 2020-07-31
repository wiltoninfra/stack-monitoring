<?php

namespace Tests\Routes\v1\Banners;

use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Http\Response;
use Tests\Routes\v1\Banners\BannerFactory;

class BannerTest extends TestCase
{
    public $factory = null;

    public function setUp()
    {
        parent::setUp();
        $this->factory = new BannerFactory($this->app);
    }

    /**
     * @group banner
     */
    public function testShouldCreateBannerGlobaly()
    {
        $parameters = $this->factory->getDocument([
            'global' => true
        ]);

        $this->post(route('banners.create'), $parameters, []);

        $this->seeStatusCode(Response::HTTP_CREATED);

        $this->seeJsonStructure([
            'id',
            'active',
            'name',
            'global',
            'image_url',
            'ios_min_version',
            'android_min_version',
            'target',
            'info',
            'start_date',
            'end_date'
        ]);

        unset($parameters['image']);

        $this->seeJson($parameters);

        $this->factory->delete($this->response->getOriginalContent()->getId());
    }

    /**
     * @group banner
     */
    public function testShouldCreateBannerWithCampaign()
    {
        $parameters = $this->factory->getDocument([
            'global' => false
        ]);

        $this->post(route('banners.create'), $parameters, []);

        $this->seeStatusCode(Response::HTTP_CREATED);

        $this->seeJsonStructure([
            'id',
            'active',
            'name',
            'global',
            'image_url',
            'ios_min_version',
            'android_min_version',
            'target',
            'info',
            'start_date',
            'end_date'
        ]);

        unset($parameters['image']);
        unset($parameters['campaign_id']);

        $this->seeJson($parameters);

        $this->factory->delete($this->response->getOriginalContent()->getId());
    }


    /**
     * @group banner
     */
    public function testShouldReturnAllBanners()
    {
        $banner = $this->factory->create();

        $this->get(route('banners.getAll'));

        $this->seeStatusCode(Response::HTTP_OK);

        $this->seeJsonStructure(['*' =>
            [
                'id',
                'active',
                'name',
                'global',
                'image_url',
                'ios_min_version',
                'android_min_version',
                'target',
                'info',
                'start_date',
                'end_date'
            ]
        ]);

        $this->factory->delete($banner->getId());
    }

    /**
     * @group banner
     */
    public function testShouldReturnBannerById()
    {
        $banner = $this->factory->create();

        $this->get(route('banners.get', ['banner_id' => $banner->getId()]));

        $this->seeStatusCode(Response::HTTP_OK);

        $this->seeJsonStructure([
            'id',
            'active',
            'name',
            'global',
            'image_url',
            'ios_min_version',
            'android_min_version',
            'target',
            'info',
            'start_date',
            'end_date'
        ]);

        $this->factory->delete($banner->getId());
    }

    /**
     * @group banner
     */
    public function testShouldNotReturnBannerNotExists()
    {
        $banner_id = '23123reqwdqqwe121323';

        $this->get(route('banners.get', ['banner_id' => $banner_id]));

        $this->seeStatusCode(Response::HTTP_NOT_FOUND);
    }

    /**
     * @group banner
     */
    public function testShouldUpdateBanner ()
    {
        $banner = $this->factory->create();

        $now = Carbon::now();
        $now_plus_2_days = $now->copy()->addDays(2);

        $parameters = [
            'name' => 'UPDATED BANNER',
            "start_date" => $now->toIso8601String(),
            "end_date" => $now_plus_2_days->toIso8601String(),
            "campaign_id" => $banner->getCampaign()->getId(),
            "target"=> [
                "name"=> "financial_service",
                "param"=> "boleto"
            ],
            "ios_min_version"=> "10.4",
            "android_min_version"=> "10.4",
            "info" => [
                "title" => "UPDATED TITLE",
                "description" => "UPDATED DESCRIPTION"
            ]
        ];

        $this->put(route('banners.update', ['banner_id' => $banner->getId()]), $parameters, []);

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonStructure([
            'id',
            'active',
            'name',
            'global',
            'image_url',
            'ios_min_version',
            'android_min_version',
            'target',
            'info',
            'start_date',
            'end_date'
        ]);

        unset($parameters['campaign_id']);

        $this->seeJson($parameters);

        $this->factory->delete($banner->getId());
    }

    /**
     * /@group banner
     */
    public function testShouldDeleteBanner()
    {
        $banner = $this->factory->create();

        $this->delete(route('banners.delete', ['banner_id' =>  $banner->getId()]));

        $this->seeStatusCode(Response::HTTP_NO_CONTENT);

        $this->factory->delete($banner->getId());
    }

    /**
     * @group banner
     */
    public function testShouldNotDeleteBannerNotExists()
    {
        $banner_id = '000000000000000000';

        $this->delete(route('banners.delete', ['banner_id' =>  $banner_id]));

        $this->seeStatusCode(Response::HTTP_NOT_FOUND);
    }

    /**
     * @group banner
     */
    public function testShouldUpdateBannerStatus ()
    {
        $banner = $this->factory->create();

        $parameters = [
            'active' => false
        ];

        $this->patch(route('banners.status', ['banner_id' =>  $banner->getId()]), $parameters, []);
        
        $this->seeStatusCode(Response::HTTP_ACCEPTED);
        
        $this->factory->delete($banner->getId());
    }

    /**
     * @group banner
     */
    public function testShouldNotUpdateBannerStatus ()
    {
        $banner = $this->factory->create();

        $parameters = [
            'active' => null
        ];

        $this->patch(route('banners.status', ['banner_id' =>  $banner->getId()]), $parameters, []);
        
        $this->seeStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        
        $this->factory->delete($banner->getId());
    }

    /**
     * @group banner
     */
    public function testShouldPublishBanner ()
    {
        $banner = $this->factory->create();

        $parameters = [
            'ids' => [
                $banner->getId()
            ]
        ];

        $this->patch(route('banners.publish'), $parameters, []);
        
        $this->seeStatusCode(Response::HTTP_ACCEPTED);
        
        $this->factory->delete($banner->getId());
    }

    /**
     * @group banner
     */
    public function testShouldNotPublishBannerEmpty ()
    {
        $parameters = [];

        $this->patch(route('banners.publish'), $parameters, []);
        
        $this->seeStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

}
