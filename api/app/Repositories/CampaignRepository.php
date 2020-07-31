<?php

namespace Promo\Repositories;

use Illuminate\Support\Facades\Log;
use Promo\Documents\Enums\CampaignTypeEnum;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Promo\Documents\Enums\TransactionTypeEnum;
use Illuminate\Support\Carbon;

/**
 * CampaignRepository class
 */
class CampaignRepository extends DocumentRepository
{

    Const MINUTES = 2; //@todo pensar em um nome melhor
    /**
     * Retorna uma campanha, por id
     *
     * @param string $id
     * @return \Promo\Documents\Campaign
     */
    public function getOne(string $id)
    {
        return $this->findOneBy(['_id' => $id, 'deleted_at' => null]);
    }

    /**
     * Retorna várias campanhas a partir de ids em string
     *
     * @param null|array $ids
     * @return array
     */
    public function getMany(?array $ids): array
    {
        if ($ids === null || empty($ids)) {
            return [];
        }

        $tags = $this->findBy(['_id' => ['$in' => $ids]]);

        return $tags;
    }


    /**
     * Retorna todas as campanhas globais ou direcionadas, ativas
     * e compatíveis com a transação corrente
     *
     * @param array $associated
     * @param array $disassociated
     * @param array $transaction
     * @return \Doctrine\ODM\MongoDB\Cursor
     */
    public function getAllCompatibleCashbackCampaigns(array $associated, array $disassociated, array $transaction)
    {
        Log::info('starting_no_retroative_cashback', [
            'context' => 'cashback',
            'status' => 'success',
            'transaction' => $transaction,
            'campaigns_associated' => $associated,
            'campaigns_disassociated' => $disassociated
        ]);

        $isTransactionRetroative = $transaction['retroative'];

        $startDateCampaign = $isTransactionRetroative ? Carbon::parse($transaction['transaction_date']) : Carbon::now();

        $qb = $this->createQueryBuilder()
            ->eagerCursor(true);


        // Obtém as campanhas globais ou as direcionadas ao user
        $qb->field('active')->equals(true)
            ->field('type')->equals(CampaignTypeEnum::CASHBACK)
            ->field('deleted_at')->exists(false)
            // campanha nao pode ser comunicação
            ->field('communication')->equals(false)
            // Filtra pelas campanhas com os mesmo tipo de transação
            ->field('transaction.type')->in([$transaction['type'], TransactionTypeEnum::MIXED]);

        // Filtra pela data de inicio

        $qb->addAnd(
            $qb->expr()->addOr(
            // Ou que seja global e não esteja na lista de desativadas
                $qb->expr()->addAnd(
                    $qb->expr()->field('duration.start_date')->exists(true),
                    $qb->expr()->field('duration.start_date')->lte($startDateCampaign)
                ),
                $qb->expr()->field('duration.start_date')->exists(false)
            )
        );

        $qb->addAnd(
            $qb->expr()->addOr(
            // Ou que seja global e não esteja na lista de desativadas
                $qb->expr()->addAnd(
                    $qb->expr()->field('global')->equals(true),
                    $qb->expr()->field('id')->notIn($disassociated)
                ),

                // Ou que seja não global e associada
                $qb->expr()->addAnd(
                    $qb->expr()->field('global')->equals(false),
                    $qb->expr()->field('id')->in($associated)
                )
            )
        );

        // Filtra pelas campanhas que têm o mesmo seller como foco e que têm o mesmo tipo de seller
        if (array_key_exists('seller_id', $transaction)
            && array_key_exists('seller_type', $transaction))
        {
            $seller_id = (int) $transaction['seller_id'];
            $seller_type = $transaction['seller_type'];

            $qb->addAnd(
                $qb->expr()->addOr(
                    $qb->expr()->addAnd(
                        $qb->expr()->field('sellers')->exists(false),
                        $qb->expr()->field('sellers_types')->exists(false)
                    ),
                    $qb->expr()->addOr(
                        $qb->expr()->field('sellers')->equals($seller_id),
                        $qb->expr()->field('sellers_types')->equals($seller_type)
                    )
                )
            );

            // E evita que as campanhas que têm o seller como exceção retornem
            $qb->field('except_sellers')->notEqual($seller_id);
        }


        // Filtra pelas campanhas que têm o mesmo consumer como foco
        if (array_key_exists('consumer_id_payee', $transaction))
        {
            $payee_id = (int) $transaction['consumer_id_payee'];

            $qb->addAnd(
                $qb->expr()->addOr(
                    $qb->expr()->field('consumers')->exists(false)
                    ,
                    $qb->expr()->field('consumers')->equals($payee_id)
                )
            );
        }


        $query = $qb->getQuery();
        // \Log::info('Query executada', ['query_info' => $query->debug()]);
//         dd(json_encode($query->debug()));
//        dd($query->execute());
        return $query->execute();
    }



