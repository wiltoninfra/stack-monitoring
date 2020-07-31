<?php

namespace Tests\Routes\v1\Webhooks;

use Promo\Services\WebhookService;
use Tests\FactoryBase;

class WebhookFactory extends FactoryBase
{
    private $service = null;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->service = new WebhookService();
    }

    public function getDocument(array $newer = []): array
    {
        $data = [
            "campaign_id" => "5d30dace21410401e82483a2",
            "variants" => [
                [
                    "name" => "variant name",
                    "percentage" => 2,
                    "target" => [
                        "model"=>"generic"
                    ]
                ]
            ]
        ];

        $data = $this->fillData($data, $newer);

        return $data;
    }

    public function create(array $newer = [])
    {
        $data = $this->getDocument($newer);
        $document = $this->service->create($data);
        return $document;
    }

    public function delete(string $webhook_id)
    {
        $this->service->delete($webhook_id, false);
    }
}