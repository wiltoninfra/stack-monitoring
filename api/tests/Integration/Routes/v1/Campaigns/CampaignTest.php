<?php

namespace Tests\Routes\v1\Cashback;

use Carbon\Carbon;
use Tests\Routes\v1\Campaigns\CampaignFactory;
use Tests\TestCase;
use Illuminate\Http\Response;
use Promo\Documents\Campaign;
use Promo\Documents\Enums\CampaignTypeEnum;
use Promo\Documents\Enums\TransactionTypeEnum;
use PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager;
use Illuminate\Validation\ValidationException;

class CampaignTest extends TestCase
{
    public $factory = null;

    public function setUp()
    {
        parent::setUp();
        $this->factory = new CampaignFactory($this->app);
    }

    /**
     * @group campaign
     */
    public function testGetTypes()
    {
        $this->get(route('campaigns.getTypes'));

        $this->seeStatusCode(Response::HTTP_OK);

        $this->assertJson(json_encode([
            'types' => array_values(CampaignTypeEnum::getFields())
        ]));
    }

    /**
     * @group campaign
     */
    public function testGetAll()
    {
        $this->get(route('campaign.getAll'));

        $this->seeStatusCode(Response::HTTP_OK);

        $this->seeJsonStructure(['*' =>
            [
                'id',
                'name',
                'description',
                'active',
                'global',
                'created_at',
                'duration',
            ]
        ]);
    }

    /**
     * @group campaign
     */
    public function testGet()
    {
        $campaign = $this->factory->create();

        $this->get(route('campaign.get', ['campaign_id' => $campaign->getId()]));

        $this->seeStatusCode(Response::HTTP_OK);

        $this->seeJsonStructure([
               'id',
               'name',
               'description',
               'active',
               'global',
               'created_at',
               'duration',
               'transaction',
               'cashback',
        ]);
    }

    /**
     * @group campaign
     */
    public function testUpdatingCampaignStatus()
    {
        $last_campaign = DocumentManager::createQueryBuilder(Campaign::class)
            ->sort('created_at', 'desc')
            ->getQuery()
            ->getSingleResult();

        $parameters = [
            'active' => true
        ];

        $this->patch(route('campaign.updateStatus', ['campaign_id' => $last_campaign->getId()]), $parameters, []);

        $this->seeStatusCode(Response::HTTP_ACCEPTED);
    }

    /**
     * @group campaign
     */
    public function testGetStats()
    {
        $campaign = $this->factory->create();

        $this->get(route('campaign.getStats', ['campaign_id' => $campaign->getId()]));

        $this->seeStatusCode(Response::HTTP_OK);

        $this->seeJsonStructure([
            'stats'
        ]);
    }

    /**
     * @group campaign
     */
    public function testReplaceTags()
    {
        $campaign = $this->factory->create();

        $parameters = [
            'tags' => [
                'teste'
            ]
        ];

        $this->patch(route('campaign.replaceTags', ['campaign_id' => $campaign->getId()]), $parameters);

        $this->seeStatusCode(Response::HTTP_ACCEPTED);
    }

    /**
     * @group campaign
     */
    public function testDeleteCampaign()
    {
        $campaign = $this->factory->create();

        $this->delete(route('campaign.delete', ['campaign_id' => $campaign->getId()]), []);

        $this->seeStatusCode(Response::HTTP_NO_CONTENT);
    }

    /**
     * @group campaign
     */
    public function testCreatingCashbackCampaignSellerMembershipDurationFixed()
    {
        $now = Carbon::now();
        $now_plus_2_days = $now->copy()->addDays(2);

        $parameters = [
            'name' => 'teste',
            'description' => 'descrição',
            'type' => CampaignTypeEnum::CASHBACK,
            'active' => true,
            'global' => false,
            'sellers_types' => ['membership'],
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $now_plus_2_days->toIso8601String(),
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::PAV,
                'max_transactions_per_consumer' => 1,
                'payment_methods' => ['credit-card'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 20,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ],
            'tags' => []
        ];

        $this->post(route('campaigns.create'), $parameters, []);

        $this->seeStatusCode(Response::HTTP_CREATED);

        $this->seeJsonStructure([
            'id',
            'name',
            'description',
            'active',
            'global',
            'created_at',
            'duration',
            'transaction',
            'cashback',
        ]);
    }

