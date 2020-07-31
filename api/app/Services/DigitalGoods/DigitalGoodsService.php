<?php

namespace Promo\Services\DigitalGoods;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class DigitalGoodsService
{
    /**
     * Tempo de duração da informação no cache
     */
    const TTL = 1440;

    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('microservices.digital_goods')
        ]);
    }

    /**
     * Obtém detalhes de um serviço do Digital Goods
     *
     * @param string $dg_id
     * @return bool
     */
    public function getServiceDetails(string $dg_id)
    {
        $self = $this;
        $value = Cache::remember($this->getKeyName($dg_id), self::TTL, function () use ($self, $dg_id) {

            $result = $self->client->request('GET', 'v1/digitalgoods/services/' . $dg_id, []);
            $body = json_decode($result->getBody(), true);

            return $body;
        });

        return $value;
    }

    /**
     * Gera a chave para armazenamento no Redis
     *
     * @param string $dg_id
     * @return string
     */
    private function getKeyName(string $dg_id)
    {
        return 'promo:dg:' . $dg_id;
    }
}