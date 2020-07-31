<?php


namespace Tests\Unit;


use Tests\TestCase;

class AvailableCampaignsTest extends TestCase
{
    /** @test */
    public function shouldReturnCollectionOfAvailableCampaings()
    {
//        $consumerId = 8582661;
        $consumerId = rand(1,5);
        $this->get("api/v1/campaigns/app-availables?consumer_id={$consumerId}");
        $this->assertResponseOk();
        $this->seeJsonStructure([
            "*" => [
                'id',
                'name',
                'description',
                'type',
                'active',
                'global',
                'consumer_as_seller',
                'consumers',
                'global_seller',
                'sellers',
                'except_sellers',
                'sellers_types',
                'created_at',
                'duration',
                'transaction',
                'deposit',
                'cashback',
                'limits',
                'app',
                'enabled'
            ]
        ]);
    }
}
