<?php

namespace Promo\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Promo\Http\Resources\TransactionInfoResource;
use Promo\Services\DepositService;
use Promo\Services\TransactionService;

class TransactionController extends Controller
{
    /**
     * @var TransactionService
     */
    protected $transactionService;

    /**
     * @var DepositService
     */
    protected $depositService;

    /**
     * TransactionController constructor.
     * @param TransactionService $transactionService
     */
    public function __construct(TransactionService $transactionService, DepositService $depositService)
    {
        $this->transactionService = $transactionService;
        $this->depositService = $depositService;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @SWG\Post(
     *     path="/transaction/info",
     *     description="Recebe array de ids de transacoes, retorna informacoes gerais das transacoes",
     *     produces={"application/json"},
     *     tags={"transaction"},
     *     @SWG\Parameter(
     *          name="transactions",
     *          in="body",
     *          required=true,
     *          type="array",
     *          @SWG\Schema(type="object", ref="#/definitions/TransactionInfo"),
     *     ),
     *     @SWG\Response(response=200, description="OK"),
     *     @SWG\Response(response=500, description="Internal server error")
     * )
     */
    public function transactionInfo(Request $request)
    {
        $transactionsInfos = $this->transactionService->getTransactionsInfo($request->get('transactions'));

        //$depositInfos = $this->depositService->getDepositsInfo($request->get('transactions'));

        //$all = $depositInfos->merge($transactionsInfos);

       return TransactionInfoResource::collection($transactionsInfos)->response()->setStatusCode(Response::HTTP_OK);
    }

}
