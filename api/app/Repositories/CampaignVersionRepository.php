<?php

namespace Promo\Repositories;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Promo\Documents\Campaign;
use Promo\Documents\CampaignVersion;
use Promo\Documents\Enums\CampaignTypeEnum;
use Promo\Documents\Enums\TransactionTypeEnum;


/**
 * CampaignVersionRepository class
 */
class CampaignVersionRepository extends BaseRepository
{
    /**
     * @param $associated
     * @param $disassociated
     * @param $transaction
     * @return mixed
     * @throws \Exception
     */
    public function getAllCompatibleCashbackRetroativeCampaigns($associated, $disassociated, $transaction)
    {
        $campaignVersionDate = Carbon::parse($transaction['transaction_date']);

        $qb = $this->createQueryBuilder();

        $qb->field('campaign.active')->equals(true)
        ->field('campaign.type')->equals(CampaignTypeEnum::CASHBACK)
        ->field('campaign.communication')->equals(false)
        ->field('campaign.transaction.type')->in([$transaction['type'], TransactionTypeEnum::MIXED]);

        $qb->addAnd(
            $qb->expr()->addAnd(
                $qb->expr()->field('permanenceStartDate')->exists(true),
                $qb->expr()->field('permanenceStartDate')->lte($campaignVersionDate)
            ),
            $qb->expr()->addOr(
                $qb->expr()->addAnd(
                    $qb->expr()->field('permanenceEndDate')->exists(true),
                    $qb->expr()->field('permanenceEndDate')->gte($campaignVersionDate)
                ),
                $qb->expr()->field('permanenceEndDate')->exists(false)
            )
        );

        $qb->addAnd(
            $qb->expr()->addOr(
                $qb->expr()->addAnd(
                    $qb->expr()->field('campaign.global')->equals(true)
                ),
                $qb->expr()->addAnd(
                    $qb->expr()->field('campaign.global')->equals(false),
                    $qb->expr()->field('campaign.id')->in($associated)
                )
            )
        );

        if (array_key_exists('seller_id', $transaction)
            && array_key_exists('seller_type', $transaction))
        {
            $seller_id = (int) $transaction['seller_id'];
            $seller_type = $transaction['seller_type'];
            $qb->addAnd(
                $qb->expr()->addOr(
                    $qb->expr()->addAnd(
                        $qb->expr()->field('campaign.sellers')->exists(false),
                        $qb->expr()->field('campaign.sellers_types')->exists(false)
                    ),
                    $qb->expr()->addOr(
                        $qb->expr()->field('campaign.sellers')->equals($seller_id),
                        $qb->expr()->field('campaign.sellers_types')->equals($seller_type)
                    )
                )
            );

            // E evita que as campanhas que têm o seller como exceção retornem
            $qb->field('campaign.except_sellers')->notEqual($seller_id);
        }

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

        return $qb->getQuery()->execute();
    }

}
