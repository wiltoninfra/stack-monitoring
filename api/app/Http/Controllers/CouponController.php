<?php

namespace Promo\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Promo\Exceptions\InvalidCouponException;
use Promo\Services\CouponService;
use Promo\Services\CampaignService;
use Promo\Http\Requests\CouponRequest;
use Promo\Http\Resources\CouponResource;
use Illuminate\Support\Facades\Validator;
use Promo\Services\ConsumerCampaignService;
use Promo\Http\Requests\AssociateCampaignRequest;
use Promo\Http\Requests\UpdateCampaignStatusRequest;
use PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager;


class CouponController extends Controller
{
    /**
     * Serviço CouponService
     *
     * @var \Promo\Services\CouponService
     */
    private $coupon_service;

    public function __construct(CouponService $coupon_service,
                                CampaignService $campaign_service,
                                ConsumerCampaignService $consumer_campaign_service)
    {
        parent::__construct($campaign_service, $consumer_campaign_service);
        $this->coupon_service = $coupon_service;
    }

    /**
     * Cria cupom
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Promo\Exceptions\InvalidCampaignException
     * @throws \Promo\Exceptions\InvalidCouponException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Promo\Exceptions\ValidationException
     *
     * @SWG\Post(
     *     path="/coupons",
     *     description="Cria cupom",
     *     produces={"application/json"},
     *     tags={"coupon"},
     *     @SWG\Parameter(
     *         name="payload",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(type="object", ref="#/definitions/Coupon"),
     *     ),
     *     @SWG\Response(response=201, description="Created"),
     *     @SWG\Response(response=422, description="Validation error"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function create(Request $request)
    {
        Validator::make($request->all(), CouponRequest::rules(false))
            ->validate();

        $coupon = $this->coupon_service->create($request->all());

        return (new CouponResource($coupon))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Obtém todos os cupons
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @SWG\Get(
     *     path="/coupons",
     *     description="Obtém cupons",
     *     produces={"application/json"},
     *     tags={"coupon"},
     *     @SWG\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Ordena por qualquer atributo da raiz. Ex.: sort=-id, ordena por id DESC",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="ids",
     *         in="query",
     *         description="Lista de ids separado por vírgula",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="redirection_type",
     *         in="query",
     *         description="Filtra por tipo de redicionamento",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="code",
     *         in="query",
     *         description="Pesquisa por código",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="active",
     *         in="query",
     *         description="Filtra por active",
     *         required=false,
     *         type="boolean",
     *     ),
     *     @SWG\Parameter(
     *         name="global",
     *         in="query",
     *         description="Filtra por global",
     *         required=false,
     *         type="boolean",
     *     ),
     *     @SWG\Parameter(
     *         name="campaign_id",
     *         in="query",
     *         description="Filtra por id de campanha",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número da página",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="page_size",
     *         in="query",
     *         description="Tamanho da página",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="count",
     *         in="query",
     *         description="Se retorna o total de itens ou não",
     *         required=false,
     *         type="boolean",
     *     ),
     *     @SWG\Response(response=200, description="Ok"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function getAll(Request $request)
    {
        $skip = $request->get('skip');
        $limit = $request->get('limit');
        $criteria = $this->cleanCriteria($request->all());
        $sort = $this->prepareSort($request->get('sort'));

        $coupons = $this->coupon_service->getAll($criteria, $sort, $limit, $skip);

        $response = CouponResource::collection($coupons)
            ->response();

        DocumentManager::flush();

        return filter_var($request->get('count'), FILTER_VALIDATE_BOOLEAN)
            ? $response->header('X-Total-Count', $this->coupon_service->countAll($criteria))
                ->header('X-Total-Active', $this->coupon_service->countActive($criteria))
            : $response;
    }

    /**
     * Associa consumers a um cupom
     *
     * @param \Illuminate\Http\Request $request
     * @param string $coupon_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Promo\Exceptions\InvalidCouponException
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     * @SWG\Patch(
     *     path="/coupons/{coupon_id}/association",
     *     description="Associa consumers um cupom",
     *     produces={"application/json"},
     *     tags={"coupon"},
     *     @SWG\Parameter(
     *         name="coupon_id",
     *         in="path",
     *         description="Id do cupom",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="payload",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(type="object", ref="#/definitions/AssociateCampaign"),
     *     ),
     *     @SWG\Response(response=202, description="Accepted"),
     *     @SWG\Response(response=422, description="Validation error"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function associate(Request $request, string $coupon_id)
    {
        try {
            Validator::make($request->all(), AssociateCampaignRequest::rules())
                ->validate();
            $this->coupon_service->associateConsumers($request->get('consumers'), $coupon_id);
        } catch (InvalidCouponException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 409);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }

        return response(null, Response::HTTP_ACCEPTED);
    }

    /**
     * Atualiza estado do cupom
     *
     * @param \Illuminate\Http\Request $request
     * @param string $coupon_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @SWG\Patch(
     *     path="/coupons/{coupon_id}/status",
     *     description="Atualiza estado do cupom",
     *     produces={"application/json"},
     *     tags={"coupon"},
     *     @SWG\Parameter(
     *         name="coupon_id",
     *         in="path",
     *         description="Id do coupon",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="payload",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(type="object", ref="#/definitions/UpdateCampaignStatus"),
     *     ),
     *     @SWG\Response(response=202, description="Accepted"),
     *     @SWG\Response(response=409, description="Conflict"),
     *     @SWG\Response(response=422, description="Validation error"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     * @throws \Promo\Exceptions\InvalidCouponException
     */
    public function updateStatus(Request $request, string $coupon_id)
    {
        Validator::make($request->all(), UpdateCampaignStatusRequest::rules())
            ->validate();

        $campaign = $this->coupon_service->updateStatus((bool) $request->get('active'), $coupon_id);

        return (new CouponResource($campaign))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    /**
     * Atualiza um cupom
     *
     * @param \Illuminate\Http\Request $request
     * @param string $coupon_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Promo\Exceptions\ValidationException
     * @SWG\Put(
     *     path="/coupons/{coupon_id}",
     *     description="Atualiza um cupom",
     *     produces={"application/json"},
     *     tags={"coupon"},
     *     @SWG\Parameter(
     *         name="coupon_id",
     *         in="path",
     *         description="ID do cupom",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="payload",
     *         in="body",
     *         required=true,
     *         description="Os campos 'code' e 'campaign_id' não podem ser editados",
     *         @SWG\Schema(type="object", ref="#/definitions/Coupon"),
     *     ),
     *     @SWG\Response(response=202, description="Accepted"),
     *     @SWG\Response(response=422, description="Validation error"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function update(Request $request, string $coupon_id)
    {
        Validator::make($request->all(), CouponRequest::rules(true))
            ->validate();

        $coupon = $this->coupon_service->update($request->all(), $coupon_id);

        return (new CouponResource($coupon))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

}
