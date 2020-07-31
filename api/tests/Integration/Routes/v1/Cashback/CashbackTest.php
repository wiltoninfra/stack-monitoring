<?php

namespace Tests\Routes\v1\Cashback;

use Carbon\Carbon;
use Faker\Factory;
use PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager;
use PicPay\Common\Services\Enums\SellerTypeEnum;
use Promo\Documents\Campaign;
use Promo\Http\Resources\CampaignCollectionResource;
use Promo\Services\CampaignService;
use Promo\Services\CampaignVersionService;
use Tests\Routes\v1\Campaigns\CampaignFactory;
use Tests\TestCase;
use Illuminate\Http\Response;
use Promo\Documents\Enums\CampaignTypeEnum;
use Promo\Documents\Enums\TransactionTypeEnum;

class CashbackTest extends TestCase
{
    private $factory;
    private $faker;

    const PREFIX_V1 = 'api/v1/';
    const PLUS_SECONDS_CAMPAIGN_END_DATE = 60;

    public function setUp()
    {
        parent::setUp();
        $this->factory = new CampaignFactory($this->app);
        $this->faker = Factory::create();
    }

    /**
     * @group cashback
     */
    public function testShouldExecuteCashbackP2PCreditCard()
    {
        $now = Carbon::now();
        $nowPlusSeconds = $now->copy()->addSecond(self::PLUS_SECONDS_CAMPAIGN_END_DATE);

        $campaign = $this->factory->create([
            'name' => 'Campanha Teste P2P Card',
            'global' => true,
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $nowPlusSeconds->toIso8601String()
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::P2P,
                'max_transactions' => 1,
                'max_transactions_per_consumer' => 1,
                'payment_methods' => ['credit-card'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 10,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ]
        ]);

        $parameters = [
            'transaction' => [
                'consumer_id' => $this->faker->randomNumber(),
                'message' => '#VaiBrasil',
                'id' => 1,
                'consumer_id_payee' => 111,
                'transaction_date' => $now->toIso8601String(),
                'type' => 'p2p',
                'credit_card' => 100.0,
                'wallet' => 0.0,
                'total' => 100.00,
                'installments' => 1
            ]
        ];

        $this->post(
            route('consumers.cashback.applyCashback'),
            $parameters,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonContains([
            'cashback' => 10,
        ]);

        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
    }

    /**
     * @group cashback
     */
    public function testShouldExecuteCashbackP2PWallet()
    {
        $now = Carbon::now();
        $nowPlusSeconds = $now->copy()->addSecond(self::PLUS_SECONDS_CAMPAIGN_END_DATE);

        $campaign = $this->factory->create([
            'name' => 'Campanha Teste P2P Wallet',
            'global' => true,
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $nowPlusSeconds->toIso8601String()
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::P2P,
                'max_transactions' => 1,
                'max_transactions_per_consumer' => 1,
                'payment_methods' => ['wallet'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 20,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ]
        ]);

        $parameters = [
            'transaction' => [
                'message' => '#VaiBrasil',
                'consumer_id' => $this->faker->randomNumber(),
                'id' => 1,
                'consumer_id_payee' => 222,
                'transaction_date' => $now->toIso8601String(),
                'type' => 'p2p',
                'credit_card' => 0,
                'wallet' => 200.0,
                'total' => 200.00,
                'installments' => 1
            ]
        ];

        $this->post(
            route('consumers.cashback.applyCashback'),
            $parameters,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonContains([
            'cashback' => 20,
        ]);

        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
    }

    /**
     * @group cashback
     */
    public function testShouldExecuteCashbackP2PMixed()
    {
        $now = Carbon::now();
        $nowPlusSeconds = $now->copy()->addSecond(self::PLUS_SECONDS_CAMPAIGN_END_DATE);

        $campaign = $this->factory->create([
            'name' => 'Campanha Teste P2P Mixed',
            'global' => true,
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $nowPlusSeconds->toIso8601String()
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::P2P,
                'max_transactions' => 1,
                'max_transactions_per_consumer' => 1,
                'payment_methods' => ['wallet', 'credit-card'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 10,
                'max_value' => 1000,
                'paid_by' => 'picpay',
            ]
        ]);

        $parameters = [
            'transaction' => [
                'message' => '#VaiBrasil',
                'consumer_id' => $this->faker->randomNumber(),
                'id' => 1,
                'consumer_id_payee' => 222,
                'transaction_date' => $now->toIso8601String(),
                'type' => 'p2p',
                'credit_card' => 200.0,
                'wallet' => 200.0,
                'total' => 400.00,
                'installments' => 1
            ]
        ];

        $this->post(
            route('consumers.cashback.applyCashback'),
            $parameters,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonContains([
            'cashback' => 40,
        ]);

        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
    }

    /**
     * @group cashback
     */
    public function testShouldExecuteCashbackPAVMixed()
    {
        $now = Carbon::now();
        $nowPlusSeconds = $now->copy()->addSecond(self::PLUS_SECONDS_CAMPAIGN_END_DATE);

        $campaign = $this->factory->create([
            'name' => 'Campanha Teste PAV Mixed',
            'global' => true,
            'sellers_types' => ['pav'],
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $nowPlusSeconds->toIso8601String()
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::PAV,
                'max_transactions' => 1,
                'max_transactions_per_consumer' => 1,
                'payment_methods' => ['wallet', 'credit-card'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 10,
                'max_value' => 1000,
                'paid_by' => 'picpay',
            ]
        ]);

        $parameters = [
            'transaction' => [
                'consumer_id' => $this->faker->randomNumber(),
                'id' => 1,
                'type' => 'pav',
                'seller_id' => 1,
                'seller_type' => SellerTypeEnum::PAV,
                'transaction_date' => $now->toIso8601String(),
                'credit_card' => 200.0,
                'wallet' => 200.0,
                'total' => 400,
                'installments' => 1
            ]
        ];

        $this->post(
            route('consumers.cashback.applyCashback'),
            $parameters,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJson([
            'cashback' => 40,
        ]);

        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
    }

    /**
     * @group cashback
     */
    public function testShouldExecuteCashbackPAVMixedWithInstallmets()
    {
        $now = Carbon::now();
        $nowPlusSeconds = $now->copy()->addSecond(self::PLUS_SECONDS_CAMPAIGN_END_DATE);

        $campaign = $this->factory->create([
            'name' => 'Campanha Teste PAV Mixed',
            'global' => true,
            'sellers_types' => ['pav'],
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $nowPlusSeconds->toIso8601String()
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::PAV,
                'max_transactions' => 1,
                'max_transactions_per_consumer' => 1,
                'payment_methods' => ['wallet', 'credit-card'],
                'min_installments' => 10,
                'conditions' => [
                    'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 10,
                'max_value' => 1000,
                'paid_by' => 'picpay',
            ]
        ]);

        $parameters = [
            'transaction' => [
                'consumer_id' => $this->faker->randomNumber(),
                'id' => 1,
                'type' => 'pav',
                'seller_id' => 1,
                'seller_type' => SellerTypeEnum::PAV,
                'transaction_date' => $now->toIso8601String(),
                'credit_card' => 400.0,
                'wallet' => 0,
                'total' => 400,
                'installments' => 100
            ]
        ];

        $this->post(
            route('consumers.cashback.applyCashback'),
            $parameters,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJson([
            'cashback' => 40,
        ]);

        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
    }

    /**
     * @group cashback
     * @group agora
     */
//    public function testShouldApplyCashBackFirstPayeeReceivedPaymentOnlyWhenTransactionRetroative()
//    {
//        $now = Carbon::now();
//        $nowPlusSeconds = $now->copy()->addSecond(self::PLUS_SECONDS_CAMPAIGN_END_DATE);
//
//        $campaign = $this->factory->create([
//            'name' => 'Campanha Teste P2P Specific branch skip',
//            'global' => true,
//            'duration' => [
//                'fixed' => true,
//                'start_date' => $now->toIso8601String(),
//                'end_date' => $nowPlusSeconds->toIso8601String()
//            ],
//            'transaction' => [
//                'type' => TransactionTypeEnum::P2P,
//                'first_payee_received_payment_only' => true,
//                'payment_methods' => ['credit-card'],
//                'credit_card_brands' => ['mastercard'],
//                'min_installments' => 1,
//                'conditions' => [
//                    'in_cash', 'installments'
//                ]
//            ],
//            'cashback' => [
//                'percentage' => 10,
//                'max_value' => 100,
//                'paid_by' => 'picpay',
//            ]
//        ]);
//
//        $a = app(CampaignVersionService::class);
//
//        $a->create([
//            'permanenceStartDate' => Carbon::now()->addMinute(-20)->toISOString(),
//            'permanenceEndDate' => Carbon::now()->toISOString(),
//            'campaign' => collect(new CampaignCollectionResource($campaign))
//        ]);
//
//
//        $parameters = [
//            'transaction' => [
//                'message' => '#VaiBrasil',
//                'consumer_id' => $this->faker->randomNumber(),
//                'id' => 1,
//                'consumer_id_payee' => 1117,
//                'transaction_date' => $now->addMinute(-10)->toIso8601String(),
//                'credit_card_brand' => 'mastercard',
//                'type' => 'p2p',
//                'credit_card' => 100.0,
//                'wallet' => 0.0,
//                'total' => 100.00,
//                'installments' => 1,
//                'first_payee_received_payment' => true
//            ]
//        ];
//
//        $this->post(
//            route('consumers.cashback.applyCashback'),
//            $parameters,
//            []
//        );
//
//        $this->seeStatusCode(Response::HTTP_ACCEPTED);
//
//        $this->seeJsonContains([
//            'cashback' => 10
//        ]);
//
//        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
//    }

    /**
     * @group cashback
     */
    public function testShouldAssociateConsumerToCampaign()
    {
        $now = Carbon::now();
        $nowPlusSeconds = $now->copy()->addSecond(self::PLUS_SECONDS_CAMPAIGN_END_DATE);

        $campaign = $this->factory->create([
            'name' => 'Campanha Teste Associate',
            'global' => false,
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $nowPlusSeconds->toIso8601String()
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::P2P,
                'max_transactions' => 1,
                'max_transactions_per_consumer' => 1,
                'payment_methods' => ['wallet', 'credit-card'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 10,
                'max_value' => 1000,
                'paid_by' => 'picpay',
            ]
        ]);

        $parameters = [
            'consumers' => [
                $this->faker->randomNumber(),
            ]
        ];

        $this->patch(
            route('campaign.associate', ['campaign_id' => $campaign->getId()]),
            $parameters,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
    }

    /**
     * @group cashback
     */
    public function testShouldNotApplyCashbackWhenCampaignNotExists()
    {
        $parameters = [
            'transaction' => [
                'consumer_id' => $this->faker->randomNumber(),
                'transaction_date' => Carbon::now()->toIso8601String(),
                'id' => 2,
                'type' => 'pav',
                'seller_id' => 20,
                'seller_type' => 'pav',
                'credit_card' => 100,
                'wallet' => 100,
                'total' => 200,
                'installments' => 1
            ]
        ];

        $this->post(
            route('consumers.cashback.applyCashback'),
            $parameters,
            []
        );
        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJson([
            'cashback' => 0,
        ]);
    }

    /**
     * @group cashback
     * @group skip
     */
    public function testShouldSkipCampaignPaymentCreditCardZero()
    {
        $now = Carbon::now();
        $nowPlusSeconds = $now->copy()->addSecond(self::PLUS_SECONDS_CAMPAIGN_END_DATE);

        $campaign = $this->factory->create([
            'name' => 'Campanha Teste P2P Card Skip',
            'global' => true,
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $nowPlusSeconds->toIso8601String()
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::P2P,
                'max_transactions' => 1,
                'max_transactions_per_consumer' => 1,
                'payment_methods' => ['credit-card'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 10,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ]
        ]);

        $parameters = [
            'transaction' => [
                'message' => '#VaiBrasil',
                'consumer_id' => $this->faker->randomNumber(),
                'id' => 1,
                'consumer_id_payee' => 111,
                'transaction_date' => $now->toIso8601String(),
                'type' => 'p2p',
                'credit_card' => 0,
                'wallet' => 100.0,
                'total' => 100.00,
                'installments' => 1
            ]
        ];

        $this->post(
            route('consumers.cashback.applyCashback'),
            $parameters,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonContains([
            'cashback' => 0,
            'campaign' => null
        ]);

        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
    }

    /**
     * @group cashback
     * @group skip
     */
    public function testShouldSkipCampaignPaymentWalletZero()
    {
        $now = Carbon::now();
        $nowPlusSeconds = $now->copy()->addSecond(self::PLUS_SECONDS_CAMPAIGN_END_DATE);

        $campaign = $this->factory->create([
            'name' => 'Campanha Teste P2P Wallet Skip',
            'global' => true,
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $nowPlusSeconds->toIso8601String()
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::P2P,
                'max_transactions' => 1,
                'max_transactions_per_consumer' => 1,
                'payment_methods' => ['credit-card'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 10,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ]
        ]);

        $parameters = [
            'transaction' => [
                'message' => '#VaiBrasil',
                'consumer_id' => $this->faker->randomNumber(),
                'id' => 1,
                'consumer_id_payee' => 111,
                'transaction_date' => $now->toIso8601String(),
                'type' => 'p2p',
                'credit_card' => 0,
                'wallet' => 100.0,
                'total' => 100.00,
                'installments' => 1
            ]
        ];

        $this->post(
            route('consumers.cashback.applyCashback'),
            $parameters,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonContains([
            'cashback' => 0,
            'campaign' => null
        ]);
        
        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
    }

    /**
     * @group cashback
     * @group skip
     */
    public function testShouldSkipCampaignWithSpecificCreditCardBrand()
    {
        $now = Carbon::now();
        $nowPlusSeconds = $now->copy()->addSecond(self::PLUS_SECONDS_CAMPAIGN_END_DATE);

        $campaign = $this->factory->create([
            'name' => 'Campanha Teste P2P Specific Credit-Card Brand skip',
            'global' => true,
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $nowPlusSeconds->toIso8601String()
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::P2P,
                'max_transactions' => 1,
                'max_transactions_per_consumer' => 1,
                'payment_methods' => ['credit-card', 'wallet'],
                'credit_card_brands' => ['mastercard'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 10,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ]
        ]);

        $parameters = [
            'transaction' => [
                'message' => '#VaiBrasil',
                'consumer_id' => $this->faker->randomNumber(),
                'id' => 1,
                'consumer_id_payee' => 111,
                'transaction_date' => $now->toIso8601String(),
                'credit_card_brand' => 'cielo',
                'type' => 'p2p',
                'credit_card' => 100.0,
                'wallet' => 0.0,
                'total' => 100.00,
                'installments' => 1
            ]
        ];

        $this->post(
            route('consumers.cashback.applyCashback'),
            $parameters,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonContains([
            'cashback' => 0,
            'campaign' => null
        ]);

        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
    }

    /**
     * @group cashback
     * @group skip
     */
    public function testShouldSkipCampaignWithTransactionLimitValue()
    {
        $now = Carbon::now();
        $nowPlusSeconds = $now->copy()->addSecond(self::PLUS_SECONDS_CAMPAIGN_END_DATE);

        $campaign = $this->factory->create([
            'name' => 'Campanha Teste P2P Min transaction skip',
            'global' => true,
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $nowPlusSeconds->toIso8601String()
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::P2P,
                'max_transactions' => 1,
                'min_transaction_value' => 200,
                'max_transactions_per_consumer' => 1,
                'payment_methods' => ['credit-card', 'wallet'],
                'credit_card_brands' => ['mastercard'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 10,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ]
        ]);

        $parameters = [
            'transaction' => [
                'message' => '#VaiBrasil',
                'consumer_id' => $this->faker->randomNumber(),
                'id' => 1,
                'consumer_id_payee' => 111,
                'transaction_date' => $now->toIso8601String(),
                'credit_card_brand' => 'cielo',
                'type' => 'p2p',
                'credit_card' => 100.0,
                'wallet' => 0.0,
                'total' => 100.00,
                'installments' => 1
            ]
        ];

        $this->post(
            route('consumers.cashback.applyCashback'),
            $parameters,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonContains([
            'cashback' => 0,
            'campaign' => null
        ]);

        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
    }

    /**
     * @group cashback
     * @group skip
     */
    public function testShouldSkipCampaignFirstPayeeReceivedPaymentOnly()
    {
        $now = Carbon::now();
        $nowPlusSeconds = $now->copy()->addSecond(self::PLUS_SECONDS_CAMPAIGN_END_DATE);

        $campaign = $this->factory->create([
            'name' => 'Campanha Teste P2P Specific branch skip',
            'global' => true,
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $nowPlusSeconds->toIso8601String()
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::P2P,
                'max_transactions' => 1,
                'max_transactions_per_consumer' => 1,
                'first_payee_received_payment_only' => true,
                'payment_methods' => ['credit-card', 'wallet'],
                'credit_card_brands' => ['mastercard'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 10,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ]
        ]);

        $parameters = [
            'transaction' => [
                'message' => '#VaiBrasil',
                'consumer_id' => $this->faker->randomNumber(),
                'id' => 1,
                'consumer_id_payee' => 111,
                'transaction_date' => $now->toIso8601String(),
                'credit_card_brand' => 'cielo',
                'type' => 'p2p',
                'credit_card' => 100.0,
                'wallet' => 0.0,
                'total' => 100.00,
                'installments' => 1
            ]
        ];

        $this->post(
            route('consumers.cashback.applyCashback'),
            $parameters,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonContains([
            'cashback' => 0,
            'campaign' => null
        ]);

        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
    }

    /**
     * @group cashback
     * @group skip
     */
//    public function testShouldSkipCampaignFirstPayeeReceivedPaymentOnlyWhenRetroativeWithOneTransactionP2PPayeeFirstSuccess()
//    {
//        $now = Carbon::now();
//        $nowPlusSeconds = $now->copy()->addSecond(self::PLUS_SECONDS_CAMPAIGN_END_DATE);
//
//        $campaign = $this->factory->create([
//            'name' => 'Campanha Teste P2P Specific branch skip',
//            'global' => true,
//            'duration' => [
//                'fixed' => true,
//                'start_date' => $now->toIso8601String(),
//                'end_date' => $nowPlusSeconds->toIso8601String()
//            ],
//            'transaction' => [
//                'type' => TransactionTypeEnum::P2P,
//                'first_payee_received_payment_only' => true,
//                'payment_methods' => ['credit-card'],
//                'credit_card_brands' => ['mastercard'],
//                'min_installments' => 1,
//                'conditions' => [
//                    'in_cash', 'installments'
//                ]
//            ],
//            'cashback' => [
//                'percentage' => 10,
//                'max_value' => 100,
//                'paid_by' => 'picpay',
//            ]
//        ]);
//
//        $parameters = [
//            'transaction' => [
//                'message' => '#VaiBrasil',
//                'consumer_id' => $this->faker->randomNumber(),
//                'id' => 1,
//                'consumer_id_payee' => 111,
//                'transaction_date' => $now->toIso8601String(),
//                'credit_card_brand' => 'mastercard',
//                'type' => 'p2p',
//                'credit_card' => 100.0,
//                'wallet' => 0.0,
//                'total' => 100.00,
//                'installments' => 1,
//                'first_payee_received_payment' => true
//            ]
//        ];
//
//        $this->post(
//            route('consumers.cashback.applyCashback'),
//            $parameters,
//            []
//        );
//
//        $this->seeStatusCode(Response::HTTP_ACCEPTED);
//
//        $this->seeJsonContains([
//            'cashback' => 10
//        ]);
//
//        $parameters['transaction']['transaction_date'] = $now->addMinute(-10)->toIso8601String();
//
//        $this->post(
//            route('consumers.cashback.applyCashback'),
//            $parameters,
//            []
//        );
//
//        $this->seeJsonContains([
//            'cashback' => 0,
//            'campaign' => null
//        ]);
//
//        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
//    }

    /**
     * @group cashback
     * @group skip
     */
    public function testShouldSkipCampaignConsumersOnSameCampaign()
    {
        $now = Carbon::now();
        $nowPlusSeconds = $now->copy()->addSecond(self::PLUS_SECONDS_CAMPAIGN_END_DATE);

        $consumerId = $this->faker->randomNumber();

        $campaign = $this->factory->create([
            'name' => 'Campanha Teste P2P Consumer same campaign',
            'global' => true,
            'consumers' => [$consumerId],
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $nowPlusSeconds->toIso8601String()
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::P2P,
                'max_transactions' => 1,
                'max_transactions_per_consumer' => 1,
                'payment_methods' => ['credit-card', 'wallet'],
                'credit_card_brands' => ['mastercard'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 10,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ]
        ]);

        $parameters = [
            'transaction' => [
                'message' => '#VaiBrasil',
                'consumer_id' => $consumerId,
                'id' => 1,
                'consumer_id_payee' => $consumerId,
                'transaction_date' => $now->toIso8601String(),
                'credit_card_brand' => 'cielo',
                'type' => 'p2p',
                'credit_card' => 100.0,
                'wallet' => 0.0,
                'total' => 100.00,
                'installments' => 1
            ]
        ];

        $this->post(
            route('consumers.cashback.applyCashback'),
            $parameters,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonContains([
            'cashback' => 0,
            'campaign' => null
        ]);

        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
    }

    /**
     * @group cashback
     * @group skip
     */
    public function testShouldSkipCampaignMinInstallmentsLower()
    {
        $now = Carbon::now();
        $nowPlusSeconds = $now->copy()->addSecond(self::PLUS_SECONDS_CAMPAIGN_END_DATE);

        $campaign = $this->factory->create([
            'name' => 'Campanha Teste P2P Specific branch skip',
            'global' => true,
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $nowPlusSeconds->toIso8601String()
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::P2P,
                'max_transactions' => 1,
                'max_transactions_per_consumer' => 1,
                'payment_methods' => ['credit-card', 'wallet'],
                'credit_card_brands' => ['mastercard'],
                'min_installments' => 12,
                'conditions' => [
                    'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 10,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ]
        ]);

        $parameters = [
            'transaction' => [
                'message' => '#VaiBrasil',
                'consumer_id' => $this->faker->randomNumber(),
                'id' => 1,
                'consumer_id_payee' => 111,
                'transaction_date' => $now->toIso8601String(),
                'credit_card_brand' => 'cielo',
                'type' => 'p2p',
                'credit_card' => 100.0,
                'wallet' => 0.0,
                'total' => 100.00,
                'installments' => 6
            ]
        ];

        $this->post(
            route('consumers.cashback.applyCashback'),
            $parameters,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonContains([
            'cashback' => 0,
            'campaign' => null
        ]);

        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
    }

    /**
     * @group cashback
     * @group skip
     */
    public function testShouldSkipCampaignInCashWithInstallments()
    {
        $now = Carbon::now();
        $nowPlusSeconds = $now->copy()->addSecond(self::PLUS_SECONDS_CAMPAIGN_END_DATE);

        $campaign = $this->factory->create([
            'name' => 'Campanha Teste P2P Specific branch skip',
            'global' => true,
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $nowPlusSeconds->toIso8601String()
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::P2P,
                'max_transactions' => 1,
                'max_transactions_per_consumer' => 1,
                'payment_methods' => ['credit-card', 'wallet'],
                'credit_card_brands' => ['mastercard'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash'
                ]
            ],
            'cashback' => [
                'percentage' => 10,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ]
        ]);

        $parameters = [
            'transaction' => [
                'message' => '#VaiBrasil',
                'consumer_id' => $this->faker->randomNumber(),
                'id' => 1,
                'consumer_id_payee' => 111,
                'transaction_date' => $now->toIso8601String(),
                'credit_card_brand' => 'cielo',
                'type' => 'p2p',
                'credit_card' => 100.0,
                'wallet' => 0.0,
                'total' => 100.00,
                'installments' => 12
            ]
        ];

        $this->post(
            route('consumers.cashback.applyCashback'),
            $parameters,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonContains([
            'cashback' => 0,
            'campaign' => null
        ]);

        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
    }

    /**
     * @group cashback
     * @group skip
     */
    public function testShouldSkipCampaignExclusiveExternalMerchant()
    {
        $now = Carbon::now();
        $nowPlusSeconds = $now->copy()->addSecond(self::PLUS_SECONDS_CAMPAIGN_END_DATE);

        $campaign = $this->factory->create([
            'name' => 'Campanha Teste P2P Specific branch skip',
            'global' => true,
            'external_merchant' => [
                'type' => 'tipo 1',
                'ids' => [
                    '123'
                ]
            ],
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $nowPlusSeconds->toIso8601String()
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::P2P,
                'max_transactions' => 1,
                'max_transactions_per_consumer' => 1,
                'payment_methods' => ['credit-card', 'wallet'],
                'credit_card_brands' => ['mastercard'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 10,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ]
        ]);

        $parameters = [
            'transaction' => [
                'message' => '#VaiBrasil',
                'consumer_id' => $this->faker->randomNumber(),
                'id' => 1,
                'consumer_id_payee' => 111,
                'transaction_date' => $now->toIso8601String(),
                'credit_card_brand' => 'cielo',
                'type' => 'p2p',
                'external_merchant' => [
                    'type' => 'tipo 2',
                    'id' => '321'
                ],
                'credit_card' => 100.0,
                'wallet' => 0.0,
                'total' => 100.00,
                'installments' => 1
            ]
        ];

        $this->post(
            route('consumers.cashback.applyCashback'),
            $parameters,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonContains([
            'cashback' => 0,
            'campaign' => null
        ]);

        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
    }

    /**
     * @group cashback
     * @group skip
     */
    public function testShouldSkipCampaignCampaignLimits()
    {
        $now = Carbon::now();
        $nowPlusSeconds = $now->copy()->addSecond(self::PLUS_SECONDS_CAMPAIGN_END_DATE);

        $campaign = $this->factory->create([
            'name' => 'Campanha Teste P2P Specific skip limits',
            'global' => true,
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $nowPlusSeconds->toIso8601String()
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::P2P,
                'max_transactions' => 2,
                'max_transactions_per_consumer' => 1,
                'payment_methods' => ['credit-card', 'wallet'],
                'credit_card_brands' => ['picpay'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'limits' => [
                'uses_per_consumer_per_period' => [
                    'type' => 'count',
                    'period' => 'day',
                    'uses' => 1
                ]
            ],
            'cashback' => [
                'percentage' => 10,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ]
        ]);

        $consumerId = $this->faker->randomNumber();

        $parameters = [
            'transaction' => [
                'consumer_id' => $consumerId,
                'message' => '#VaiBrasil',
                'id' => 999,
                'consumer_id_payee' => 111,
                'transaction_date' => $now->toIso8601String(),
                'credit_card_brand' => 'picpay',
                'type' => 'p2p',
                'credit_card' => 100.0,
                'wallet' => 0.0,
                'total' => 100.00,
                'installments' => 1
            ]
        ];


        $this->post(
            route('consumers.cashback.applyCashback'),
            $parameters,
            []
        );

        $this->seeJsonContains([
            'cashback' => 10
        ]);

        $this->post(
            route('consumers.cashback.applyCashback'),
            $parameters,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonContains([
            'cashback' => 0,
            'campaign' => null
        ]);

        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
    }

    /**
     * @group cashback
     * @group skip
     */
//    public function testShouldNotSkipCampaignCampaignLimitsTransactionRetroative()
//    {
//        $now = Carbon::now();
//        $nowPlusSeconds = $now->copy()->addSecond(self::PLUS_SECONDS_CAMPAIGN_END_DATE);
//
//        $campaign = $this->factory->create([
//            'name' => 'Campanha Teste P2P Specific skip limits',
//            'global' => true,
//            'duration' => [
//                'fixed' => true,
//                'start_date' => $now->toIso8601String(),
//                'end_date' => $nowPlusSeconds->toIso8601String()
//            ],
//            'transaction' => [
//                'type' => TransactionTypeEnum::P2P,
//                'max_transactions' => 2,
//                'max_transactions_per_consumer' => 1,
//                'payment_methods' => ['credit-card', 'wallet'],
//                'credit_card_brands' => ['picpay'],
//                'min_installments' => 1,
//                'conditions' => [
//                    'in_cash', 'installments'
//                ]
//            ],
//            'limits' => [
//                'uses_per_consumer_per_period' => [
//                    'type' => 'count',
//                    'period' => 'day',
//                    'uses' => 1
//                ]
//            ],
//            'cashback' => [
//                'percentage' => 10,
//                'max_value' => 100,
//                'paid_by' => 'picpay',
//            ]
//        ]);
//
//        $consumerId = $this->faker->randomNumber();
//
//        $parameters = [
//            'transaction' => [
//                'consumer_id' => $consumerId,
//                'message' => '#VaiBrasil',
//                'id' => 1,
//                'consumer_id_payee' => 111,
//                'transaction_date' => $now->addMinute(-10),
//                'credit_card_brand' => 'picpay',
//                'type' => 'p2p',
//                'credit_card' => 100.0,
//                'wallet' => 0.0,
//                'total' => 100.00,
//                'installments' => 1
//            ]
//        ];
//
//
//        $this->post(
//            route('consumers.cashback.applyCashback'),
//            $parameters,
//            []
//        );
//
//        $this->seeJsonContains([
//            'cashback' => 10
//        ]);
//
//        $this->post(
//            route('consumers.cashback.applyCashback'),
//            $parameters,
//            []
//        );
//
//        $this->seeStatusCode(Response::HTTP_ACCEPTED);
//
//        $this->seeJsonContains([
//            'cashback' => 10
//        ]);
//
//        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
//    }

    /**
     * @group cashback
     * @group discard
     */
    public function testShouldDiscardCampaignByMaxTransaction()
    {
        $now = Carbon::now();
        $nowPlusSeconds = $now->copy()->addSecond(self::PLUS_SECONDS_CAMPAIGN_END_DATE);

        $campaign = $this->factory->create([
            'name' => 'Campanha Teste P2P Discard MaxTransaction',
            'global' => true,
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $nowPlusSeconds->toIso8601String()
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::P2P,
                'max_transactions' => 1,
                'max_transactions_per_consumer' => 1,
                'payment_methods' => ['credit-card', 'wallet'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 10,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ]
        ]);

        $parameters = [
            'transaction' => [
                'message' => '#VaiBrasil',
                'consumer_id' => $this->faker->randomNumber(),
                'id' =>  $this->faker->randomNumber(),
                'consumer_id_payee' => $this->faker->randomNumber(),
                'transaction_date' => $now->toIso8601String(),
                'type' => 'p2p',
                'credit_card' => 100.0,
                'wallet' => 0.0,
                'total' => 100.00,
                'installments' => 1
            ]
        ];

        $this->post(
            route('consumers.cashback.applyCashback'),
            $parameters,
            []
        );

        $this->seeJsonContains([
            'cashback' => 10
        ]);

        $this->post(
            route('consumers.cashback.applyCashback', ['consumer_id' => $this->faker->randomNumber()]),
            $parameters,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonContains([
            'cashback' => 0,
            'campaign' => null
        ]);

        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
    }

    /**
     * @group cashback
     * @group discard
     */
    public function testShouldDiscardCampaignByLimits()
    {
        $now = Carbon::now();
        $nowPlusSeconds = $now->copy()->addSecond(self::PLUS_SECONDS_CAMPAIGN_END_DATE);

        $campaign = $this->factory->create([
            'name' => 'Campanha Teste P2P Discard Limits',
            'global' => true,
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $nowPlusSeconds->toIso8601String()
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::P2P,
                'max_transactions' => 5,
                'max_transactions_per_consumer' => 5,
                'payment_methods' => ['credit-card', 'wallet'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'limits' => [
                'uses_per_consumer' => [
                    'type' => 'p2p',
                    'uses' => 1
                ],
            ],
            'cashback' => [
                'percentage' => 10,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ]
        ]);

        $consumerId = $this->faker->randomNumber();

        $parameters = [
            'transaction' => [
                'message' => '#VaiBrasil',
                'consumer_id' => $consumerId,
                'id' =>  $this->faker->randomNumber(),
                'consumer_id_payee' => $this->faker->randomNumber(),
                'transaction_date' => $now->toIso8601String(),
                'type' => 'p2p',
                'credit_card' => 100.0,
                'wallet' => 0.0,
                'total' => 100.00,
                'installments' => 1
            ]
        ];


        $this->post(
            route('consumers.cashback.applyCashback'),
            $parameters,
            []
        );

        $this->seeJsonContains([
            'cashback' => 10
        ]);

        $this->post(
            route('consumers.cashback.applyCashback'),
            $parameters,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonContains([
            'cashback' => 0,
            'campaign' => null
        ]);

        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
    }

    /**
     * @group cashback
     * @group discard
     */
    public function testShouldDiscardCampaignByExpiredForConsumer()
    {
        $now = Carbon::now();

        $campaign = $this->factory->create([
            'name' => 'Campanha Teste P2P Discard Expired for consumer',
            'global' => false,
            'duration' => [
                'fixed' => false,
                'hour' => 1
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::P2P,
                'max_transactions' => 1,
                'max_transactions_per_consumer' => 1,
                'payment_methods' => ['credit-card', 'wallet'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 10,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ]
        ]);

        $consumerId = $this->faker->randomNumber();

        $parameters = [
            'consumers' => [
                $consumerId,
            ]
        ];

        $this->patch(
            route('campaign.associate', ['campaign_id' => $campaign->getId()]),
            $parameters,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $parameters = [
            'transaction' => [
                'message' => '#VaiBrasil',
                'id' =>  $this->faker->randomNumber(),
                'consumer_id' => $consumerId,
                'consumer_id_payee' => $this->faker->randomNumber(),
                'transaction_date' => $now->subHour(2)->toIso8601String(),
                'type' => 'p2p',
                'credit_card' => 100.0,
                'wallet' => 0.0,
                'total' => 100.00,
                'installments' => 1
            ]
        ];

        $this->post(
            route('consumers.cashback.applyCashback'),
            $parameters,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonContains([
            'cashback' => 0,
            'campaign' => null
        ]);

        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
    }

    /**
     * @group cashback
     * @group discard
     */
    public function testShouldDiscardCampaignByNotFirstPayment()
    {
        $now = Carbon::now();
        $nowPlusSeconds = $now->copy()->addSecond(self::PLUS_SECONDS_CAMPAIGN_END_DATE);

        $campaign = $this->factory->create([
            'name' => 'Campanha Teste P2P Discard not first payment',
            'global' => true,
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $nowPlusSeconds->toIso8601String()
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::P2P,
                'first_payment' => true,
                'max_transactions' => 1,
                'max_transactions_per_consumer' => 1,
                'payment_methods' => ['credit-card', 'wallet'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 10,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ]
        ]);

        $parameters = [
            'transaction' => [
                'message' => '#VaiBrasil',
                'consumer_id' => $this->faker->randomNumber(),
                'id' =>  $this->faker->randomNumber(),
                'consumer_id_payee' => $this->faker->randomNumber(),
                'transaction_date' => $now->toIso8601String(),
                'first_payment' => false,
                'type' => 'p2p',
                'credit_card' => 100.0,
                'wallet' => 0.0,
                'total' => 100.00,
                'installments' => 1
            ]
        ];

        $this->post(
            route('consumers.cashback.applyCashback'),
            $parameters,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonContains([
            'cashback' => 0,
            'campaign' => null
        ]);

        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
    }

    /**
     * @group cashback
     * @group discard
     * @group retroative
     */
    public function testShouldDiscardCampaignByNotFirstPaymentRetroative()
    {
        $now = Carbon::now();
        $nowPlusSeconds = $now->copy()->addSecond(5);

        $campaign = $this->factory->create([
            'name' => 'Campanha Teste P2P Discard not first payment retroative',
            'global' => true,
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $nowPlusSeconds->toIso8601String()
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::P2P,
                'first_payment' => true,
                'max_transactions' => 10,
                'max_transactions_per_consumer' => 10,
                'payment_methods' => ['credit-card', 'wallet'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 10,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ]
        ]);

        $parameters = [
            'transaction' => [
                'message' => '#VaiBrasil',
                'consumer_id' => 123,
                'id' =>  $this->faker->randomNumber(),
                'consumer_id_payee' => $this->faker->randomNumber(),
                'transaction_date' => $now->toIso8601String(),
                'first_payment' => true,
                'type' => 'p2p',
                'credit_card' => 100.0,
                'wallet' => 0.0,
                'total' => 100.00,
                'installments' => 1
            ]
        ];

        $this->post(
            route('consumers.cashback.applyCashback'),
            $parameters,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonContains([
            'cashback' => 10
        ]);

        $parametersRetroative = [
            'transaction' => [
                'message' => '#VaiBrasil',
                'consumer_id' => 123,
                'id' =>  $this->faker->randomNumber(),
                'consumer_id_payee' => $this->faker->randomNumber(),
                'transaction_date' => $now->subMinutes(20)->toIso8601String(),
                'first_payment' => true,
                'type' => 'p2p',
                'credit_card' => 100.0,
                'wallet' => 0.0,
                'total' => 100.00,
                'installments' => 1
            ]
        ];

        $this->post(
            route('consumers.cashback.applyCashback'),
            $parametersRetroative,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonContains([
            'cashback' => 0
        ]);

        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
    }

    /**
     * @group cashback
     * @group discard
     */
    public function testShouldDiscardCampaignByFirstPaymentToSeller()
    {
        $now = Carbon::now();
        $nowPlusSeconds = $now->copy()->addSecond(self::PLUS_SECONDS_CAMPAIGN_END_DATE);

        $campaign = $this->factory->create([
            'name' => 'Campanha Teste PAV Discard MaxTransaction',
            'global' => true,
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $nowPlusSeconds->toIso8601String()
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::PAV,
                'max_transactions' => 1,
                'max_transactions_per_consumer' => 1,
                'first_payment_to_seller' => true,
                'payment_methods' => ['credit-card', 'wallet'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 10,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ]
        ]);

        $parameters = [
            'transaction' => [
                'message' => '#VaiBrasil',
                'id' =>  $this->faker->randomNumber(),
                'consumer_id' => $this->faker->randomNumber(),
                'consumer_id_payee' => $this->faker->randomNumber(),
                'transaction_date' => $now->toIso8601String(),
                'first_payment_to_seller' => false,
                'seller_id' => 1,
                'seller_type' => SellerTypeEnum::PAV,
                'type' => 'pav',
                'credit_card' => 100.0,
                'wallet' => 0.0,
                'total' => 100.00,
                'installments' => 1
            ]
        ];

        $this->post(
            route('consumers.cashback.applyCashback'),
            $parameters,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonContains([
            'cashback' => 0,
            'campaign' => null
        ]);

        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
    }

    /**
     * @group cashback
     * @group discard
     * @group retroative1
     */
    public function testShouldDiscardCampaignByFirstPaymentToSellerRetroative()
    {
        $now = Carbon::now();
        $nowPlusSeconds = $now->copy()->addSecond(5);

        $campaign = $this->factory->create([
            'name' => 'Campaign Test First payment Mixed',
            'global' => true,
            'sellers_types' => ['pav'],
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $nowPlusSeconds->toIso8601String()
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::PAV,
                'max_transactions' => 10,
                'max_transactions_per_consumer' => 10,
                'first_payment_to_seller' => true,
                'payment_methods' => ['wallet', 'credit-card'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 10,
                'max_value' => 1000,
                'paid_by' => 'picpay',
            ]
        ]);

        $parameters = [
            'transaction' => [
                'consumer_id' => 123,
                'id' => 1,
                'type' => 'pav',
                'seller_id' => 1,
                'seller_type' => SellerTypeEnum::PAV,
                'transaction_date' => $now->toIso8601String(),
                'first_payment_to_seller' => true,
                'credit_card' => 100.0,
                'wallet' => 100.0,
                'total' => 200,
                'installments' => 1
            ]
        ];

        $this->post(
            route('consumers.cashback.applyCashback'),
            $parameters,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonContains([
            'cashback' => 20
        ]);

        $parametersRetroative = [
            'transaction' => [
                'id' =>  $this->faker->randomNumber(),
                'consumer_id' => 123,
                'transaction_date' => $now->subMinutes(20)->toIso8601String(),
                'first_payment_to_seller' => true,
                'seller_id' => 1,
                'seller_type' => SellerTypeEnum::PAV,
                'type' => 'pav',
                'credit_card' => 100.0,
                'wallet' => 0.0,
                'total' => 100.00,
                'installments' => 1
            ]
        ];

        $this->post(
            route('consumers.cashback.applyCashback'),
            $parametersRetroative,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonContains([
            'cashback' => 0,
            'campaign' => null
        ]);

        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
    }

    /**
     * @group cashback
     * @group discard
     */
    public function testShouldDiscardCampaignByRequiredMessage()
    {
        $now = Carbon::now();
        $nowPlusSeconds = $now->copy()->addSecond(self::PLUS_SECONDS_CAMPAIGN_END_DATE);

        $campaign = $this->factory->create([
            'name' => 'Campanha Teste P2P Discard MaxTransaction',
            'global' => true,
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $nowPlusSeconds->toIso8601String()
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::P2P,
                'max_transactions' => 1,
                'max_transactions_per_consumer' => 1,
                'required_message' => true,
                'payment_methods' => ['credit-card', 'wallet'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 10,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ]
        ]);

        $parameters = [
            'transaction' => [
                'message' => '',
                'id' =>  $this->faker->randomNumber(),
                'consumer_id' => $this->faker->randomNumber(),
                'consumer_id_payee' => $this->faker->randomNumber(),
                'transaction_date' => $now->toIso8601String(),
                'type' => 'p2p',
                'credit_card' => 100.0,
                'wallet' => 0.0,
                'total' => 100.00,
                'installments' => 1
            ]
        ];

        $this->post(
            route('consumers.cashback.applyCashback'),
            $parameters,
            []
        );

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonContains([
            'cashback' => 0,
            'campaign' => null
        ]);

        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]));
    }
}