    /**
     * @group campaign
     */
    public function testCreatingCashbackCampaignSellerPavDurationVariable()
    {
        $parameters = [
            'name' => 'teste',
            'description' => 'descrição',
            'type' => CampaignTypeEnum::CASHBACK,
            'active' => true,
            'global' => false,
            'sellers_types' => ['pav'],
            'sellers' => [1, 35, 874],
            'duration' => [
                'fixed' => false,
                'days' => 1
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::PAV,
                'payment_methods' => ['credit-card', 'wallet'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 30,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ],
            'tags' => []
        ];

        $this->post(route('campaigns.create'), $parameters, []);

        $this->seeStatusCode(Response::HTTP_CREATED);

        $this->seeJsonStructure([
            'id',
            'name',
            'description',
            'active',
            'global',
            'created_at',
            'duration',
            'transaction',
            'cashback',
        ]);
    }

    /**
     * @group campaign
     */
    public function testCreatingCashbackCampaignP2PDurationFixed()
    {
        $now = Carbon::now();
        $now_minus_2_days = $now->copy()->subDay(2);

        $parameters = [
            'name' => 'teste',
            'description' => 'descrição',
            'type' => CampaignTypeEnum::CASHBACK,
            'active' => true,
            'global' => true,
            'duration' => [
                'fixed' => true,
                'start_date' => $now_minus_2_days->toIso8601String(),
                'end_date' => $now->addSecond(1)->toIso8601String(),
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::P2P,
                'payment_methods' => ['credit-card', 'wallet'],
                'max_transactions_per_consumer' => 2,
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 30,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ],
            'tags' => []
        ];

        $this->post(route('campaigns.create'), $parameters, []);

        $this->seeStatusCode(Response::HTTP_CREATED);

        $this->seeJsonStructure([
            'id',
            'name',
            'description',
            'active',
            'global',
            'created_at',
            'duration',
            'transaction',
            'cashback',
        ]);
    }

    /**
     * @group campaign
     */
    public function testCreatingCashbackCampaignMixedDurationFixed()
    {
        $now = Carbon::now();
        $now_plus_2_days = $now->copy()->addDays(2);

        $parameters = [
            'name' => 'teste',
            'description' => 'descrição',
            'type' => CampaignTypeEnum::CASHBACK,
            'active' => true,
            'global' => true,
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $now_plus_2_days->toIso8601String(),
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::MIXED,
                'payment_methods' => ['credit-card', 'wallet'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 45,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ],
            'tags' => []
        ];

        $this->post(route('campaigns.create'), $parameters, []);

        $this->seeStatusCode(Response::HTTP_CREATED);

        $this->seeJsonStructure([
            'id',
            'name',
            'description',
            'active',
            'global',
            'created_at',
            'duration',
            'transaction',
            'cashback',
        ]);
    }

    /**
     * @group campaign
     */
    public function testCreatingCashbackCampaignInstallments()
    {
        $now = Carbon::now();
        $now_plus_2_days = $now->copy()->addDay(2);

        $parameters = [
            'name' => 'Parcelado',
            'description' => 'Descricao',
            'type' => CampaignTypeEnum::CASHBACK,
            'active' => true,
            'global' => true,
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $now_plus_2_days->toIso8601String(),
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::PAV,
                'max_transactions_per_consumer' => 1,
                'payment_methods' => ['credit-card'],
                'min_installments' => 2,
                'conditions' => [
                    'in_cash'
                ]
            ],
            'cashback' => [
                'percentage' => 20,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ],
            'tags' => []
        ];

        $this->post(route('campaigns.create'), $parameters, []);

        $this->seeStatusCode(Response::HTTP_CREATED);

        $this->seeJsonStructure([
            'id',
            'name',
            'description',
            'active',
            'global',
            'created_at',
            'duration',
            'transaction',
            'cashback',
        ]);
    }

    /**
     * @group campaign
     */
    public function testUpdatingCashbackCampaign()
    {
        $last_campaign = DocumentManager::createQueryBuilder(Campaign::class)
                ->sort('created_at', 'desc')
                ->getQuery()
                ->getSingleResult();

        $now = Carbon::now();
        $now_plus_2_days = $now->copy()->addDays(2);

        $parameters = [
            'name' => $last_campaign->getName(),
            'description' => $last_campaign->getDescription(),
            'type' => CampaignTypeEnum::CASHBACK,
            'active' => true,
            'global' => true,
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $now_plus_2_days->toIso8601String(),
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::MIXED,
                'payment_methods' => ['credit-card', 'wallet'],
                'max_transactions_per_consumer' => 1,
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 45,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ],
            'tags' => [],
            'versions' => $last_campaign->getVersions()
        ];

        $this->put(route('campaign.update', ['campaign_id' => $last_campaign->getId()]), $parameters, []);

        $this->seeStatusCode(Response::HTTP_ACCEPTED);

