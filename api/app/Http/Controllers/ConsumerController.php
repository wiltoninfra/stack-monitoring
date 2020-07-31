<?php

namespace Promo\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Promo\Exceptions\InvalidCampaignException;
use Promo\Exceptions\InvalidCouponException;
use Promo\Exceptions\PromoException;
use Promo\Http\Resources\RewardResource;
use Promo\Services\BannerService;
use Promo\Services\CouponService;
use Promo\Services\CampaignService;
use Promo\Services\CashbackService;
use Promo\Services\CashfrontService;
use Promo\Services\TransactionService;
use Promo\Http\Resources\CouponResource;
use Promo\Http\Requests\CashbackRequest;
use Illuminate\Support\Facades\Validator;
use Promo\Http\Requests\CashfrontRequest;
use Doctrine\ODM\MongoDB\MongoDBException;
use Promo\Http\Resources\CashfrontResource;
use Promo\Services\ConsumerCampaignService;
use Promo\Http\Resources\TransactionResource;
use Promo\Http\Resources\MobileBannerResource;
use Promo\Http\Requests\CouponApplicationRequest;
use Promo\Http\Requests\ConsumerBatchPaymentRequest;
use Promo\Http\Resources\ConsumerCampaignResource;
use \Illuminate\Validation\ValidationException;

class ConsumerController extends Controller
{
    /**
     * Serviço CashbackService
     *
     * @var \Promo\Services\CashbackService
     */
    protected $cashback_service;

    /**
     * Serviço de Cashfront
     *
     * @var \Promo\Services\CashfrontService
     */
    protected $cashfront_service;

    /**
     * Serviço TransactionService
     *
     * @var \Promo\Services\TransactionService
     */
    protected $transaction_service;

    /**
     * Serviço CouponnService
     *
     * @var \Promo\Services\CouponService
     */
    protected $coupon_service;

    /**
     * Serviço BannerService
     *
     * @var \Promo\Services\BannerService
     */
    protected $banner_service;

    public function __construct(CashbackService $cashback_service,
                                CashfrontService $cashfront_service,
                                TransactionService $transaction_service,
                                CampaignService $campaign_service,
                                ConsumerCampaignService $consumer_campaign_service,
                                CouponService $coupon_service,
                                BannerService $banner_service)
    {
        parent::__construct($campaign_service, $consumer_campaign_service);
        $this->cashback_service = $cashback_service;
        $this->cashfront_service = $cashfront_service;
        $this->transaction_service = $transaction_service;
        $this->coupon_service = $coupon_service;
        $this->banner_service = $banner_service;
    }

