<?php

namespace Promo\Repositories;

use Carbon\Carbon;
use PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager;
use Promo\Documents\Campaign;
use Promo\Documents\Enums\TransactionTypeEnum;
use Promo\Documents\Transaction;
use Promo\Documents\ConsumerCampaign;
use Doctrine\ODM\MongoDB\DocumentRepository;

class TransactionRepository extends DocumentRepository
{
    const DAY = 'day';
    const WEEK = 'week';
    const MONTH = 'month';

    const ACTION_SUM = 'sum';
    const ACTION_COUNT = 'count';

    /**
     * Tenta obter uma transação
     *
     * @param int $consumer_id
     * @param string $transaction_type
     * @param int $transaction_id
     * @return \Promo\Documents\Transaction|null
     */
    public function getTransaction(int $consumer_id, string $transaction_type, int $transaction_id): ?Transaction
    {
        $transaction = $this->findOneBy([
            'consumer_id' => $consumer_id,
            'type' => $transaction_type,
            'transaction_id' => $transaction_id
        ]);

        return $transaction;
    }

    /**
     * Função que informa se existe transacao com cashback     * O período de filtro é opcional
     *
     * @param int $consumerId
     * @return bool
     */

    public function hasTransactions(int $consumerId): bool
    {
        $transaction = $this->findOneBy([
            'consumer_id' => $consumerId,
        ]);

        if ($transaction instanceof Transaction){
            return true;
        }

        return false;
    }

    /**
     * Função que soma ou conta transações, dependendo do período passado e ação
     * O período de filtro é opcional
     *
     * @param ConsumerCampaign $consumer_campaign
     * @param string $action
     * @param string|null $period
     * @return int|float
     */
    public function processTransactionsOfPeriod(ConsumerCampaign $consumer_campaign, string $action, string $period = null)
    {
        $consumer_id = $consumer_campaign->getConsumerId();
        $campaign = $consumer_campaign->getCampaign();

        // Obtém todas as transações desde o início até o fim do período (dia, semana ou mês)
        $builder = $this->createAggregationBuilder();
        $builder->match()
            ->field('campaign')
            ->references($campaign)
            ->field('consumer_id')
            ->equals($consumer_id);

        // Em relações reiniciadas, considera só as transações a partir da data de reinicialização
        if ($consumer_campaign->isRestarted() === true)
        {
            $builder->match()
                ->field('created_at')
                ->gte($consumer_campaign->getCreatedAt());
        }

        // Seleciona intervalo de tempo de acordo com o período
        switch ($period)
        {
            case self::DAY:
                $start = Carbon::now()->startOfDay();
                $end = Carbon::now()->endOfDay();

                $builder->match()
                    ->field('created_at')
                    ->gte($start)
                    ->lte($end);
                break;

            case self::WEEK:
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();

                $builder->match()
                    ->field('created_at')
                    ->gte($start)
                    ->lte($end);
                break;

            case self::MONTH:
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();

                $builder->match()
                    ->field('created_at')
                    ->gte($start)
                    ->lte($end);
                break;
        }

        // Executa operação de acordo com a ação passada
        switch ($action)
        {
            case self::ACTION_COUNT:
                $builder->count('total_of_items');

                $result = $builder->execute()->toArray();

                return $result[0]['total_of_items'] ?? 0;

            case self::ACTION_SUM:
                $builder->group()
                    ->field('id')
                    ->expression(null)
                    ->field('total')
                    ->sum('$transaction_value');

                $result = $builder->execute()->toArray();

                return $result[0]['total'] ?? 0;

            default:
                return 0;
                break;
        }
    }

    /**
     * Verifica se houve uma transacao first_payee realizada
     * Transacoes retroativas podem recebem "first_payee_received_payment": true
     * e o payeeId ja pode ter recebido em outra transação.
     *
     * @param int $payeeId
     * @param string $campaignId
     * @return bool
     */
    public function firstTransactionToPayeeOnCampaign(int $payeeId, string $campaignId) : bool
    {
        $qb = $this->createQueryBuilder();

        $qb->field('campaign')->equals($campaignId)
            ->addAnd(
                $qb->expr()->field('details.consumer_id_payee')->equals($payeeId)
            )->addAnd(
                $qb->expr()->field('details.first_payee_received_payment')->equals(true)
            )->addAnd(
                $qb->expr()->field('type')->equals('p2p')
            );

        return (bool) $qb->count()->getQuery()->execute();
    }

    /**
     * @param $consumerId
     * @param $sellerId
     * @param $transactionDate
     * @return bool
     * @throws \Exception
     */
    public function firstPaymentToSeller($consumerId, $sellerId, $transactionDate)
    {
        $qb = $this->createQueryBuilder();

        $qb->field('consumer_id')->equals($consumerId)
            ->addAnd(
                $qb->expr()->field('details.seller_id')->equals($sellerId)
            )->addAnd(
                $qb->expr()->field('type')->equals(TransactionTypeEnum::PAV)
            )->addAnd(
                $qb->expr()->field('created_at')->gte(new \DateTime($transactionDate))
            );

        return $qb->count()->getQuery()->execute();
    }

    /**
     * @param $transactions
     * @return mixed
     * @throws \Exception
     */
    public function getTransactionsInfos($transactions)
    {


        $builder = $this->createAggregationBuilder();

        $builder->match()->field('transaction_id')->in($transactions);

        $transactionsRaw = $builder->execute()->toArray();
        $campaigns=[];
        foreach($transactionsRaw as $transaction){
            $campaigns[] = (string) $transaction["campaign"];
        }

        $campaignsFiltered = DocumentManager::createQueryBuilder(Campaign::class)
            ->field('_id')->in($campaigns)
                ->getQuery()
                ->execute();

        $campaignIds = array_keys((collect($campaignsFiltered)->toArray()));

        $qb = $this->createQueryBuilder();

        $qb->select(['transaction_id', 'type', 'details.transaction_date', 'cashback_given', 'campaign',"consumer_id","transaction_value"]);

        $qb->field('transaction_id')->in($transactions)
            ->addAnd(
                $qb->expr()->field('campaign')->in($campaignIds)
            );

        return $qb->getQuery()->execute();
    }

}
