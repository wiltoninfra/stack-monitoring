<?php

namespace Promo\Services;

use Illuminate\Support\Carbon;
use Promo\Documents\Deposit;
use Doctrine\MongoDB\Query\Builder;
use PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DepositService
{
    /**
     * Repositório de Deposito
     *
     * @var \Doctrine\ODM\MongoDB\DocumentRepository
     */
    private $repository;

    /**
     * AggregationBuilder de Deposito
     *
     * @var \Doctrine\ODM\MongoDB\Aggregation\Builder
     */
    private $aggregation_builder;

    /**
     * DepositService constructor.
     */
    public function __construct()
    {
        $this->repository = DocumentManager::getRepository(Deposit::class);
        $this->aggregation_builder = DocumentManager::createAggregationBuilder(Deposit::class);
    }


    /**
     * @param $deposits
     * @return mixed
     */
    public function getDepositsInfo($deposits)
    {
        // garante que busca INT e STRING, pois infelizmente varia no mongo.
        $ids = array_merge(array_map('intval', $deposits),array_map('strval', $deposits));

        return collect($this->repository->getDepositsInfos($ids));
    }
}