    /**
     * Obtém cashback
     *
     * @param \Illuminate\Http\Request $request
     * @param $consumer_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws MongoDBException
     *
     * @SWG\Post(
     *     path="/consumers/cashback",
     *     description="A partir de detalhes de transação e consumer, retorna a melhor campanha e cancela as similares",
     *     produces={"application/json"},
     *     tags={"cashback"},
     *     @SWG\Parameter(
     *         name="payload",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(type="object", ref="#/definitions/TransactionCorePayload"),
     *     ),
     *     @SWG\Response(response=202, description="Accepted"),
     *     @SWG\Response(response=404, description="Consumer not found"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function applyCashback(Request $request)
    {
        try {
            Validator::make($request->all(), CashbackRequest::rules())
                ->validate();

            $transaction = $request->get('transaction');
            $this->transaction_service->checkRetroativeTransaction($transaction);

            Log::info('starting_cashback_transaction', [
                'context' => 'cashback',
                'status' => 'success',
                'transaction' => $transaction
            ]);

            $cashback = $this->cashback_service->cashback($transaction['consumer_id'], $transaction);

            return (new RewardResource($cashback))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);

        } catch (\Exception $e) {

            Log::info('unexpected_error', [
                'context' => 'cashback',
                'status' => 'fail',
                'exception_message' => $e->getMessage(),
                'transaction' => $transaction ?? null
            ]);

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
     * Executa cashfront
     *
     * @param \Illuminate\Http\Request $request
     * @param $consumer_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws MongoDBException
     *
     * @SWG\Patch(
     *     path="/consumers/{consumer_id}/cashfront",
     *     description="A partir de detalhes do depósito e consumer, retorna a melhor campanha e cancela as similares",
     *     produces={"application/json"},
     *     tags={"cashfront"},
     *     @SWG\Parameter(
     *         name="consumer_id",
     *         in="path",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="payload",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(type="object", ref="#/definitions/DepositCorePayload"),
     *     ),
     *     @SWG\Response(response=202, description="Accepted"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function applyCashfront(Request $request, int $consumer_id)
    {
        Validator::make($request->all(), CashfrontRequest::rules())
            ->validate();

        $cashback = $this->cashfront_service->cashfront($consumer_id, $request->deposit);

        return (new CashfrontResource($cashback))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    /**
     * Reverte cashback e efeitos colaterais gerados por transação
     *
     * @param \Illuminate\Http\Request $request
     * @param $consumer_id
     * @param $transaction_type
     * @param $transaction_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws MongoDBException
     *
     * @SWG\Patch(
     *     path="/consumers/{consumer_id}/transactions/{transaction_type}/{transaction_id}/undo",
     *     description="Com informação de transação que gerou cashback, desfaz",
     *     produces={"application/json"},
     *     tags={"cashback"},
     *     @SWG\Parameter(
     *         name="consumer_id",
     *         in="path",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="transaction_type",
     *         in="path",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="transaction_id",
     *         in="path",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(response=200, description="Ok"),
     *     @SWG\Response(response=404, description="Transaction not found"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function undoCashback(Request $request, int $consumer_id, string $transaction_type, int $transaction_id)
    {
        $this->cashback_service->undoCashback($consumer_id, $transaction_type, $transaction_id);

        return response(null, Response::HTTP_ACCEPTED);
    }

    /**
     * Obtém as as associações de um usuário a campanhas
     *
     * @param \Illuminate\Http\Request $request
     * @param $consumer_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws MongoDBException
     *
     * @SWG\Get(
     *     path="/consumers/{consumer_id}/campaigns/associations",
     *     description="Obtém as as associações de um usuário a campanhas",
     *     produces={"application/json"},
     *     tags={"consumer"},
     *     @SWG\Parameter(
     *         name="consumer_id",
     *         in="path",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Filtra por datas de associação a partir da informada, em ISO 8601",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Filtra por datas de associação de fim até a informada, em ISO 8601",
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
     *         type="integer",
     *     ),
     *     @SWG\Response(response=200, description="Ok"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function getAssociations(Request $request, int $consumer_id)
    {
        $criteria = $this->cleanCriteria($request->all());
        $sort = $this->prepareSort($request->get('sort'));
        $limit = $request->get('limit');
        $skip = $request->get('skip');

        $associations = $this->consumer_campaign_service->getConsumerAssociations($consumer_id, $criteria, $sort, $limit, $skip);

        $response = ConsumerCampaignResource::collection($associations)
                ->response()
                ->setStatusCode(Response::HTTP_OK);

        return filter_var($request->get('count'), FILTER_VALIDATE_BOOLEAN)
            ? $response->header('X-Total-Count', $this->consumer_campaign_service->countConsumerAssociations($consumer_id, $criteria))
            : $response;
    }

    /**
     * Aplica cupom a usuário
     *
     * @param \Illuminate\Http\Request $request
     * @param int $consumer_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Promo\Exceptions\InvalidCampaignException
     * @throws \Promo\Exceptions\InvalidCouponException
     * @throws \Promo\Exceptions\ValidationException
     * @SWG\Patch(
     *     path="/consumers/{consumer_id}/coupon",
     *     description="Tenta aplicar cupom, que faz associação a campanha",
     *     produces={"application/json"},
     *     tags={"coupon"},
     *     @SWG\Parameter(
     *         name="consumer_id",
     *         in="path",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="payload",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(type="object", ref="#/definitions/CouponApplicationPayload"),
     *     ),
     *     @SWG\Response(response=202, description="Accepted"),
     *     @SWG\Response(response=404, description="Consumer not found"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function applyCoupon(Request $request, int $consumer_id)
    {
        try {
            Validator::make($request->all(), CouponApplicationRequest::rules())
                ->validate();
            $coupon = $this->coupon_service->apply($request->coupon, $consumer_id);
        } catch (InvalidCouponException $e) {
            \Log::info('Track Cupom ',[$e->getMessage()]);
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        } catch (ValidationException $e) {
            \Log::info('Track Cupom ',[$e->getMessage()]);
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (InvalidCampaignException $e) {
            \Log::info('Track Cupom ',[$e->getMessage()]);
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }

        return (new CouponResource($coupon))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    /**
     * Obtém transação que gerou cashback
     *
     * @param \Illuminate\Http\Request $request
     * @param int $consumer_id
     * @param string $transaction_type
     * @param int $transaction_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @SWG\Get(
     *     path="/consumers/{consumer_id}/transactions/{transaction_type}/{transaction_id}",
     *     description="Com consumer e transaction ids, retorna a transação",
     *     produces={"application/json"},
     *     tags={"cashback"},
     *     @SWG\Parameter(
     *         name="consumer_id",
     *         in="path",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="transaction_type",
     *         in="path",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="transaction_id",
     *         in="path",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(response=200, description="Ok"),
     *     @SWG\Response(response=404, description="Transaction not found"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function getTransaction(Request $request, int $consumer_id, string $transaction_type, int $transaction_id)
    {
        $transaction = $this->transaction_service->getOne($consumer_id, $transaction_type, $transaction_id);
        return (new TransactionResource($transaction))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    /**
     * Obtém as as transações que geraram cashback a um usuário
     *
     * @param \Illuminate\Http\Request $request
     * @param $consumer_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws MongoDBException
     *
     * @SWG\Get(
     *     path="/consumers/{consumer_id}/transactions",
     *     description="Obtém as as transações que geraram cashback a um usuário",
     *     produces={"application/json"},
     *     tags={"cashback"},
     *     @SWG\Parameter(
     *         name="consumer_id",
     *         in="path",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="campaign_id",
     *         in="query",
     *         description="Filtra por id de campanha",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="transaction_id",
     *         in="query",
     *         description="Filtra por id de transação",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="transaction_type",
     *         in="query",
     *         description="Tipo p2p ou pav",
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
    public function getAllTransactions(Request $request, int $consumer_id)
    {
        $criteria = $this->cleanCriteria($request->all());
        $sort = $this->prepareSort($request->get('sort'));
        $limit = $request->get('limit');
        $skip = $request->get('skip');

        $transactions = $this->transaction_service->getAll($consumer_id, $criteria, $sort, $limit, $skip);

        $response = TransactionResource::collection($transactions)
            ->response()
            ->setStatusCode(Response::HTTP_OK);

        return filter_var($request->get('count'), FILTER_VALIDATE_BOOLEAN)
            ? $response->header('X-Total-Count', $this->transaction_service->countAll($consumer_id, $criteria))
            : $response;
    }

    /**
     * Obtém os banners de um consumer, direcionados e globais
     *
     * @param \Illuminate\Http\Request $request
     * @param $consumer_id
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws MongoDBException
     *
     * @SWG\Get(
     *     path="/consumers/{consumer_id}/banners",
     *     description="Obtém os banners direcionados a um consumer",
     *     produces={"application/json"},
     *     tags={"banner"},
     *     @SWG\Parameter(
     *         name="consumer_id",
     *         in="path",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="area_code",
     *         in="query",
     *         description="DDD do usuário",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(response=200, description="Ok"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function getBanners(Request $request, int $consumer_id)
    {
        $banners = $this->banner_service->getConsumerBanners($consumer_id, $request->all());

        $response = MobileBannerResource::collection($banners)
            ->response()
            ->setStatusCode(Response::HTTP_OK);

        return $response;
    }

    /**
     * Processa pagamentos manuais dos consumers, de acordo com uma campanha
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws MongoDBException
     *
     * @SWG\Post(
     *     path="/consumers/batch/payments",
     *     description="Processa pagamentos manuais dos consumers, de acordo com uma campanha",
     *     produces={"application/json"},
     *     tags={"consumer"},
     *     @SWG\Parameter(
     *         name="justification",
     *         in="formData",
     *         type="string",
     *         default="Cashbacks não processados",
     *         required=true,
     *         @SWG\Schema(ref="#/definitions/ConsumerBatchPaymentJustification"),
     *     ),
     *     @SWG\Parameter(
     *         name="file",
     *         in="formData",
     *         required=true,
     *         type="file",
     *         @SWG\Schema(ref="#/definitions/ConsumerBatchPaymentFile"),
     *     ),
     *     @SWG\Response(response=200, description="Ok"),
     *     @SWG\Response(response=422, description="Unprocessable Entity"),
     *     @SWG\Response(response=500, description="Internal server error"),
     * )
     */
    public function processBatchPayments(Request $request)
    {
        try {
            $params = $request->all();
            $file = $request->file('file');

            if ($file) {
                $params['extension'] = $file->getClientOriginalExtension();
            }

            Validator::make($params, ConsumerBatchPaymentRequest::rules())->validate();
            $path = $request->file('file')->getRealPath();
            $data = array_map('str_getcsv', file($path));

            $this->consumer_campaign_service->processBatchPayments(
                $data,
                $request->justification
            );

            $result = [
                "status" => "success",
                "data" => "null"
            ];

            return response($result, Response::HTTP_OK);
        } catch (ValidationException $e) {
            $result = [
                "status" => "fail",
                "message" => $e->errors()
            ];

            return response($result, Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (PromoException $e) {
            $result = [
                "status" => "fail",
                "message" => $e->getMessage()
            ];

            return response($result, Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            $result = [
                "status" => "error",
                "message" => $e->getMessage()
            ];

            return response($result, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
