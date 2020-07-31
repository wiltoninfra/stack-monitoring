<?php

namespace Promo\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Promo\Http\Requests\BlackListedConsumer\BlackListedConsumerCreateRequest;
use Promo\Http\Requests\BlackListedConsumer\BlackListedConsumerUpdateRequest;
use Promo\Services\BlackListedConsumerService;


class BlackListedConsumerController extends Controller
{

    protected $service;

    public function __construct(BlackListedConsumerService $service)
    {
        $this->service = $service;
    }

    /**
     * Cria BlackListed
     *
     * @param BlackListedConsumerCreateRequest $request
     *
     *
     *
     * @return JsonResponse
     *
     * @SWG\Post(
     *     path="/consumers/{consumer_id}/blacklist",
     *     description="Cria BlacklistedConsumer",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     tags={"blacklisted"},
     *     @SWG\Parameter(
     *         name="payload",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(type="object", ref="#/definitions/BlackListedConsumerCreateRequest"),
     *     ),
     *     @SWG\Parameter(
     *         name="consumer_id",
     *         in="path",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(response=201, description="Created"),
     *     @SWG\Response(response=422, description="Validation concern"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     *
     */


    public function create(BlackListedConsumerCreateRequest $request, $consumer_id)
    {
      // A varíavel esta sendo recuperada dentro do FormRequest, REFATORAR.
      // @todo Colocar validacao de consumer_id dentro do service por é uma regra de negócio.

        $black_listed_consumer = $this->service->create($request->validated());

        $this->service->commit();

        return $this->responseOne($black_listed_consumer, Response::HTTP_CREATED);

    }

    /**
     * Obtém BlackListed By Consumer Id
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @SWG\Get(
     *     path="/consumers/{consumer_id}/blacklist",
     *     description="Obtém detalhes do bloqueio de um usuario",
     *     produces={"application/json"},
     *     tags={"blacklisted"},
     *     @SWG\Response(response=200, description="Ok"),
     *     @SWG\Response(response=500, description="Internal server error"),
     *     @SWG\Parameter(
     *         name="consumer_id",
     *         in="path",
     *         required=true,
     *         type="integer"
     *     )
     * )
     */

    public function findByConsumerId(Request $request, int $consumer_id)
    {

        $black_listed_consumer = $this->service->getOneBy(['consumer_id' => $consumer_id]);

        if (is_null($black_listed_consumer)){
            return response()->json(null,Response::HTTP_OK);
        }

        return $this->responseOne($black_listed_consumer);

    }




    /**
     * Atualiza BlacklistedConsumer
     *
     * @param BlackListedConsumerUpdateRequest $request
     *
     *
     *
     * @return JsonResponse
     *
     *
     * @SWG\Put(
     *     path="/consumers/{consumer_id}/blacklist",
     *     description="Atualiza detalhes do bloqueio de usuario",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     tags={"blacklisted"}
     * ,
     * @SWG\Parameter(
     *         name="payload",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(type="object", ref="#/definitions/BlackListedConsumerUpdateRequest"),
     *     ),
     *     @SWG\Parameter(
     *         name="consumer_id",
     *         in="path",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(response=200, description="Accepted"),
     *     @SWG\Response(response=422, description="Validation concern"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function update(BlackListedConsumerUpdateRequest $request)
        // A varíavel esta sendo recuperada dentro do FormRequest, REFATORAR.
        // todo Colocar dentro do service por é uma regra de negócio.

    {
        $blacklisted_consumer = $this->service->updateByConsumerId($request->validated(), $request->consumer_id);

        return $this->responseOne($blacklisted_consumer);
    }

}