<?php

namespace Promo\Services;

use Promo\Documents\Campaign;
use Doctrine\ODM\MongoDB\Cursor;
use Promo\Documents\ConsumerCampaign;
use Doctrine\ODM\MongoDB\MongoDBException;
use PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager;
use Promo\Documents\Enums\CampaignDiscardFilterEnum;
use Promo\Documents\Enums\CampaignSkipFilterEnum;

/**
 * Classe de serviço responsável por executar ações
 * relacionadas ao cashback
 */
class CashfrontService
{
    /**
     * Serviço de recompensas
     *
     * @var \Promo\Services\RewardService
     */
    private $reward_service;

    public function __construct(RewardService $reward_service)
    {
        $this->reward_service = $reward_service;
    }

    /**
     * @param int $consumer_id
     * @param array $deposit
     * @return mixed
     * @throws MongoDBException
     */
    public function cashfront(int $consumer_id, array $deposit)
    {
        \Log::info('Requisição de cashfront recebida', ['deposit_info' => $deposit]);

        $campaigns = $this->getDepositCampaigns($consumer_id);
        $cashfront = $this->fittestCampaign($campaigns, $consumer_id, $deposit);

        DocumentManager::flush();

        return $cashfront;
    }

    /**
     * @param int $consumer_id
     * @return Cursor
     * @throws MongoDBException
     */
    public function getDepositCampaigns(int $consumer_id)
    {
        // Array de IDs das campanhas associadas e ativas
        $associated = $this->reward_service->getConsumerActiveAssociatedCampaigns($consumer_id);

        // Array de IDs das campanhas não mais ativas ao usuário
        $disassociated = $this->reward_service->getConsumerDisabledCampaigns($consumer_id);

        // Obtém todas as campanhas (globais e direcionadas), filtradas por aspectos da transação
        $campaigns = $this->reward_service
            ->campaign_repository->getAllCompatibleCashfrontCampaigns($associated, $disassociated);

        return $campaigns;
    }

    /**
     * @param Cursor $campaigns
     * @param int $consumer_id
     * @param array $deposit
     * @return array
     * @throws MongoDBException
     */
    public function fittestCampaign(Cursor $campaigns, int $consumer_id, array $deposit)
    {
        // Objeto a ser retornado
        $fittest = [
            'campaign' => null,
            'consumer_campaign' => null,
            'cashfront' => 0
        ];

        // Campanhas que potencialmente podem ser descartadas
        $possibily_discarded_campaigns = [];

        // Campanhas que serão descartadas pois atingiram limites
        $discarded_campaigns = [];

        foreach ($campaigns as $campaign)
        {
            // Relação de usuário e campanha
            $consumer_campaign = $this->reward_service->getConsumerCampaign($consumer_id, $campaign);

            // Critérios que pulam a campanha, mas não descartam/desativam
            if ($this->matchSkipCriteria($campaign, $consumer_campaign, $consumer_id, $deposit))
            {
                continue;
            }

            // Critérios finos que potencialmente desativarão a campanha para o usuário
            if ($this->matchDiscardCriteria($campaign, $consumer_campaign, $deposit))
            {
                $discarded_campaigns[] = $consumer_campaign;
                continue;
            }

            $cashfront_value = $this->reward_service->calculateRewardValue($campaign, $deposit);

            // E substitui por melhores nas iterações subsequentes
            if ($cashfront_value > $fittest['cashfront'])
            {
                // Desativa a melhor campanha escolhida anteriormente (se houve uma anterior)
                if ($fittest['consumer_campaign'] !== null)
                {
                    $possibily_discarded_campaigns[] = $fittest['consumer_campaign'];
                }

                // Substitui pela nova melhor
                $fittest['campaign'] = $campaign;
                $fittest['consumer_campaign'] = $consumer_campaign;
                $fittest['cashfront'] = $cashfront_value;
            }
            else
            {
                // Se não substitui, apenas enfileira para desativar
                $possibily_discarded_campaigns[] = $consumer_campaign;
            }
        }

        $this->reward_service
            ->proccessFittestCampaign($fittest, $deposit, $possibily_discarded_campaigns, $discarded_campaigns, $consumer_id);

        return $fittest;
    }

    /**
     * Função para descartar campanhas que não atendem a diversos critérios
     *
     * @param Campaign $campaign
     * @param ConsumerCampaign $consumer_campaign
     * @param array $deposit
     * @return boolean
     */
    private function matchDiscardCriteria(Campaign $campaign, ConsumerCampaign $consumer_campaign, array $deposit): bool
    {
        $deposit_details = $campaign->getDepositDetails();

        // Se a campanha não está ativa (expirada ou desativada manualmente)
        if ($campaign->isActive() === false)
        {
            $consumer_campaign->setLogDiscardFilter(CampaignDiscardFilterEnum::INACTIVE);

            return true;
        }

        // Descarta campanha em caso de exigência primeiro depósito do usuário
        if ($deposit_details->isFirstDepositOnly() === true && ($deposit['first_deposit'] ?? false) === false)
        {
            $consumer_campaign->setLogDiscardFilter(CampaignDiscardFilterEnum::NEED_FIRST_DEPOSIT);

            return true;
        }

        // Se está expirada para o consumer, a partir da data de associação/push
        if ($campaign->isExpiredForConsumer($consumer_campaign->getCreatedAt()))
        {
            $consumer_campaign->setLogDiscardFilter(CampaignDiscardFilterEnum::EXPIRED);

            return true;
        }

        // Se está dentro do número máximo de transações
        if ($deposit_details->getMaxDepositsPerConsumer() !== null && $consumer_campaign->getCompletedTransactions() >= $deposit_details->getMaxDepositsPerConsumer())
        {
            $consumer_campaign->setLogDiscardFilter( CampaignDiscardFilterEnum::MAX_TRANSACTIONS);

            return true;
        }

        return false;
    }

    /**
     * Função para pular campanhas que não atendem a diversos critérios
     *
     * @param Campaign $campaign
     * @param int $consumer_id
     * @param array $data
     * @return boolean
     * @throws MongoDBException
     */
    private function matchSkipCriteria(Campaign $campaign, ConsumerCampaign $consumer_campaign, int $consumer_id, array $data): bool
    {
        $deposit_details = $campaign->getDepositDetails();

        // Se está dentro do número máximo de transações
        if ($deposit_details->getMinDepositValue() !== null && $deposit_details->getMinDepositValue() > $data['total'])
        {
            $consumer_campaign->setLogSkipFilter(CampaignSkipFilterEnum::MAX_TRANSACTIONS);

            return true;
        }

        // Verifica se consumer já passou do limite de transações para um dia, se existe
        if ($deposit_details->getMaxDepositsPerConsumerPerDay() !== null
            && $this->reward_service->countUsesOfToday($consumer_id, $campaign) >= $deposit_details->getMaxDepositsPerConsumerPerDay())
        {
            $consumer_campaign->setLogSkipFilter(CampaignSkipFilterEnum::MAX_TRANSACTIONS_PER_DAY);

            return true;
        }

        // Verifica se o recharge_method do deposito é igual ao da campanha
        if ($campaign->getCashfrontDetails()->getRechargeMethod() != $data['recharge_method'] ) {

            $consumer_campaign->setLogSkipFilter(CampaignSkipFilterEnum::INVALID_RECHARGE_METHOD);

            return true;
        }

        return false;
    }
}