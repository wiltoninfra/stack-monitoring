<?php

namespace Promo\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Promo\Documents\Webhook;
use Promo\Events\Mixpanel\CampaignAssociateMessageSentEvent;
use Promo\Exceptions\PromoException;
use Promo\Http\Requests\ApplyPermissionsRequest;
use Promo\Http\Requests\CampaignRequest;
use Illuminate\Support\Facades\Validator;
use Promo\Http\Resources\CampaignResource;
use Promo\Http\Requests\TagAssociationRequest;
use Promo\Http\Resources\CampaignSellerResource;
use Promo\Http\Resources\CampaignStatsResource;
use Promo\Http\Requests\AssociateCampaignRequest;
use Promo\Http\Requests\UpdateCampaignStatusRequest;
use Promo\Http\Requests\UpdateCampaignSellersRequest;
use PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager;
use Promo\Http\Resources\CampaignTreadResource;
use Promo\Http\Resources\NotificationTemplateRulerResource;


//kainan (retirar antes do deploy em prod)
use Promo\Clients\MixPanelClient;
use Promo\Documents\Enums\MixPanelEventsEnum;
use PicPay\Snspp\EventClient;
use Illuminate\Support\Facades\Log;
use Promo\Http\Resources\WebhookResource;
use Promo\Services\Notification\Template\NotificationTemplate;
use \Promo\Services\WebhookService;
use Illuminate\Support\Facades\Cache;
//kainan (retirar antes do deploy em prod)

class CampaignController extends Controller
{