    /**
     * Retorna todas as campanhas globais ou direcionadas, ativas
     * e compatíveis com a transação corrente
     *
     * @param array $associated
     * @param array $disassociated
     * @param array $transaction
     * @return \Doctrine\ODM\MongoDB\Cursor
     */
    public function getAllCompatibleCashbackCampaignsNew(array $associated, array $disassociated, array $transaction)
    {
        $transaction_date = new Carbon($transaction['transaction_date']);
        $now = Carbon::now();
        $useHistory = ($now->diffInMinutes($transaction_date) < self::MINUTES); // Utilizar data para buscar campanha disponiveis por histórico

        $qb = $this->createQueryBuilder()
            ->eagerCursor(true);

        if(!$useHistory){
            $qb->field('active')->equals(true);
        }

        // Obtém as campanhas globais ou as direcionadas ao user
        $qb
            ->field('type')->equals(CampaignTypeEnum::CASHBACK)
            ->field('deleted_at')->exists(false)
            // Filtra pelas campanhas com os mesmo tipo de transação
            ->field('transaction.type')->in([$transaction['type'], TransactionTypeEnum::MIXED]);

        // Filtra pela data de inicio
        $qb->expr()->addAnd(
            $qb->expr()->field('duration.start_date')->lte($now),
            $qb->expr()->field('duration.start_date')->exists(true)
        );

        $qb->addAnd(
            $qb->expr()->addOr(
            // Ou que seja global e não esteja na lista de desativadas
                $qb->expr()->addAnd(
                    $qb->expr()->field('global')->equals(true),
                    $qb->expr()->field('id')->notIn($disassociated)
                ),

                // Ou que seja não global e associada
                $qb->expr()->addAnd(
                    $qb->expr()->field('global')->equals(false),
                    $qb->expr()->field('id')->in($associated)
                )
            )
        );

        // Filtra pelas campanhas que têm o mesmo seller como foco e que têm o mesmo tipo de seller
        if (array_key_exists('seller_id', $transaction)
            && array_key_exists('seller_type', $transaction))
        {
            $seller_id = (int) $transaction['seller_id'];
            $seller_type = $transaction['seller_type'];

            $qb->addAnd(
                $qb->expr()->addOr(
                    $qb->expr()->addAnd(
                        $qb->expr()->field('sellers')->exists(false),
                        $qb->expr()->field('sellers_types')->exists(false)
                    ),
                    $qb->expr()->addOr(
                        $qb->expr()->field('sellers')->equals($seller_id),
                        $qb->expr()->field('sellers_types')->equals($seller_type)
                    )
                )
            );

            // E evita que as campanhas que têm o seller como exceção retornem
            $qb->field('except_sellers')->notEqual($seller_id);
        }

        // Filtra pelas campanhas que têm o mesmo consumer como foco
        if (array_key_exists('consumer_id_payee', $transaction))
        {
            $payee_id = (int) $transaction['consumer_id_payee'];

            $qb->addAnd(
                $qb->expr()->addOr(
                        $qb->expr()->field('consumers')->exists(false)
                    ,
                        $qb->expr()->field('consumers')->equals($payee_id)
                )
            );
        }

        if($useHistory){
            $getAllCompatibleCampaignsOnDate = $this->getAllCompatibleCampaignsOnDate($transaction['transaction_date']);
            $qb->field('_id')->in($getAllCompatibleCampaignsOnDate);
        }

        $query = $qb->getQuery();
        // \Log::info('Query executada', ['query_info' => $query->debug()]);
        // dd(json_encode($query->debug()));
        return $query->execute();
    }

