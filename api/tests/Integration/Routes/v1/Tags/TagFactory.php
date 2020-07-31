<?php

namespace Tests\Routes\v1\Tags;

use Promo\Services\TagService;
use Tests\FactoryBase;

class TagFactory extends FactoryBase
{
    private $service = null;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->service = new TagService();
    }

    public function getDocument(array $newer = []): array
    {
        $data = [
            "name" => "FACTORY TAG",
            "abbreviation" => "TST",
            "color" => "#FFF"
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

    public function delete(string $tag_id)
    {  
        $this->service->delete($tag_id, false);
    }
}