    /**
     * Cria campanha
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Promo\Exceptions\ValidationException
     * @SWG\Post(
     *     path="/campaigns",
     *     description="Cria campanha",
     *     produces={"application/json"},
     *     tags={"campaign"},
     *     @SWG\Parameter(
     *         name="payload",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(type="object", ref="#/definitions/Campaign"),
     *     ),
     *     @SWG\Response(response=201, description="Created"),
     *     @SWG\Response(response=422, description="Validation stuff"),
     *     @SWG\Response(response=409, description="Conflict"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function create(Request $request)
    {
        Validator::make($request->all(), CampaignRequest::rules($request->get('type')))
            ->validate();


        $campaign = $this->campaign_service->create($request->all());
        return (new CampaignResource($campaign))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Exclui campanha
     *
     * @param \Illuminate\Http\Request $request
     * @param string $campaign_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @SWG\Delete(
     *     path="/campaigns/{campaign_id}",
     *     description="Exclui campanha (soft delete)",
     *     produces={"application/json"},
     *     tags={"campaign"},
     *     @SWG\Parameter(
     *         name="campaign_id",
     *         in="path",
     *         description="Id da campanha",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(response=204, description="Deleted"),
     *     @SWG\Response(response=404, description="Campaign not found"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function delete(Request $request, string $campaign_id)
    {
        $this->campaign_service->delete($campaign_id);

        return response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Atualiza campanha
     *
     * @param \Illuminate\Http\Request $request
     * @param string $campaign_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Promo\Exceptions\ValidationException
     * @SWG\Put(
     *     path="/campaigns/{campaign_id}",
     *     description="Atualiza campanha",
     *     produces={"application/json"},
     *     tags={"campaign"},
     *     @SWG\Parameter(
     *         name="campaign_id",
     *         in="path",
     *         description="Id da campanha",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="payload",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(type="object", ref="#/definitions/Campaign"),
     *     ),
     *     @SWG\Response(response=202, description="Accepted"),
     *     @SWG\Response(response=422, description="Validation error"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function update(Request $request, string $campaign_id)
    {
        Validator::make($request->all(), CampaignRequest::rules($request->get('type')))
            ->validate();

        $campaign = $this->campaign_service->update($request->all(), $campaign_id);

        return (new CampaignResource($campaign))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    /**
     * Atualiza estado de campanha
     *
     * @param \Illuminate\Http\Request $request
     * @param string $campaign_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Promo\Exceptions\ExpiredCampaignException
     * @SWG\Patch(
     *     path="/campaigns/{campaign_id}/status",
     *     description="Atualiza estado de campanha",
     *     produces={"application/json"},
     *     tags={"campaign"},
     *     @SWG\Parameter(
     *         name="campaign_id",
     *         in="path",
     *         description="Id da campanha",
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
    public function updateStatus(Request $request, string $campaign_id)
    {

        Validator::make($request->all(), UpdateCampaignStatusRequest::rules())
            ->validate();

        $campaign = $this->campaign_service->updateStatus((bool)$request->get('active'), $campaign_id);

        return (new CampaignResource($campaign))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    /**
     * Associa consumers a campanha
     *
     * @param \Illuminate\Http\Request $request
     * @param string $campaign_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \MongoException
     * @throws \Promo\Exceptions\GlobalCampaignException
     * @SWG\Patch(
     *     path="/campaigns/{campaign_id}/association",
     *     description="Associa consumers a campanha",
     *     produces={"application/json"},
     *     tags={"campaign"},
     *     @SWG\Parameter(
     *         name="campaign_id",
     *         in="path",
     *         description="Id da campanha",
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
    public function associate(Request $request, string $campaign_id)
    {
        try {
            Validator::make($request->all(), AssociateCampaignRequest::rules())
                ->validate();

            //Esse metodo realiza a associacao dos usuarios e retorna SOMENTE os nao inseridos por estarem na Blacklist
            $blacklisted = $this->consumer_campaign_service->associateConsumers($request->get('consumers'), $campaign_id);

            //Se existem usuarios na blacklist retornar
            if (count($blacklisted) > 0) {
                return response(['blacklisted' => $blacklisted], Response::HTTP_PRECONDITION_FAILED);
            }

            return response(null, Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            return response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }



    /**
     * Atualiza sellers da campanha
     *
     * @param string $consumer_id
     * @param string $campaign_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \MongoException
     * @throws \Promo\Exceptions\GlobalCampaignException
     * @SWG\Patch(
     *     path="/campaigns/{campaign_id}/add-consumer/{consumer_id}",
     *     description="Atualiza consumers da campanha",
     *     produces={"application/json"},
     *     tags={"campaign"},
     *     @SWG\Parameter(
     *         name="campaign_id",
     *         in="path",
     *         description="Id da campanha",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="consumer_id",
     *         in="path",
     *         description="Id do consumer",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(response=202, description="Accepted"),
     *     @SWG\Response(response=422, description="Validation error"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function addConsumer(string $consumer_id, string $campaign_id)
    {

        $campaign = $this->campaign_service->addConsumer((int)$consumer_id, $campaign_id);

        return (new CampaignResource($campaign))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    /**
     * Atualiza sellers da campanha
     *
     * @param \Illuminate\Http\Request $request
     * @param string $campaign_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \MongoException
     * @throws \Promo\Exceptions\GlobalCampaignException
     * @SWG\Patch(
     *     path="/campaigns/{campaign_id}/sellers",
     *     description="Atualiza sellers da campanha",
     *     produces={"application/json"},
     *     tags={"campaign"},
     *     @SWG\Parameter(
     *         name="campaign_id",
     *         in="path",
     *         description="Id da campanha",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="payload",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(type="object", ref="#/definitions/UpdateCampaignSellers"),
     *     ),
     *     @SWG\Response(response=202, description="Accepted"),
     *     @SWG\Response(response=422, description="Validation error"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function updateSellers(Request $request, string $campaign_id)
    {
        Validator::make($request->all(), UpdateCampaignSellersRequest::rules())
            ->validate();

        $campaign = $this->campaign_service->updateSellers($request->get('sellers'), $campaign_id);

        return (new CampaignResource($campaign))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    /**
     * Remove sellers da campanha
     *
     * @param \Illuminate\Http\Request $request
     * @param string $campaign_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \MongoException
     * @SWG\Patch(
     *     path="/campaigns/{campaign_id}/removeSellers",
     *     description="Remove sellers da campanha",
     *     produces={"application/json"},
     *     tags={"campaign"},
     *     @SWG\Parameter(
     *         name="campaign_id",
     *         in="path",
     *         description="Id da campanha",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="payload",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(type="object", ref="#/definitions/UpdateCampaignSellers"),
     *     ),
     *     @SWG\Response(response=202, description="Accepted"),
     *     @SWG\Response(response=422, description="Validation error"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function removeSellers(Request $request, string $campaignId)
    {
        Validator::make($request->all(), UpdateCampaignSellersRequest::rules())->validate();

        $campaign = $this->campaign_service->removeSellers($request->get('sellers'), $campaignId);

        return response()->json($campaign->getId(), Response::HTTP_ACCEPTED);
    }

    /**
     * Desassocia consumers a campanha
     *
     * @param \Illuminate\Http\Request $request
     * @param string $campaign_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \MongoException
     * @throws \Promo\Exceptions\GlobalCampaignException
     * @SWG\Delete(
     *     path="/campaigns/{campaign_id}/association",
     *     description="Desassocia consumers a campanha",
     *     produces={"application/json"},
     *     tags={"campaign"},
     *     @SWG\Parameter(
     *         name="campaign_id",
     *         in="path",
     *         description="Id da campanha",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="payload",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(type="object", ref="#/definitions/AssociateCampaign"),
     *     ),
     *     @SWG\Response(response=200, description="Ok"),
     *     @SWG\Response(response=422, description="Validation error"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function disassociate(Request $request, string $campaign_id)
    {
        Validator::make($request->all(), AssociateCampaignRequest::rules())
            ->validate();

        $this->consumer_campaign_service->disassociateConsumers($request->get('consumers'), $campaign_id);

        return response(null, Response::HTTP_OK);
    }

    /**
     * Obtém uma campanha
     *
     * @param \Illuminate\Http\Request $request
     * @param string $campaign_id
     * @return CampaignResource
     *
     * @SWG\Get(
     *     path="/campaigns/{campaign_id}",
     *     description="Obtém uma campanha específica",
     *     produces={"application/json"},
     *     tags={"campaign"},
     *     @SWG\Parameter(
     *         name="campaign_id",
     *         in="path",
     *         description="Id da campanha",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(response=200, description="Ok"),
     *     @SWG\Response(response=404, description="Campanha não encontrada"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function get(Request $request, string $campaign_id)
    {
        $campaign = $this->campaign_service->get($campaign_id);

        return (new CampaignResource($campaign));
    }

    public function getTreated(Request $request, string $campaign_id)
    {
        $campaign = $this->campaign_service->get($campaign_id);

        return (new CampaignTreadResource($campaign));
    }

    /**
     * Obtém todas as campanhas
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @SWG\Get(
     *     path="/campaigns",
     *     description="Obtém campanhas",
     *     produces={"application/json"},
     *     tags={"campaign"},
     *     @SWG\Parameter(
     *         name="name",
     *         in="query",
     *         description="Filtra por nome da campanha",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Ordena por qualquer atributo da raiz. Ex.: sort=-id, ordena por id DESC",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         in="query",
     *         description="Filtra por id",
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
     *         name="type",
     *         in="query",
     *         description="Filtra por type",
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
     *         name="start_date",
     *         in="query",
     *         description="Filtra por datas de início a partir da informada, em ISO 8601",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Filtra por datas de fim até a informada, em ISO 8601",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="expire_date",
     *         in="query",
     *         description="Filtra campanhas que expiram até o fim do dia da data informada, em ISO 8601",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="consumers",
     *         in="query",
     *         description="Filtra por IDs de consumers, separados por vírgula",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="sellers",
     *         in="query",
     *         description="Filtra por IDs de sellers, separados por vírgula",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="tags",
     *         in="query",
     *         description="Filtra por IDs de tags, separados por vírgula",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="sellers_types",
     *         in="query",
     *         description="Filtra por tipos de sellers, separados por vírgula",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="transaction_type",
     *         in="query",
     *         description="Filtra por tipos de transação da campanha",
     *         required=false,
     *         type="string",
     *         enum={"p2p", "pav"}
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

        $campaigns = $this->campaign_service->getAll($criteria, $sort, $limit, $skip);

        $response = CampaignResource::collection($campaigns)
            ->response();

        DocumentManager::flush();

        return filter_var($request->get('count'), FILTER_VALIDATE_BOOLEAN)
            ? $response->header('X-Total-Count', $this->campaign_service->countAll($criteria))
                ->header('X-Total-Active', $this->campaign_service->countActive($criteria))
            : $response;
    }

    /**
     * Obtém tipos de campanha
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @SWG\Get(
     *     path="/campaigns/types",
     *     description="Obtém tipos possíveis de campanhas",
     *     produces={"application/json"},
     *     tags={"campaign"},
     *     @SWG\Response(response=200, description="Ok"),
     * )
     */
    public function getTypes(Request $request)
    {
        $types = $this->campaign_service->getTypes();

        return response($types, Response::HTTP_OK);
    }

