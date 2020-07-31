<?php

namespace Promo\Repositories;

use Carbon\Carbon;
use Promo\Documents\Campaign;
use Doctrine\ODM\MongoDB\DocumentRepository;

class DepositRepository extends DocumentRepository
{
    /**
     * Obtém total de depositos que um consumer fez para
     * uma campanha, em um dia
     *
     * @param int $consumer_id
     * @param Campaign $campaign
     * @return int
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function countDepositsOfToday(int $consumer_id, Campaign $campaign): int
    {
        $start_of_today = Carbon::now()->startOfDay();
        $end_of_today = Carbon::now()->endOfDay();

        $qb = $this->createQueryBuilder();

        // Obtém todas as transações desde o início do dia, atém o fim do dia
        $qb->field('campaign')->references($campaign)
            ->field('consumer_id')->equals($consumer_id)
            ->field('created_at')
                ->gte($start_of_today)
                ->lte($end_of_today);

        $query = $qb->count()->getQuery();

        return $query->execute();
    }


    /**
     * @param $transactions
     * @return mixed
     * @throws \Exception
     */
    public function getDepositsInfos($ids)
    {

        $qb = $this->createQueryBuilder();

        $qb->select(['consumer_id', 'details.id', 'created_at', 'cashfront_given','campaign','details.total']);

        $qb->field('details.id')->in($ids);

        return $qb->getQuery()->execute();
    }
}