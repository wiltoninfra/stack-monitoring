<?php

namespace Tests\Routes\v1\Cashback;

use Tests\TestCase;
use Illuminate\Http\Response;

class CashfrontTest extends TestCase
{

    /**
     * @group cashfront
     */
    public function testShouldReturnCashfrontByCampaignValid()
    {
        $payloadCampaign = [
            "name" => "Campanha cashfront test",
            "description" => "Uma campanha muito legal.",
            "type" => "cashfront",
            "global" => true,
            "consumers" => [
                1
            ],
            "sellers" => [
                3
            ],
            "except_sellers" => [
                8
            ],
            "sellers_types" => [
                "membership"
            ],
            "webhook_url" => "http://webhookpromo.aws.picpay.endereco-grande.com",
            "webview_url" => "http://cdn.aws.picpay.endereco-grande.com/termos.html",
            "duration" => [
                "fixed" => true,
                "start_date" => "2018-09-28T19:05:47.904Z",
                "end_date" => "2027-09-30T19:05:47.904Z",
                "hours" => 1,
                "days" => 3,
                "weeks" => 2,
                "months" => 6
            ],
            "transaction" => [
                "type" => "mixed",
                "payment_methods" => [
                    "cashfront"
                ]
            ],
            "cashback" => [
                "percentage" => 2.2,
                "max_value" => 5.5,
                "paid_by" => "picpay"
            ],
            "cashfront" => [
                "percentage" => 10,
                "max_value" => 100
             ],
            "instantcash" => [
                "value" => 10
            ],
            "deposit" => [
                "first_deposit_only" => false
            ]
        ];
        $this->post(route('campaigns.create'), $payloadCampaign);
        $this->seeStatusCode(201);

        $parameters = [
            'deposit' => [
                'id' => '123456',
                'recharge_method' => 'conta-corrente',
                'total' => 100.00,
                'first_deposit' => false
            ]
        ];

        $this->patch(route('consumers.cashfront.applyCashfront', ['consumer_id' => 1]), $parameters, []);

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJson([
            'cashfront' => 10,
        ]);
    }

    /**
     * @group cashfront
     */
    public function testShouldReturnCashfrontToFirstDeposit()
    {
        $payloadCampaign = [
            "name" => "Campanha cashfront primeiro deposito",
            "description" => "Uma campanha muito legal.",
            "type" => "cashfront",
            "global" => true,
            "consumers" => [
                2
            ],
            "sellers" => [
                3
            ],
            "except_sellers" => [
                8
            ],
            "sellers_types" => [
                "membership"
            ],
            "webhook_url" => "http://webhookpromo.aws.picpay.endereco-grande.com",
            "webview_url" => "http://cdn.aws.picpay.endereco-grande.com/termos.html",
            "duration" => [
                "fixed" => true,
                "start_date" => "2018-09-28T19:05:47.904Z",
                "end_date" => "2027-09-30T19:05:47.904Z",
                "hours" => 1,
                "days" => 3,
                "weeks" => 2,
                "months" => 6
            ],
            "transaction" => [
                "type" => "mixed",
                "payment_methods" => [
                    "cashfront"
                ]
            ],
            "cashback" => [
                "percentage" => 2.2,
                "max_value" => 5.5,
                "paid_by" => "picpay"
            ],
            "cashfront" => [
                "percentage" => 10,
                "max_value" => 100
            ],
            "instantcash" => [
                "value" => 10
            ],
            "deposit" => [
                "first_deposit_only" => true
            ]
        ];
        $this->post(route('campaigns.create'), $payloadCampaign);
        $this->seeStatusCode(201);

        $parameters = [
            'deposit' => [
                'id' => '123456',
                'recharge_method' => 'conta-corrente',
                'total' => 100.00,
                'first_deposit' => true
            ]
        ];

        $this->patch(route('consumers.cashfront.applyCashfront', ['consumer_id' => 2]), $parameters, []);

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJson([
            'cashfront' => 10,
        ]);
    }

    /**
     * @group cashfront
     */
    public function testCashfrontNotShouldExceedValueConfiguredInTheCampaign()
    {
        $parameters = [
            'deposit' => [
                'id' => '123456',
                'recharge_method' => 'conta-corrente',
                'total' => 9000,
                'first_deposit' => false
            ]
        ];

        $this->patch(route('consumers.cashfront.applyCashfront', ['consumer_id' => 1]), $parameters, []);

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJson([
            'cashfront' => 10,
        ]);
    }
}
