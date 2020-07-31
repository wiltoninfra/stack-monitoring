<?php

namespace Promo\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Promo\Http\Requests\WebhookGetRequest;
use Promo\Http\Requests\WebhookRequest;
use Promo\Http\Resources\WebhookResource;
use Promo\Services\WebhookService;
use Illuminate\Support\Facades\Validator;

class WebhookController
{

    private $webhook_service;

    public function __construct(WebhookService $webhook_service)
    {
        $this->webhook_service = $webhook_service;
    }

    /**
     * Obtém um webhook
     *
     * @param \Illuminate\Http\Request $request
     * @param string $webhook_id
     * @return WebhookResource
     *
     * @SWG\Get(
     *     path="/webhooks/{webhook_id}",
     *     description="Obtém uma webhook",
     *     produces={"application/json"},
     *     tags={"webhook"},
     *     @SWG\Parameter(
     *         name="webhook_id",
     *         in="path",
     *         description="Id do webhook",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(response=200, description="Ok"),
     *     @SWG\Response(response=404, description="Banner não encontrado"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function get(Request $request, string $webhook_id)
    {
        Validator::make([
            'webhook_id' => $webhook_id], [
            'webhook_id' => 'required|string',
        ])->validate();

        $webhook = $this->webhook_service->get($webhook_id);

        return (new WebhookResource($webhook));
    }

    /**
     * Obtém todos os webhooks
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @SWG\Get(
     *     path="/webhooks",
     *     description="Obtém webhooks",
     *     produces={"application/json"},
     *     tags={"webhook"},
     *
     *     @SWG\Parameter(
     *         name="campaign_id",
     *         in="query",
     *         description="Id da campanha",
     *         type="string"
     *     ),
     *
     *     @SWG\Parameter(
     *         name="coupon_id",
     *         in="query",
     *         description="Id do cupom",
     *         type="string"
     *     ),
     *
     *     @SWG\Response(response=200, description="Ok"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function getAll(Request $request)
    {
        Validator::make($request->all(), WebhookGetRequest::rules())
            ->validate();

        $webhooks = $this->webhook_service->getAll($request->all());
        return WebhookResource::collection($webhooks)
            ->response();
    }

    /**
     * Cria webhook
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Doctrine\ODM\MongoDB\LockException
     * @throws \Doctrine\ODM\MongoDB\Mapping\MappingException
     * @throws \Promo\Exceptions\InvalidCampaignException
     * @throws \Promo\Exceptions\InvalidCouponException
     * @SWG\Post(
     *     path="/webhooks",
     *     description="Cria webhook",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     tags={"webhook"},
     *
     *     @SWG\Parameter(
     *         name="payload",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(type="object", ref="#/definitions/Webhook"),
     *     ),
     *     @SWG\Response(response=201, description="Created"),
     *     @SWG\Response(response=422, description="Validation concern"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function create(Request $request)
    {
        Validator::make($request->all(), WebhookRequest::rules($request->only('campaign_id')))
            ->validate();

        $webhook = $this->webhook_service->create($request->all());

        return (new WebhookResource($webhook))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Atualiza webhook
     *
     * @param \Illuminate\Http\Request $request
     * @param string $webhook_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Doctrine\ODM\MongoDB\LockException
     * @throws \Doctrine\ODM\MongoDB\Mapping\MappingException
     * @throws \Promo\Exceptions\InvalidCampaignException
     * @throws \Promo\Exceptions\InvalidCouponException
     * @SWG\Put(
     *     path="/webhooks/{webhook_id}",
     *     description="Atualiza webhook",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     tags={"webhook"},
     *     @SWG\Parameter(
     *         name="webhook_id",
     *         in="path",
     *         description="Id do webhook",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="payload",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(type="object", ref="#/definitions/Webhook"),
     *     ),
     *     @SWG\Response(response=202, description="Accepted"),
     *     @SWG\Response(response=422, description="Validation concern"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function update(Request $request, string $webhook_id)
    {
        Validator::make($request->all(), WebhookRequest::rules($request->only('campaign_id')))
            ->validate();

        $webhook = $this->webhook_service->update($webhook_id, $request->all());

        return (new WebhookResource($webhook))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    /**
     * Exclui webhook
     *
     * @param \Illuminate\Http\Request $request
     * @param string $webhook_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @SWG\Delete(
     *     path="/webhooks/{webhook_id}",
     *     description="Exclui webhook (soft delete)",
     *     produces={"application/json"},
     *     tags={"webhook"},
     *     @SWG\Parameter(
     *         name="webhook_id",
     *         in="path",
     *         description="Id do webhook",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(response=204, description="Deleted"),
     *     @SWG\Response(response=404, description="Webhook not found"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function delete(Request $request, string $webhook_id)
    {
        $this->webhook_service->delete($webhook_id);
        return response(null, Response::HTTP_NO_CONTENT);
    }
}