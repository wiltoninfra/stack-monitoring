<?php

namespace Tests\Routes\v1\Campaigns;

use Carbon\Carbon;
use Promo\Documents\Enums\CampaignTypeEnum;
use Promo\Documents\Enums\TransactionTypeEnum;
use Promo\Services\CampaignService;
use Tests\FactoryBase;

class CampaignFactory extends FactoryBase
{
    /**
     * @var CampaignService|null
     */
    private $service = null;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->service = $this->getApp()->make(CampaignService::class);
    }

    public function create(array $newer = [])
    {
        $now = Carbon::now();
        $now_plus_2_days = $now->copy()->addDays(2);

        $data = [
            'name' => 'FACTORY CAMPAIGN',
            'description' => 'DESCRIPTION',
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

        $data = $this->fillData($data, $newer);

        $document = $this->service->create($data);

        return $document;
    }

    public function invalidate(string $campaign_id): void
    {
        $this->service->updateStatus(false, $campaign_id);
    }

    public function delete(string $campaign_id): void
    {  
        $this->service->delete($campaign_id, false);
    }

}
