<?php

namespace Promo\Repositories;




/**
 * BlackListedConsumerRepository class
 */
class BlackListedConsumerRepository extends BaseRepository
{
    /**
     * Update the specified resource in storage.
     *
     * @param  array
     * @param  int  $id
     * @return Model
     */
    public function updateByConsumerId($data, int $id)
    {
        $document = $this->findOneBy(['consumer_id' => $id]);
        if ($document){
            $document->fill($data);
            $this->commit();
        }

        return $document;
    }


    /**
     * @param $campaign_type
     * @param $transaction_types
     * @param array $consumers
     * @return array
     */
    public function getBlacklistedConsumersByCampaignTransactionTypes(string $campaign_type, array $transaction_types, array $consumers =[])
    {


        $this->init();
        $this->builder->select('consumer_id');
        $this->builder->field('active')->equals(true);
        $this->builder->field('deleted_at')->exists(false);

        $this->builder->addAnd(
            $this->builder->expr()->addOr(
                $this->builder->expr()->addAnd(
                    $this->builder->expr()->field('transaction_types')->in($transaction_types),
                    $this->builder->expr()->field('campaign_types')->equals($campaign_type)
                ),

                $this->builder->expr()->addAnd(
                    $this->builder->expr()->field('transaction_types')->in($transaction_types),
                    $this->builder->expr()->addOr(
                        $this->builder->expr()->field('campaign_types')->size(0),
                        $this->builder->expr()->field('campaign_types')->exists(false)
                    )
                ),

                $this->builder->expr()->addAnd(
                    $this->builder->expr()->addOr(
                        $this->builder->expr()->field('transaction_types')->size(0),
                        $this->builder->expr()->field('transaction_types')->exists(false)
                    ),
                    $this->builder->expr()->field('campaign_types')->equals($campaign_type)
                )


            )
        );

        if (count($consumers) > 0) {
            $this->builder->field('consumer_id')->in($consumers);
        }


        return $this->builder->getQuery()->execute();
    }



}