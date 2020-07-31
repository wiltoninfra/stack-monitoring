<?php

namespace Promo\Services;

use Illuminate\Support\Carbon;
use Promo\Documents\Transaction;
use Doctrine\MongoDB\Query\Builder;
use PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TransactionService
{
    /**
     * Repositório de Transação
     *
     * @var \Doctrine\ODM\MongoDB\DocumentRepository
     */
    private $repository;

    /**
     * AggregationBuilder de Transação
     *
     * @var \Doctrine\ODM\MongoDB\Aggregation\Builder
     */
    private $aggregation_builder;

    /**
     * TransactionService constructor.
     */
    public function __construct()
    {
        $this->repository = DocumentManager::getRepository(Transaction::class);
        $this->aggregation_builder = DocumentManager::createAggregationBuilder(Transaction::class);
    }

    /**
     * Obtém uma transação de consumer
     *
     * @param integer $consumer_id
     * @param string $transaction_type
     * @param integer $transaction_id
     * @return \Promo\Documents\Transaction
     */
    public function getOne(int $consumer_id, string $transaction_type, int $transaction_id)
    {
        $transaction = $this->repository->findOneBy([
            'consumer_id' => $consumer_id,
            'type' => $transaction_type,
            'transaction_id' => $transaction_id
        ]);

        if ($transaction === null)
        {
            \Log::error('Tentativa de obter transação de usuário inexistente no histórico', [
                'consumer_id' => $consumer_id,
                'transaction_id' => $transaction_id
            ]);

            throw new NotFoundHttpException('Transação não encontrada.');
        }

        return $transaction;
    }

    /**
     * Obtém todas as transações que deram cashback
     *
     * @param int $consumer_id
     * @param array $criteria
     * @param array $sort
     * @param int $limit
     * @param int $skip
     * @return mixed
     */
    public function getAll(int $consumer_id, array $criteria = [], array $sort = [], int $limit = 10, int $skip = 0)
    {
        $qb = $this->getAllQuery($consumer_id, $criteria);

        // Aplica as ordenações
        foreach ($sort as $field => $order)
        {
            $qb->sort($field, $order);
        }

        // Paginação
        $qb->limit($limit);
        $qb->skip($skip);

        $result = $qb->getQuery()
            ->execute()
            ->toArray();

        $result = array_values($result);

        return collect($result);
    }

    /**
     * Conta todos os resultados de transações
     *
     * @param int $consumer_id
     * @param array $criteria
     * @return int
     */
    public function countAll(int $consumer_id, array $criteria = []): int
    {
        $total = $this->getAllQuery($consumer_id, $criteria)
            ->count()
            ->getQuery()
            ->execute();

        return $total;
    }

    /**
     * Retorna Query Builder de acordo com critérios
     *
     * @param int $consumer_id
     * @param array $criteria
     * @return Builder
     */
    private function getAllQuery(int $consumer_id, array $criteria): Builder
    {
        $qb = $this->repository->createQueryBuilder()
            ->field('consumer_id')->equals($consumer_id);

        // Filtro por id de transação
        if (array_key_exists('transaction_id', $criteria) === true)
        {
            $qb->field('transaction_id')->equals((int) $criteria['transaction_id']);
        }

        // Filtro pelo tipo da transação
        if (array_key_exists('transaction_type', $criteria) === true)
        {
            $qb->field('type')->equals($criteria['transaction_type']);
        }

        // Filtro por campanha específica
        if (array_key_exists('campaign_id', $criteria) === true)
        {
            $qb->field('campaign')->equals($criteria['campaign_id']);
        }

        return $qb;
    }

    /**
     * Retorna a soma dos valores de cashback e dos valores da transação
     *
     * @param string $campaign_id
     * @return array
     */
    public function getCampaignStats(string $campaign_id): array
    {
        // Filtra pelo ID da campanha
        $builder = $this->aggregation_builder->match()
            ->field('campaign')
            ->equals($campaign_id);

        // Faz a agregação pelos valores desejados
        $builder->group()
            ->field('id')->expression('$campaign')
            ->field('cashback_sum_value')
            ->sum('$cashback_given')
            ->field('transactions_sum_value')
            ->sum('$transaction_value');

        // Executa a query e transforma o resultado em array
        $result = $builder->execute()
            ->toArray();

        // Não existe nenhum registro de transação
        if (count($result) === 0)
        {
            return [
                'cashback_sum_value' => 0,
                'transactions_sum_value' => 0
            ];
        }

        return $result[0];
    }

    /**
     * Adiciona a key ['retroative'] na transacao para reprocessamento
     *
     * @param array $transaction
     * @return void
     */
    public function checkRetroativeTransaction(array &$transaction) : void
    {
        $transaction_date = new Carbon($transaction['transaction_date']);
        $now = Carbon::now();

        ($now->diffInMinutes($transaction_date) > Transaction::TIME_IN_MINUTES_TO_BE_RETROATIVE) ?
            $transaction['retroative'] = true :
            $transaction['retroative'] = false;
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
    public function firstTransactionToPayeeOnCampaign($payeeId, $campaignId) : bool
    {
        return $this->repository->firstTransactionToPayeeOnCampaign($payeeId, $campaignId);
    }

    /**
     * @param $consumerId
     * @return bool
     */
    public function hasTransactions($consumerId): bool
    {
        return $this->repository->hasTransactions($consumerId);
    }

    /**
     * @param $consumerId
     * @param $sellerId
     * @param $transactionDate
     * @return mixed
     */
    public function firstPaymentToSeller($consumerId, $sellerId, $transactionDate)
    {
        return $this->repository->firstPaymentToSeller($consumerId, $sellerId, $transactionDate);
    }

    /**
     * @param $transactions
     * @return mixed
     */
    public function getTransactionsInfo($transactions)
    {
        $integerIDs = array_map('intval', $transactions);
        return collect($this->repository->getTransactionsInfos($integerIDs));
    }
}
