<?php

namespace Promo\Services\Core;

use GuzzleHttp\Client;

/**
 * Class CoreService
 * @package Promo\Services\Core
 */
class CoreService
{
    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * @var mixed
     */
    private $token;

    /**
     * CoreService constructor.
     */
    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('microservices.core_address'),
            'http_errors' => false
        ]);
        $this->token = config('microservices.core_token');
    }

    /**
     * Adiciona crédito promocional ao consumer
     *
     * @param int $consumer_id
     * @param float $reward_value
     * @param string $credit_type
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function addConsumerCredit(int $consumer_id, float $reward_value, string $credit_type = 'manual_deposit_reward')
    {
        $data = [
            'value'         => $reward_value,
            'seller_id'     => 1, // PicPay
            'consumer_id'   => $consumer_id,
            'credit_type'   => $credit_type, // padrão é o crédito de cashfront
            'origin_id'     => 1 // PicPay
        ];

        $token = config('microservices.core_token');

        $response = $this->client->request('POST', 'reward/addConsumerCredit?token=' . $token , [
            'form_params' => $data,
        ]);

        $result = $this->parseJsonToObject($response);

        // Verifica se a operação foi feita com sucesso
        if ( ($result->body->codigo ?? 0) != 1)
        {
            return false;
        }

        \Log::info(($result->body->texto ?? null), [
            'consumer_id' => $consumer_id,
            'reward'      => $reward_value
        ]);

        return true;
    }

    /**
     * @param $consumer_id
     * @return bool
     */
    public function upgradeUserProWithoutFee($consumer_id)
    {
        \Log::info("Adicionando usuário para PRO - (upgradeUserProWithoutFee) ");

        $url = sprintf("Consumers/upgradeUserProWithoutFee/%s?token={$this->token}",
            $consumer_id
        );
        $response = $this->client->get($url);
        $url = config('microservices.core_address') . $url;
        $response = $this->response($response);

        if($response['code'] === 200){
            return true;
        }

        \Log::info("Fail to connect (upgradeUserProWithoutFee)", [
            'url' => $url,
            'code' => $response['code'],
            'message' => $response['message']
        ]);
    }

    /**
     * @param $consumer_id
     * @return bool
     */
    public function addLabelToConsumer($consumer_id)
    {
        \Log::info("Adicionando label para consumer - (addLabelToConsumer) ");

        $labels = [429, 430]; // 429 - Ambulante | 430 - Verão2019
        foreach ($labels as $label) {
            $url = "Consumers/addLabelToConsumer/{$consumer_id}/{$label}?token={$this->token}";
            $response = $this->client->get($url);
            $url = config('microservices.core_address') . $url;

            $response = $this->response($response);
            if($response['code'] != 200){
                \Log::info("Fail to connect (addLabelToConsumer)", [
                    'url' => $url,
                    'code' => $response['code'],
                    'message' => $response['message']
                ]);
            }

        }

        return true;
    }

    /**
     * @param $consumer_id
     * @return bool
     */
    public function addSalvadorLabelToConsumer($consumer_id)
    {
        \Log::info("Adicionando Reveillon 2020 salvador label para consumer - (addLabelToConsumer) ");

        $labels = [429, 451]; // 429 - Ambulante | 430 - Reveillon salvador
        foreach ($labels as $label) {
            $url = "Consumers/addLabelToConsumer/{$consumer_id}/{$label}?token={$this->token}";
            $response = $this->client->get($url);
            $url = config('microservices.core_address') . $url;

            $response = $this->response($response);
            if($response['code'] != 200){
                \Log::info("Fail to connect (addLabelToConsumer)", [
                    'url' => $url,
                    'code' => $response['code'],
                    'message' => $response['message']
                ]);
            }

        }

        return true;
    }


    /**
     * @param $response
     * @return mixed
     */
    private function response($response)
    {
        if($response->getStatusCode() === 200){
            return json_decode($response->getBody(), true);
        }

    }

    /**
     * Código copiado do Herodash para transformar retorno
     * do Core em objeto
     *
     * @param $response
     * @return object
     */
    protected function parseJsonToObject($response)
    {
        $body = (string) $response->getBody();
        $headers = $response->getHeaders();
        $status = $response->getStatusCode();

        foreach($headers as $i=>$h) {
            $headers[$i] = $h[0];
        }

        return (object) [
            'header' => $headers,
            'body' => json_decode($body)?:[],
            'status' => $status
        ];
    }
}