        $this->seeJsonStructure([
            'id',
            'name',
            'description',
            'active',
            'global',
            'created_at',
            'duration',
            'transaction',
            'cashback'
        ]);
    }

    /**
     * @group campaign
     */
    public function testCreatingCashfrontCampaignDurantionFixed()
    {
        $now = Carbon::now();
        $now_plus_2_days = $now->copy()->addDays(2);

        $parameters = [
            'name' => 'teste',
            'description' => 'descrição',
            'type' => CampaignTypeEnum::CASHFRONT,
            'active' => true,
            'global' => true,
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $now_plus_2_days->toIso8601String(),
            ],
            'deposit' => [
                'min_deposit_value' => 10,
                'max_deposits_per_consumer_per_day' => 1,
                'first_deposit_only' => true
            ],
            'cashfront' => [
                'percentage' => 30,
                'max_value' => 100
            ],
            'tags' => []
        ];

        $this->post(route('campaigns.create'), $parameters, []);

        $this->seeStatusCode(Response::HTTP_CREATED);

        $this->seeJsonStructure([
            'id',
            'name',
            'description',
            'active',
            'global',
            'created_at',
            'duration',
            'deposit',
            'cashfront',
        ]);
    }

    /**
     * @group campaign
     */
    public function testCreatingCashfrontCampaignDurationVariable()
    {
        $parameters = [
            'name' => 'teste',
            'description' => 'descrição',
            'type' => CampaignTypeEnum::CASHFRONT,
            'active' => true,
            'global' => true,
            'duration' => [
                'fixed' => false,
                'days' => 1
            ],
            'deposit' => [
                'min_deposit_value' => 10,
                'max_deposits_per_consumer' => 1,
                'first_deposit_only' => true
            ],
            'cashfront' => [
                'percentage' => 40,
                'max_value' => 100
            ]
        ];

        $this->post(route('campaigns.create'), $parameters, []);

        $this->seeStatusCode(Response::HTTP_CREATED);

        $this->seeJsonStructure([
            'id',
            'name',
            'description',
            'active',
            'global',
            'created_at',
            'duration',
            'deposit',
            'cashfront',
        ]);
    }

    /**
     * @group campaign
     */
    public function testCreatingInstantCashCampaignDurationFixed()
    {
        $now = Carbon::now();
        $now_plus_2_days = $now->copy()->addDay(2);

        $parameters = [
            'name' => 'teste',
            'description' => 'descrição',
            'type' => CampaignTypeEnum::INSTANTCASH,
            'instantcash' => [
                'value' => 100
            ],
            'duration' => [
                'fixed' => true,
                'start_date' => $now,
                'end_date' => $now_plus_2_days
            ],
            'tags' => []
        ];

        $this->post(route('campaigns.create'), $parameters, []);

        $this->seeStatusCode(Response::HTTP_CREATED);

        $this->seeJsonStructure([
            'id',
            'name',
            'description',
            'active',
            'global',
            'created_at',
            'duration',
            'instantcash'
        ]);
    }

    /**
     * @group campaign
     */
    public function testShouldCheckSellersIdsTrue()
    {
        $now = Carbon::now();
        $now_plus_2_days = $now->copy()->addDays(2);

        $parameters = [
            'name' => 'teste',
            'description' => 'descrição',
            'type' => CampaignTypeEnum::CASHBACK,
            'active' => true,
            'global' => false,
            'sellers_types' => ['membership'],
            'sellers' => [1, 2, 3],
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $now_plus_2_days->toIso8601String(),
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::PAV,
                'max_transactions_per_consumer' => 1,
                'payment_methods' => ['credit-card'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 20,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ],
            'tags' => []
        ];

        $campaign = $this->factory->create($parameters);

        $this->post(route('campaign.checkSellers', ['campaign_id' => $campaign->getId()]), ['sellers' => [1, 2, 3]]);

        $this->seeStatusCode(Response::HTTP_OK);
    }

    /**
     * @group campaign
     */
    public function testShouldRemoveSellersFromCampaign()
    {
        $now = Carbon::now();
        $now_plus_2_days = $now->copy()->addDays(2);

        $parameters = [
            'name' => 'teste',
            'description' => 'descrição',
            'type' => CampaignTypeEnum::CASHBACK,
            'active' => true,
            'global' => false,
            'sellers_types' => ['membership'],
            'sellers' => [1, 2, 3],
            'duration' => [
                'fixed' => true,
                'start_date' => $now->toIso8601String(),
                'end_date' => $now_plus_2_days->toIso8601String(),
            ],
            'transaction' => [
                'type' => TransactionTypeEnum::PAV,
                'max_transactions_per_consumer' => 1,
                'payment_methods' => ['credit-card'],
                'min_installments' => 1,
                'conditions' => [
                    'in_cash', 'installments'
                ]
            ],
            'cashback' => [
                'percentage' => 20,
                'max_value' => 100,
                'paid_by' => 'picpay',
            ],
            'tags' => []
        ];

        $campaign = $this->factory->create($parameters);

        $this->patch(route('campaign.removeSellers', ['campaign_id' => $campaign->getId()]), ['sellers' => [1, 2]]);

        $this->seeStatusCode(Response::HTTP_ACCEPTED);
    }

}
