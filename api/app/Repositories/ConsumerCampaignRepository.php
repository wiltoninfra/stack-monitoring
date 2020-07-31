<?php

namespace Promo\Repositories;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\UnitOfWork;
use MongoDB\BSON\ObjectId;
use Promo\Documents\Campaign;
use Promo\Documents\ConsumerCampaign;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * ConsumerCampaignRepository class
 *
 */
class ConsumerCampaignRepository extends DocumentRepository
{
    /**
     * Obtém uma relação, se existir
     *
     * @param int $consumer_id
     * @param Campaign $campaign
     * @return null|ConsumerCampaign
     */
    public function getOne(int $consumer_id, Campaign $campaign): ?ConsumerCampaign
    {
        $cc = $this->findOneBy([
            'consumer_id' => $consumer_id,
            'campaign' => new ObjectId($campaign->getId())
        ]);

        return $cc;
    }

    /**
     * Obtém todas as campanhas ativas de um usuário
     *
     * @param integer $consumer_id
     * @param array $transaction
     * @return array
     *
     * @throws MongoDBException
     */
    public function getConsumerActiveAssociatedCampaigns(int $consumer_id, array $transaction = [])
    {
        $transactionRetroative = false;

        if (isset($transaction['retroative']) && $transaction['retroative'] != false) {
            $transactionRetroative = true;
        }

        $qb = $this->createQueryBuilder();

        $qb->select('campaign')
            ->field('consumer_id')->equals($consumer_id)
            ->field('active')->equals(true);

            // transacao nao retroativa deve considerar filtro abaixo
            if (!$transactionRetroative) {
                $qb->addAnd(
                        $qb->expr()->addOr(
                            $qb->expr()->field('campaign_active')->equals(true),
                            $qb->expr()->field('campaign_active')->exists(false)
                        )
                    );
            }

            $qb->sort('created_at', 'desc')
            ->hydrate(false)
            ->limit(200);

        $query = $qb->getQuery();

        // dd($query->debug());

        return $query->execute();
    }

    /**
     * Obtém todas as campanhas globais desativadas de um usuário
     *
     * @param integer $consumer_id
     * @return array
     *
     * @throws MongoDBException
     */
    public function getConsumerDisabledCampaigns(int $consumer_id)
    {
        $qb = $this->createQueryBuilder()
            ->select('campaign')
            ->field('consumer_id')->equals($consumer_id)
            ->field('active')->equals(false)
            // Desativadas e globais, já que as associadas ativas
            // já excluem as associadas inativas
            ->field('global')->equals(true)
            ->sort('created_at', 'desc')
            ->limit(500)
            ->hydrate(false);

        $query = $qb->getQuery();

        return $query->execute();
    }

    /**
     * Obtém todas as relações canceladas por uma transação
     * que gerou cashback
     *
     * @param int $consumer_id
     * @param string $transaction_type
     * @param int $transaction_id
     * @return mixed
     * @throws MongoDBException
     */
    public function getCancelledByTransaction(int $consumer_id, string $transaction_type, int $transaction_id)
    {
        $qb = $this->createQueryBuilder()
            ->eagerCursor(true)
            ->field('consumer_id')->equals($consumer_id)
            ->field('active')->equals(false)
            ->field('cancelled_by_transaction_type')->equals($transaction_type)
            ->field('cancelled_by_transaction_id')->equals($transaction_id);

        $query = $qb->getQuery();

        return $query->execute();
    }

    /**
     * @param $consumers
     * @param $campaign_id
     * @return mixed
     * @throws MongoDBException
     */
    public function getConsumersAssociatedByCampaign($consumers, $campaign_id)
    {
        $qb = $this->createQueryBuilder()
            ->field('consumer_id')->in($consumers)
            ->field('campaign')->equals(new ObjectId($campaign_id));

        $query = $qb->getQuery();

        return $query->execute()->toArray();

    }
}
