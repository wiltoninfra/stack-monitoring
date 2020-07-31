<?php


namespace Tests\Integration\Routes\v1\BlackListedConsumer;

use Faker\Factory;
use Illuminate\Http\Response;
use Promo\Documents\Enums\CampaignTypeEnum;
use Promo\Documents\Enums\TransactionTypeEnum;
use Tests\TestCase;


class BlackListedConsumerTest extends TestCase
{

    private $faker;

    public function setUp()
    {
        parent::setUp();
        $this->faker = Factory::create();
    }

    /**
     * @group blacklistedconsumer
     */
    public function testShouldCreateBlackListedConsumer()
    {
        $parameters = $this->createRandomBlackListedConsumer();

        $userId = $this->faker->randomNumber();

        $this->post(route('consumers.blacklist.create', ['consumer_id' => $userId]), $parameters, []);

        $this->seeStatusCode(Response::HTTP_CREATED);

        $this->seeJsonStructure([
            'consumer_id',
            'campaign_types',
            'active',
            'transaction_types',
            'details'
        ]);
    }

    /**
     * @group blacklistedconsumer
     */
    public function testShouldGetConsumerById()
    {
        $parameters = $this->createRandomBlackListedConsumer();

        $userId = $this->faker->randomNumber();

        $this->post(route('consumers.blacklist.create', ['consumer_id' => $userId]), $parameters, []);

        $this->get(route('consumers.blacklist.findByConsumerId', ['consumer_id' => $userId]));

        $this->seeStatusCode(Response::HTTP_OK);

        $this->seeJsonStructure([
            'consumer_id',
            'campaign_types',
            'active',
            'transaction_types',
            'details'
        ]);
    }

    /**
     * @group blacklistedconsumer
     */
    public function testShouldGetNullConsumer()
    {
        $this->get(route('consumers.blacklist.findByConsumerId', ['consumer_id' => 0]));

        $this->seeStatusCode(Response::HTTP_OK);
    }

    /**
     * @group blacklistedconsumer
     */
    public function testShouldUpdateBlackListedConsumer()
    {
        $parameters = $this->createRandomBlackListedConsumer();

        $userId = $this->faker->randomNumber();

        $this->post(route('consumers.blacklist.create', ['consumer_id' => $userId]), $parameters, []);

        $newParameters = $this->createRandomBlackListedConsumer();

        $this->put(route('consumers.blacklist.update', ['consumer_id' => $userId]), $newParameters, []);

        $this->seeStatusCode(Response::HTTP_OK);

        $this->seeJsonStructure([
            'consumer_id',
            'campaign_types',
            'active',
            'transaction_types',
            'details'
        ]);
    }

    private function createRandomBlackListedConsumer()
    {
        return [
            'campaign_types' => [$this->faker->randomElement(CampaignTypeEnum::getFields())],
            'transaction_types' => [$this->faker->randomElement(TransactionTypeEnum::getFields())],
            'active' => $this->faker->boolean,
            'details' => [
                'created_by' => $this->faker->email,
                'description' => $this->faker->text,
                'origin' => 'herodash'
            ]
        ];
    }

}
