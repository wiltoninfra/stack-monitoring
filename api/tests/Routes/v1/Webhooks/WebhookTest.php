<?php

namespace Tests\Routes\v1\Webhooks;

use Tests\TestCase;
use Illuminate\Http\Response;

class WebhookTest extends TestCase
{
    public $factory = null;

    public function setUp()
    {
        parent::setUp();
        $this->factory = new WebhookFactory($this->app);
    }

    /**
     * /webhooks [GET]
     *
     * @test
     */
    public function should_return_webhook_collection()
    {
        $this->get('api/v1/webhooks?campaign_id=5d30dace21410401e82483a2');
        $this->seeStatusCode(Response::HTTP_OK);
        $this->seeJsonStructure([
            '*' =>
                [
                    'id',
                    'campaign_id',
                    'variants' => [
                        [
                            "name",
                            "percentage",
                            "target" => [
                                "model"
                            ]
                        ]
                    ],
                ]
        ]);
    }

    /**
     * /webhooks [POST]
     *
     * @test
     */
    public function should_return_ok_when_create_webhook()
    {
        $parameters = $this->factory->getDocument();
        $this->post('api/v1/webhooks', $parameters, []);
        $this->seeStatusCode(Response::HTTP_CREATED);
        $this->factory->delete($this->response->getOriginalContent()->getId());
    }

    /**
     * /webhooks/id [PUT]
     *
     * @test
     */
    public function should_return_ok_when_update_webhook()
    {
        $webhook = $this->factory->create();

        $parameters = [
            "campaign_id" => "5d30dace21410401e82483a2",
            "variants" => [
                [
                    "name" => "variant name",
                    "percentage" => 2,
                    "target" => [
                        "model" => "generic"
                    ]
                ]
            ]
        ];

        $this->put('api/v1/webhooks/' . $webhook->getId(), $parameters, []);

        $this->seeStatusCode(Response::HTTP_ACCEPTED);
        $this->factory->delete($webhook->getId());
    }

    /**
     * /webhook/id [DELETE]
     *
     * @test
     */
    public function should_delete_a_webhook()
    {
        $webhook = $this->factory->create();
        $this->delete('api/v1/webhooks/' . $webhook->getId());
        $this->seeStatusCode(Response::HTTP_NO_CONTENT);
    }
}