    private function getAllCompatibleCampaignsOnDate($date){

        $builder = $this->createAggregationBuilder();

        $builder->unwind('$history');
        $builder->match()
            ->field('history.created_at')
            ->lt(new \DateTime($date));

        $builder->sort('history.created_at', -1);

        $builder->group()
            ->field('_id')
            ->expression('$_id')
            ->field('history')
            ->first(
                $builder->expr()
                    ->field('created_at')
                    ->expression('$history.created_at')
            )
            ->field('history')
            ->first(
                $builder->expr()
                    ->field('status')
                    ->expression('$history.status')
            );

        $builder->match()
            ->field('history.status')
            ->equals(true);

        $campaigns_ids = $this->prepareDataCampaignsOnDate($builder->execute()->toArray());

        return $campaigns_ids;
    }

    private function prepareDataCampaignsOnDate($data){
        if (empty($data))
            return [];

        $campaigns_ids  = [];
        foreach ($data as $d){
            $campaigns_ids[] = (string) new \MongoDB\BSON\ObjectId($d['_id']);
        }

        return $campaigns_ids;
    }

    /**
     * Retorna todas as campanhas globais ou direcionadas, ativas
     * e compatíveis com a transação corrente
     *
     * @param array $associated
     * @param array $disassociated
     * @return \Doctrine\ODM\MongoDB\Cursor
     */
    public function getAllCompatibleCashfrontCampaigns(array $associated, array $disassociated)
    {
        $qb = $this->createQueryBuilder()
            ->eagerCursor(true);

        // Obtém as campanhas globais ou as direcionadas ao user
        $qb->field('active')->equals(true)
            ->field('type')->equals(CampaignTypeEnum::CASHFRONT)
            // nao pode ser campanha de comunicação
            ->field('communication')->equals(false)
            ->field('deleted_at')->exists(false);

        $qb->addAnd(
            $qb->expr()->addOr(
            // Ou que seja global e não esteja na lista de desativadas
                $qb->expr()->addAnd(
                    $qb->expr()->field('global')->equals(true),
                    $qb->expr()->field('id')->notIn($disassociated)
                ),

                // Ou que seja não global e associada
                $qb->expr()->addAnd(
                    $qb->expr()->field('global')->equals(false),
                    $qb->expr()->field('id')->in($associated)
                )
            )
        );

        $query = $qb->getQuery();
        // \Log::info('Query executada', ['query_info' => $query->debug()]);
        // dd(json_encode($query->debug()));
        return $query->execute();
    }

    /**
     * Retorna todos os ids de campanhas globais ativas
     *
     * @return mixed
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function getGlobalActiveCampaignsOnly()
    {
        $qb = $this->createQueryBuilder()
            ->select('_id')
            ->field('active')->equals(true)
            ->field('global')->equals(true)
            ->sort('created', 'desc')
            ->limit(100)
            ->hydrate(false);

        $query = $qb->getQuery();

        return $query->execute();
    }

    /**
     * Retorna cursor sem  campanhas
     *
     * @return Cursor
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function getNoCampaigns()
    {
        $qb = $this->createQueryBuilder()
            ->select('_id')
            ->field('_id')->equals('nothing');
        $query = $qb->getQuery();

        return $query->execute();
    }
}
