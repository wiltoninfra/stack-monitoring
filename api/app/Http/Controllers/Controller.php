<?php

namespace Promo\Http\Controllers;

use Illuminate\Http\Response;
use Promo\Services\CampaignService;
use Promo\Services\ConsumerCampaignService;
use Laravel\Lumen\Routing\Controller as BaseController;

/**
 * @SWG\Swagger(
 *     basePath="/api/v1",
 *     schemes={"http"},
 *     @SWG\Info(
 *         version="1.0",
 *         title="Promo API",
 *         description="Promo API by PicPay",
 *     ),
 * )
 */
class Controller extends BaseController
{
    protected $resourceClass;

    /** @var \Promo\Services\CampaignService */
    protected $campaign_service;

    /** @var \Promo\Services\ConsumerCampaignService */
    protected $consumer_campaign_service;

    public function __construct(CampaignService $campaign_service, ConsumerCampaignService $consumer_campaign_service)
    {
        $this->campaign_service = $campaign_service;
        $this->consumer_campaign_service = $consumer_campaign_service;
    }

    /**
     * Método que remove entradas desnecessárias do request,
     * entregando um array de critérios limpo que pode ser
     * diretamente usado em uma query no Doctrine
     *
     * @param array $all
     * @return array
     */
    protected function cleanCriteria(array $all) : array
    {
        return array_diff_key($all, array_flip(['q', 'skip', 'limit', 'include',
                                                        'page', 'page_size', 'sort', 'count']));
    }

    /**
     * Transforma sort do padrão PicPay para o
     * padrão Mongo/Doctrine
     *
     * @param array $sort
     * @return array|null
     */
    protected function prepareSort(string $sort = null)
    {
        if (!isset($sort))
        {
            return [];
        }

        $sort = explode(',', $sort);
        $new_sort = [];

        array_map(function($key) use (&$new_sort) {
            if (isset($key) && strlen($key) >= 1)
            {
                if ($key[0] === '-')
                {
                    $key = substr($key, 1);
                    $new_sort[$key] = 'desc';
                }
                else
                {
                    $new_sort[$key] = 'asc';
                }

            }
        }, $sort);

        return $new_sort;
    }

    /**
     * @param $document
     * @param int $code
     * @param bool $resourceClassName
     * @return mixed
     * @throws \ReflectionException
     */
    protected function responseOne($document, $code = Response::HTTP_OK, $resourceClassName = false){
        if (!$resourceClassName){
            $resourceClassName = 'Promo\Http\Resources\\'.$this->getDocumentName().'\\'.$this->getDocumentName().'Resource';
        }
        return (new $resourceClassName($document))->response()->setStatusCode($code);
    }

    /**
     * @param $collection
     * @param int $code
     * @param bool $resourceClassName
     * @return mixed
     * @throws \ReflectionException
     */
    protected function responseAll($collection, $code = Response::HTTP_OK,$resourceClassName = false)
    {
        $resourceName = 'Promo\Http\Resources\\' . $this->getDocumentName() . '\\' . $this->getDocumentName() . 'Resource';
        $response = $resourceName::collection($collection['data'])->response()->setStatusCode($code);
        if (array_key_exists('pagination', $collection)) {
            $response->withHeaders($collection['pagination']);
        }
        if (array_key_exists('total_count', $collection)) {
            $response->header('X-Total-Count',$collection['total_count']);
        }
        return $response;
    }

    /**
     * @return string|string[]
     * @throws \ReflectionException
     */
    protected function getDocumentName(){
        $name = (new \ReflectionClass($this))->getShortName();
        return str_replace('Controller','', $name );
    }


}