    /**
     * Obtém as estatísticas de uma campanha
     *
     * @param Request $request
     * @param string $campaign_id
     * @return CampaignStatsResource
     *
     * @SWG\Get(
     *     path="/campaigns/{campaign_id}/stats",
     *     description="Obtém as estatísticas de uma campanha específica",
     *     produces={"application/json"},
     *     tags={"campaign"},
     *     @SWG\Parameter(
     *         name="campaign_id",
     *         in="path",
     *         description="ID da campanha",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(response=200, description="Ok"),
     *     @SWG\Response(response=404, description="Campanha não encontrada"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     *
     */
    public function getStats(Request $request, string $campaign_id)
    {
        $campaign_stats = $this->campaign_service->getStats($campaign_id);

        return (new CampaignStatsResource($campaign_stats));
    }

    /**
     * Atualiza tags de campanha
     *
     * @param \Illuminate\Http\Request $request
     * @param string $campaign_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @SWG\Patch(
     *     path="/campaigns/{campaign_id}/tags",
     *     description="Atualiza tags de campanha",
     *     produces={"application/json"},
     *     tags={"campaign"},
     *     @SWG\Parameter(
     *         name="campaign_id",
     *         in="path",
     *         description="Id da campanha",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="payload",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(type="object", ref="#/definitions/TagAssociation"),
     *     ),
     *     @SWG\Response(response=202, description="Accepted"),
     *     @SWG\Response(response=422, description="Validation error"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function replaceTags(Request $request, string $campaign_id)
    {
        Validator::make($request->all(), TagAssociationRequest::rules())
            ->validate();

        $this->campaign_service->replaceTags($campaign_id, $request->get('tags'));

        return response(null, Response::HTTP_ACCEPTED);
    }

    /**
     * Aplicar as permissoes da campanha
     *
     * @param \Illuminate\Http\Request $request
     * @param string $campaign_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @SWG\Patch(
     *     path="/campaigns/{campaign_id}/permissions",
     *     description="Aplicar as permissoes da campanha",
     *     produces={"application/json"},
     *     tags={"campaign"},
     *     @SWG\Parameter(
     *         name="campaign_id",
     *         in="path",
     *         description="Id da campanha",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="payload",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(type="object", ref="#/definitions/ApplyPermissions"),
     *     ),
     *     @SWG\Response(response=202, description="Accepted"),
     *     @SWG\Response(response=422, description="Validation error"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function applyPermissions(Request $request, string $campaign_id)
    {
        Validator::make($request->all(), ApplyPermissionsRequest::rules())
            ->validate();

        $request = $request->only(array_keys(ApplyPermissionsRequest::rules()));

        $campaign = $this->campaign_service->applyPermissions($campaign_id, $request);

        return response(null, Response::HTTP_ACCEPTED);
    }

    public function publish(string $campaignId)
    {
        try {
            $this->campaign_service->publishOnSlack($campaignId);

            return response()->json(
                [
                    'status' => 'success',
                    'data' => '',
                ],
                200
            );

        } catch (PromoException $e) {
            return response()->json(
                [
                    'status' => 'fail',
                    'message' => $e->getMessage(),
                ],
                422
            );
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ],
                500
            );
        }
    }

    /**
     * Obtém os dados da camapanha em formato para GDash
     *
     * @param Request $request
     * @param string $campaign_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @SWG\Get(
     *     path="/campaigns/{campaign_id}/notification-message-ruler",
     *     description="Obtém os dados da camapanha em formato para GDash",
     *     produces={"application/json"},
     *     tags={"campaign"},
     *     @SWG\Parameter(
     *         name="campaign_id",
     *         in="path",
     *         description="ID da campanha",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(response=200, description="Ok"),
     *     @SWG\Response(response=404, description="Campanha não encontrada"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     *
     */
    public function notificationTemplateRuler(string $campaign_id){

        try {
            $data = $this->campaign_service->notificationTemplateRuler($campaign_id);

            return (new NotificationTemplateRulerResource($data))
                ->response()
                ->setStatusCode(Response::HTTP_OK);

        } catch (PromoException $e) {
            return response()->json(
                [
                    'status' => 'fail',
                    'message' => $e->getMessage(),
                ],
                Response::HTTP_NOT_FOUND
            );
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @param Request $request
     * @param $campaignId
     * @return JsonResponse
     *
     * @SWG\POST(
     *     path="/campaings/{campaign_id}/checkSellers",
     *     description="Obtém a validacao se os sellersIds estao na campanha",
     *     produces={"application/json"},
     *     tags={"campign"},
     *     @SWG\Parameter(
     *         name="campaign_id",
     *         in="path",
     *         description="ID da campanha",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *          name="sellers",
     *          in="body",
     *          required=true,
     *          type="array",
     *          @SWG\Schema(type="object", ref="#/definitions/SellersInfo"),
     *     ),
     *     @SWG\Response(response=200, description="Ok"),
     *     @SWG\Response(response=403, description="Fornidden"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     *
     * @SWG\Definition(
     *     definition="SellersInfo",
     *     type="object",
     *     @SWG\Property(property="sellers", type="array", @SWG\Items(type="sellers", example=1)),
     * )
     */
    public function checkSellers(Request $request, $campaignId)
    {
        try {
            $campaignSellers = $this->campaign_service->checkSellers($request->get('sellers'), $campaignId);

            return response()->json($campaignSellers, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Obtém todas as campanhas de um seller
     *
     * @param Request $request
     * @param $sellerId
     * @return \Illuminate\Http\JsonResponse
     *
     * * @SWG\Get(
     *     path="/seller/{sellerId/campaigns}",
     *     description="Obtém campanhas de um sellerId",
     *     produces={"application/json"},
     *     tags={"campaign"},
     *     @SWG\Parameter(
     *         name="sellerId",
     *         in="query",
     *         description="Id do seller",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="page_size",
     *         in="query",
     *         description="Quantidade de itens por página",
     *         required=false,
     *         type="integer",
     *     ),
     *      @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="número da página",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Response(response=200, description="Ok"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function getCampaignsBySeller(Request $request, string $sellerId)
    {
        Validator::make([
            'sellerId' => $sellerId], [
            'sellerId' => 'required|integer',
        ])->validate();

        // paginação
        $skip = $request->get('skip');
        $limit = $request->get('limit');

        $campaigns = $this->campaign_service->getBySeller(intval($sellerId), $limit, $skip);

        return CampaignSellerResource::collection($campaigns)->response();
    }

    public function get_string_between($string, $start, $end){
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }

    //kainan (retirar antes do deploy em prod)
    public function testDispatch(Request $request)
    {
//        try{
//            $data = [
//                'distinct_id' => 8586208,
//                'Nome da Variacao' => 'teste',
//                'Variacao Recebida' => 'Bora ver qualé',
//                'ID da Campanha' => "0ads09jd9asj9das9jd0askjdja9dj98saj9dsj0d0jsj0dsjdj0sj0ds",
//                'Nome da Campanha' => 'nome da camapnha',
//            ];
//
//            $mixpanel = new MixPanelClient();
//
//            $mixpanel->track(
//                8586208,
//                MixPanelEventsEnum::CAMPAIGN_DELIVERY,
//                $data
//            );
//
//            return Response()->json(['success' => true]);
//
//        }catch (\Exception $e){
//            return Response()->json(['message' => $e->getMessage()]);
//        }

        try{

            $data = $request->all();
            unset($data['q']);

            \PicPay\Brokers\Facades\Producer::produce("lambda_mixpanel_webhook_promo_associate-campaign-associate",
                $data
            );

            return Response()->json([
                'success' => true
            ]);

        }catch (\Exception $e){

            return Response()->json(['message' => $e->getMessage()]);
        }

    }

    //kainan (retirar antes do deploy em prod)
    /**
     * @param Webhook $webhook
     * @return array
     */
    private function createVariantsData(Webhook $webhook) : array
    {
        $variantsData = [];
        foreach ($webhook->getVariants() as $variant){

            $variantsData['campaign']['id'] = $webhook->getCampaign()->getId();
            $variantsData['campaign']['name'] = $webhook->getCampaign()->getName();
            $variantsData['campaign']['active'] = $webhook->getCampaign()->isActive();
            $variantsData['campaign']['communication'] = $webhook->getCampaign()->isCommunication();

            $variantPayload = [
                'webhook_id' => $webhook->getId(),
                'webhook_percentage' => $variant->getPercentage(),
                'webhook_variant_name' => $variant->getName(),
                'campaign' => [
                    'id' => $webhook->getCampaign()->getId(),
                    'name' => $webhook->getCampaign()->getName(),
                ],
                'target' => [
                    'model' => $variant->getTarget()->getModel(),
                    'href' => $variant->getTarget()->getHref(),
                    'params' => $variant->getTarget()->getParams(),
                    'user_properties' => $variant->getTarget()->getUserProperties(),
                    'mixpanel_properties' => $variant->getTarget()->getMixpanelProperties()
                ]
            ];

            if ($variant->getPush()) {
                $variantPayload['push'] = [
                    'title' => $variant->getPush()->getTitle(),
                    'message' => $variant->getPush()->getMessage(),
                ];
            }

            if ($variant->getInApp()) {
                $variantPayload['in_app'] = [
                    'message' => $variant->getInApp()->getMessage(),
                ];
            }

            if ($variant->getSMS()) {
                $variantPayload['sms'] = [
                    'message' => $variant->getSMS()->getMessage(),
                ];
            }

            $variantsData['variants'][] = $variantPayload;
        }

        return $variantsData;
    }

    //kainan (retirar antes do deploy em prod)
    public function testNotification(Request $request, string $webhook_id)
    {
        Validator::make([
            'webhook_id' => $webhook_id], [
            'webhook_id' => 'required|string',
        ])->validate();

        $webhookService = new WebhookService();

//        $payloadWebhook = Cache::remember($webhook_id, 1, function () use ($webhookService, $webhook_id) {
//            return $this->payloadNotification($webhookService->get($webhook_id));
//        });

//        $array = [
//            0,1,2,3,4,5,6,7,8,9,10,
//            11,12,13,14,15,16,17,18,19,
//            20,21,22,23,24,25,26,27,28,29,
//            30,31,32,33,34,35,36,37,38,39,
//            40,41,42,43,44,45,46,47,48,49
//        ];

        $payloadWebhook = $this->createVariantsData($webhookService->get($webhook_id));

        $percentageTotal = 100;
        $notifications = $request->get('data');
        foreach ($payloadWebhook['variants'] as $variant) {
            $ceil = ( ceil(($variant['webhook_percentage'] / $percentageTotal) * count($notifications) ));
            $percentageTotal = $percentageTotal - $variant['webhook_percentage'];
            $dispatchNotificatiton = array_slice($notifications, 0, $ceil);

            //realiza disparo
            foreach($dispatchNotificatiton as $dispatch) {
                //realiza disparo e limpa o array abaixo

                $notifications = array_filter($notifications, function($entry) use ($dispatch) {
                    return $entry != $dispatch;
                });
            }
            echo "webhook_variant_name: " . $variant['webhook_variant_name'] . "<br>";
            echo "webhook_percentage: " . $variant['webhook_percentage'] . "<br>";
            echo "Notificatoes enviadas: " . count($dispatchNotificatiton) . "<br>";
            echo "Notificacoes que sobraram: " . count($notifications) . "<br>";
            echo "<br>";echo "<br>";
        }

        dd([]);

//dd($payloadWebhook);
        $notificationservice = new \Promo\Services\Notification\NotificationService();

        if(!empty($payloadWebhook['in_app']) ) {
            $notificationservice->sendMassAppNotification(
                NotificationTemplate::getAppNotificationTemplate(
                    $payloadWebhook,
                    $request->get('properties'),
                    $request->get('consumer_id'),
                    $request->get('campaign_id')
                )
            );
        }

        if(!empty($payloadWebhook['push']) ) {
            $notificationservice->sendMassPushNotification(
                NotificationTemplate::getPushNotificationTemplate(
                    $payloadWebhook,
                    $request->get('properties'),
                    $request->get('consumer_id'),
                    $request->get('campaign_id')
                )
            );
        }

        if(!empty($payloadWebhook['sms']) && !empty($associations['properties']['$phone'])) {
            $notificationservice->sendSmsNotification(
                NotificationTemplate::getSmsNotificationTemplate(
                    $request->get('properties')['$phone'],
                    $payloadWebhook,
                    $request->get('prefix')
                )
            );
        }

        $webhook = $webhookService->get($webhook_id);

        return (new WebhookResource($webhook));
    }

}
