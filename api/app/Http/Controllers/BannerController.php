<?php

namespace Promo\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Promo\Services\ImageService;
use Promo\Services\BannerService;
use Promo\Services\CampaignService;
use Promo\Http\Requests\BannerRequest;
use Promo\Http\Resources\BannerResource;
use Illuminate\Support\Facades\Validator;
use Promo\Services\ConsumerCampaignService;
use Promo\Http\Requests\BannerUpdateRequest;
use Promo\Http\Requests\PublishBannersRequest;
use Promo\Http\Requests\UpdateCampaignStatusRequest;

class BannerController extends Controller
{
    /**
     * Serviço de Banner
     *
     * @var \Promo\Services\BannerService
     */
    protected $banner_service;

    /**
     * Serviço de Banner
     *
     * @var \Promo\Services\ImageService
     */
    protected $image_service;

    public function __construct(BannerService $banner_service,
                                ImageService $image_service,
                                CampaignService $campaign_service,
                                ConsumerCampaignService $consumer_campaign_service)
    {
        parent::__construct($campaign_service, $consumer_campaign_service);
        $this->banner_service = $banner_service;
        $this->image_service = $image_service;
    }

    /**
     * Cria banner
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @SWG\Post(
     *     path="/banners",
     *     description="Cria banner",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     tags={"banner"},
     *     @SWG\Parameter(
     *         name="payload",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(type="object", ref="#/definitions/Banner"),
     *     ),
     *     @SWG\Response(response=201, description="Created"),
     *     @SWG\Response(response=409, description="Conflict"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function create(Request $request)
    {
        Validator::make($request->all(), BannerRequest::rules())
            ->validate();

        $data = $request->all();
        $image_url = $this->image_service->upload($request->get('image'));
        $data['image'] = $image_url;

        $banner = $this->banner_service->create($data);

        return (new BannerResource($banner))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Obtém um banner
     *
     * @param \Illuminate\Http\Request $request
     * @param string $banner_id
     * @return BannerResource
     *
     * @SWG\Get(
     *     path="/banners/{banner_id}",
     *     description="Obtém uma banner",
     *     produces={"application/json"},
     *     tags={"banner"},
     *     @SWG\Parameter(
     *         name="banner_id",
     *         in="path",
     *         description="Id do banner",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(response=200, description="Ok"),
     *     @SWG\Response(response=404, description="Banner não encontrado"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function get(Request $request, string $banner_id)
    {
        $banner = $this->banner_service->get($banner_id);

        return (new BannerResource($banner));
    }

    /**
     * Obtém todos os banners
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @SWG\Get(
     *     path="/banners",
     *     description="Obtém banners",
     *     produces={"application/json"},
     *     tags={"banner"},
     *     @SWG\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Ordena por qualquer atributo da raiz. Ex.: sort=-id, ordena por id DESC",
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

        $coupons = $this->banner_service->getAll($criteria, $sort, $limit, $skip);

        $response = BannerResource::collection($coupons)
            ->response();

        return filter_var($request->get('count'), FILTER_VALIDATE_BOOLEAN)
            ? $response->header('X-Total-Count', $this->banner_service->countAll($criteria))
            : $response;
    }

    /**
     * Atualiza banner
     *
     * @param \Illuminate\Http\Request $request
     * @param string $banner_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @SWG\Put(
     *     path="/banners/{banner_id}",
     *     description="Atualiza banner",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     tags={"banner"},
     *     @SWG\Parameter(
     *         name="banner_id",
     *         in="path",
     *         description="Id do banner",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="payload",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(type="object", ref="#/definitions/BannerUpdate"),
     *     ),
     *     @SWG\Response(response=201, description="Created"),
     *     @SWG\Response(response=409, description="Conflict"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function update(Request $request, string $banner_id)
    {
        $data = $request->all();
        Validator::make($data, BannerUpdateRequest::rules())
            ->validate();

        $data['image'] = $request->get('image') ?  $this->image_service->upload($request->get('image')) : null;

        $banner = $this->banner_service->update($data, $banner_id);
        return (new BannerResource($banner))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    /**
     * Atualiza estado do banner
     *
     * @param \Illuminate\Http\Request $request
     * @param string $banner_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Promo\Exceptions\ExpiredBannerException
     * @SWG\Patch(
     *     path="/banners/{banner_id}/status",
     *     description="Atualiza estado do banner",
     *     produces={"application/json"},
     *     tags={"banner"},
     *     @SWG\Parameter(
     *         name="banner_id",
     *         in="path",
     *         description="Id do banner",
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
     */
    public function updateStatus(Request $request, string $banner_id)
    {
        Validator::make($request->all(), UpdateCampaignStatusRequest::rules())
            ->validate();

        $campaign = $this->banner_service->updateStatus((bool) $request->get('active'), $banner_id);

        return (new BannerResource($campaign))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    /**
     * Exclui banner
     *
     * @param \Illuminate\Http\Request $request
     * @param string $banner_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @SWG\Delete(
     *     path="/banners/{banner_id}",
     *     description="Exclui banner (soft delete)",
     *     produces={"application/json"},
     *     tags={"banner"},
     *     @SWG\Parameter(
     *         name="banner_id",
     *         in="path",
     *         description="Id do banner",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(response=202, description="Deleted"),
     *     @SWG\Response(response=404, description="Campaign not found"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function delete(Request $request, string $banner_id)
    {
        $this->banner_service->delete($banner_id);

        return response(null, Response::HTTP_ACCEPTED);
    }

    /**
     * @param Request $request
     * @return Response|\Laravel\Lumen\Http\ResponseFactory
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     *
     * @SWG\Patch(
     *     path="/banners/publish",
     *     description="Publica os banners",
     *     produces={"application/json"},
     *     tags={"banner"},
     *     @SWG\Parameter(
     *         name="payload",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(type="object", ref="#/definitions/PublishBanners"),
     *     ),
     *     @SWG\Response(response=202, description="Accepted"),
     *     @SWG\Response(response=409, description="Conflict"),
     *     @SWG\Response(response=422, description="Validation error"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function publish(Request $request)
    {
        Validator::make($request->all(), PublishBannersRequest::rules())
            ->validate();

        $this->banner_service->publish($request->ids);

        return response(null, Response::HTTP_ACCEPTED);
    }
}
