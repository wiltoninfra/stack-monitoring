<?php

namespace Promo\Services;

use Illuminate\Support\Facades\Log;
use Promo\Documents\BlackListedConsumer;
use Promo\Documents\Campaign;
use Doctrine\ODM\MongoDB\Cursor;
use Promo\Documents\ConsumerCampaign;
use Promo\Documents\Enums\CampaignDiscardFilterEnum;
use Promo\Documents\Enums\CampaignSkipFilterEnum;
use Doctrine\ODM\MongoDB\MongoDBException;
use Promo\Documents\Enums\PaymentMethodsEnum;
use Promo\Documents\Enums\TransactionConditionEnum;
use Promo\Documents\Enums\TransactionTypeEnum;
use PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Classe de serviço responsável por executar ações
 * relacionadas ao cashback
 */
class CashbackService
{
    /**
     * Serviço de recompensas
     *
     * @var \Promo\Services\RewardService
     */
    private $reward_service;

    /**
     * Serviço de transação
     *
     * @var \Promo\Services\TransactionService
     */
    private $transactionService;

    /**
     * Serviço de recompensas
     *
     * @var \Promo\Services\RewardService
     */
    private $blacklisted_consumer_repository;

    protected $blackListedConsumerService;

    private const INTERNAL_SELLERS = [63519, 63526, 69193, 71277, 72012, 72011];

    private const INTERNAL_CONSUMERS = [1199222,13441244, 15021645];

    public function __construct(RewardService $reward_service, TransactionService $transactionService, BlackListedConsumerService $blackListedConsumerService)
    {
        $this->reward_service = $reward_service;
        $this->blacklisted_consumer_repository = DocumentManager::getRepository(BlackListedConsumer::class);
        $this->blackListedConsumerService = $blackListedConsumerService;
        $this->transactionService = $transactionService;
    }

    /**
     * Executa a seleção de campanhas e retorno do melhor cashback, junto a campanha
     *
     * @param integer $consumer_id
     * @param array $transaction
     * @return array
     *
     * @throws MongoDBException
     */
    public function cashback(int $consumerId, array $transaction)
    {

        $campaigns = $this->getTransactionCampaigns($consumerId, $transaction);

        $campaignsAllowed = $this->blackListedConsumerService->filterCampaignsAllowed($campaigns, $consumerId);

        $cashback = $this->fittestCampaign($campaignsAllowed, $consumerId, $transaction);

        DocumentManager::flush();

        return $cashback;
    }

    /**
     * Desfaz alterações causadas por cashback de uma transação
     *
     * @param int $consumerId
     * @param string $transactionType
     * @param int $transactionId
     * @throws MongoDBException
     */
    public function undoCashback(int $consumerId, string $transactionType, int $transactionId)
    {
        $this->revertChanges($consumerId, $transactionType, $transactionId);

        \Log::info('Cashback desfeito para a transação', [
            'transaction_id' => $transactionId,
            'transaction_type' => $transactionType,
            'consumer_id' => $consumerId
        ]);
    }

    /**
     * Retorna todas as campanhas ativas, incluindo as
     * globais e as direcionadas ao usuário que são compatíveis
     * com as características da transação
     *
     * @param int $consumer_id
     * @param array $transaction
     * @return Cursor
     *
     * @throws MongoDBException
     */
    public function getTransactionCampaigns(int $consumer_id, array $transaction)
    {
        Log::info('blacklist_checking', [
            'context' => 'cashback',
            'status' => 'success',
            'transaction' => $transaction
        ]);

        // Se o usuario estiver na Blacklist nao procurar campanhas validas.
        if (count($this->blacklisted_consumer_repository->getBlacklistedConsumersByCampaignTransactionTypes('',[$transaction['type']],[$consumer_id])) == 0) {
            // Array de IDs das campanhas associadas e ativas
            $associated = $this->reward_service->getConsumerActiveAssociatedCampaigns($consumer_id, $transaction);


            // Array de IDs das campanhas não mais ativas ao usuário
            $disassociated = $this->reward_service->getConsumerDisabledCampaigns($consumer_id);


            // Obtém todas as campanhas (globais e direcionadas), filtradas por aspectos da transação
            $campaigns = $this
                ->reward_service
                ->getCompatibleCashbackCampaigns($associated, $disassociated, $transaction);

            Log::info('user_no_blacklisted', [
                'context' => 'cashback',
                'status' => 'success',
                'transaction' => $transaction,
                'campaigns_associated' => $associated,
                'campaigns_disassociated' => $disassociated
            ]);

        } else {
            Log::info('user_blacklisted', [
                'context' => 'cashback',
                'status' => 'success',
                'transaction' => $transaction
            ]);
            $campaigns = $this->reward_service
                ->campaign_repository->getNoCampaigns();
        }

        return $campaigns;
    }

    /**
     * Retorna a melhor campanha e o valor do cashback
     *
     * @param array $campaigns
     * @param int $consumerId
     * @param array $transaction
     * @return array
     */
    private function fittestCampaign(array $campaigns, int $consumerId, array $transaction): array
    {
        Log::info('starting_fittest_campaign', [
            'context' => 'cashback',
            'status' => 'success',
            'consumer_id' => $consumerId,
            'transaction' => $transaction,
        ]);

        // Objeto a ser retornado
        $fittest = [
            'campaign' => null,
            'consumer_campaign' => null,
            'cashback' => 0
        ];

        if (array_key_exists('seller_id', $transaction)) {
            if (in_array($transaction['seller_id'], self::INTERNAL_SELLERS)) {
                Log::info("Seller interno PicPay");
                return $fittest;
            }
        }

        if (array_key_exists('consumer_id_payee', $transaction)) {
            if (in_array($transaction['consumer_id_payee'], self::INTERNAL_CONSUMERS)) {
                Log::info("Consumer interno PicPay ");
                return $fittest;
            }
        }

        if (!isset($transaction['first_payment'])) {
            $transaction['first_payment'] = false;
        }

        // Campanhas que potencialmente podem ser descartadas
        $possibilyDiscardedCampaigns = [];

        // Campanhas que serão descartadas pois atingiram limites
        $discardedCampaigns = [];

        foreach ($campaigns as $campaign)
        {
            Log::info('starting_skip_criteria_foreach_campaign', [
                'context' => 'cashback',
                'status' => 'success',
                'transaction' => $transaction,
                'consumer_id' => $consumerId,
                'campaign' => $campaign->getArray()

            ]);

            // Relação de usuário e campanha
            $consumerCampaign = $this->reward_service->getConsumerCampaign($consumerId, $campaign);

            //////////////////////////////////////////////////////////////////////////////////

            // Critérios que pulam a campanha, mas não descartam/desativam

            if ($this->matchSkipCriteria($campaign, $consumerCampaign, $transaction))
            {
                continue;
            }

            // Critérios finos que potencialmente desativarão a campanha para o usuário
            if ($this->matchDiscardCriteria($campaign, $consumerCampaign, $transaction))
            {
                $discardedCampaigns[] = $consumerCampaign;
                continue;
            }

            $cashback_value = $this->reward_service->calculateRewardValue($campaign, $transaction);

            // E substitui por melhores nas iterações subsequentes
            if ($cashback_value > $fittest['cashback'])
            {
                // Desativa a melhor campanha escolhida anteriormente (se houve uma anterior)
                if ($fittest['consumer_campaign'] !== null)
                {
                    $possibilyDiscardedCampaigns[] = $fittest['consumer_campaign'];
                }

                // Substitui pela nova melhor
                $fittest['campaign'] = $campaign;
                $fittest['consumer_campaign'] = $consumerCampaign;
                $fittest['cashback'] = $cashback_value;
            }
            else
            {
                // Se não substitui, apenas enfileira para desativar
                $possibilyDiscardedCampaigns[] = $consumerCampaign;
            }
        }

        Log::info('finishing_fittest_campaign', [
            'context' => 'cashback',
            'status' => 'success',
            'transaction' => $transaction,
            'campaign' => isset($fittest['campaign']) ? $fittest['campaign']->getArray() : 'null',
            'cashback' => $fittest['cashback'] ?? 0
        ]);

        // Se há melhor escolhida, incrementa o contador de transações completas
        // e desativa as descartadas
        if (!$this->reward_service
            ->proccessFittestCampaign($fittest, $transaction, $discardedCampaigns, $discardedCampaigns, $consumerId)){

            $fittest = [
                'campaign' => null,
                'consumer_campaign' => null,
                'cashback' => 0
            ];
        }

        return $fittest;
    }

    /**
     * Função para descartar campanhas que não atendem a diversos critérios
     *
     * @param Campaign $campaign
     * @param ConsumerCampaign $consumer_campaign
     * @param array $transaction
     * @return boolean
     *
     */
    private function matchDiscardCriteria(Campaign $campaign, ConsumerCampaign $consumer_campaign, array $transaction): bool
    {
        Log::info('starting_match_discard', [
            'context' => 'cashback',
            'status' => 'success',
            'transaction' => $transaction,
            'campaign' => $campaign->getArray()

        ]);
        $transaction_details = $campaign->getTransactionDetails();

        // Se a campanha não está ativa (expirada ou desativada manualmente)
        if (($campaign->isActive()) === false && ($transaction['retroative'] == false))
        {
            $consumer_campaign->setLogDiscardFilter(CampaignDiscardFilterEnum::INACTIVE);

            return true;
        }

        // Verifica limites de campanha (gerais)
        if ( $this->reward_service->checkCampaignDiscardLimits($campaign, $consumer_campaign))
        {
            $consumer_campaign->setLogDiscardFilter(CampaignDiscardFilterEnum::LIMITS);

            return true;
        }

        // Se está expirada para o consumer, a partir da data de associação/push
        if ( $campaign->isExpiredForConsumer($consumer_campaign->getCreatedAt(), $transaction['transaction_date']) )
        {
            $consumer_campaign->setLogDiscardFilter(CampaignDiscardFilterEnum::EXPIRED_FOR_CONSUMER);

            return true;
        }

        // Se usuario nao fez transacao
        if ( ($campaign->getTransactionDetails()->isFirstPayment() === true)
            && ($transaction['first_payment'] == false) )
        {
            $consumer_campaign->setLogDiscardFilter(CampaignDiscardFilterEnum::FIRST_PAYMENT);

            return true;
        }

        // Se usuario nao fez transacao considerando retroativos
        if ($transaction['retroative'] === true && ($campaign->getTransactionDetails()->isFirstPayment() === true)
            && ($transaction['first_payment'] == true)) {
                $existsTransaction = $this->transactionService->hasTransactions($transaction['consumer_id']);
                if ($existsTransaction) {
                    $consumer_campaign->setLogDiscardFilter(CampaignDiscardFilterEnum::FIRST_PAYMENT_RETROATIVE);

                    return true;
                }
        }

        // Se a campanha pede que o cashback seja válido apenas para o primeiro pagamento ao seller
        if ( $transaction_details->isFirstPaymentToSeller() === true
            && ($transaction['first_payment_to_seller'] ?? false) == false )
        {
            $consumer_campaign->setLogDiscardFilter(CampaignDiscardFilterEnum::FIRST_PAYMENT_TO_SELLER);

            return true;
        }

        if ($transaction['retroative'] && $transaction_details->isFirstPaymentToSeller() === true
            && ($transaction['first_payment_to_seller'] ?? false) == true) {

            $firstPaymentToSeller = $this->transactionService
                ->firstPaymentToSeller($transaction['consumer_id'], $transaction['seller_id'], $transaction['transaction_date']);
            if ($firstPaymentToSeller) {
                $consumer_campaign->setLogDiscardFilter(CampaignDiscardFilterEnum::FIRST_PAYMENT_TO_SELLER_RETROATIVE);

                return true;
            }
        }

        // Se possui mensagem exigida na transação
        if ( $transaction_details->getRequiredMessage() !== null
            && ! stripos($transaction['message'] ?? '', $transaction_details->getRequiredMessage()) )
        {
            $consumer_campaign->setLogDiscardFilter(CampaignDiscardFilterEnum::REQUIRED_MESSAGE);

            return true;
        }

        return false;
    }

    /**
     * Função para pular (e não desativar) campanhas que não atendem a alguns critérios
     *
     * @param Campaign $campaign
     * @param ConsumerCampaign $consumer_campaign
     * @param array $transaction
     * @return bool
     */
    private function matchSkipCriteria(Campaign $campaign, ConsumerCampaign $consumer_campaign, array $transaction): bool
    {
        Log::info('starting_match_skip', [
            'context' => 'cashback',
            'status' => 'success',
            'transaction' => $transaction,
            'campaign' => $campaign->getArray()
        ]);

        $transaction_details = $campaign->getTransactionDetails();

        // Se a campanha é para pagamento somente com cartão
        if (( $transaction_details->getPaymentMethods() == PaymentMethodsEnum::CREDIT_CARD_ONLY )
            && ( $transaction['credit_card'] == 0 ))
        {
            $consumer_campaign->setLogSkipFilter(CampaignSkipFilterEnum::PAYMENT_ONLY_CARD);

            return true;
        }

        // Se a campanha é para pagamento somente saldo
        if (( $transaction_details->getPaymentMethods() == PaymentMethodsEnum::WALLET_ONLY )
            && ( $transaction['wallet'] == 0 ))
        {
            $consumer_campaign->setLogSkipFilter(CampaignSkipFilterEnum::WALLET_BALANCE_ONLY);


            return true;
        }

        // Se a campanha é somente para uma bandeira específica de cartão
        if (( $transaction_details->getRequiredCreditCardBrands() !== null )
            && ( array_key_exists('credit_card_brand', $transaction) === true )
            && ( in_array($transaction['credit_card_brand'], $transaction_details->getRequiredCreditCardBrands()) === false ))
        {
            $consumer_campaign->setLogSkipFilter(CampaignSkipFilterEnum::CREDIT_CARD_BRAND_SPECIFIC);

            return true;
        }

        // Verifica se o valor da transação atingiu o limite mínimo requerido pela campanha
        if ( $transaction_details->getMinTransactionValue() > $transaction['total'] )
        {
            $consumer_campaign->setLogSkipFilter(CampaignSkipFilterEnum::MIN_TRANSACTION_VALUE);
            return true;
        }

        // Verifica limites de campanha por período
        if ( $this->reward_service->checkCampaignSkipLimits($campaign, $consumer_campaign) && $transaction['retroative'] == false)
        {
            $consumer_campaign->setLogSkipFilter(CampaignSkipFilterEnum::CAMPAIGN_LIMITS_BY_PERIOD);

            return true;
        }

        // Verifica se a campanha exige que pagamento seja para alguém que nunca recebeu transação alguma
        if ( ( $transaction['type'] === TransactionTypeEnum::P2P)
            && ( $transaction_details->isFirstPayeeReceivedPaymentOnly() === true )
            && ( ($transaction['first_payee_received_payment'] ?? false) === false ))
        {
            $consumer_campaign->setLogSkipFilter(CampaignSkipFilterEnum::PAYEE_RECEIVED_PAYMENT);

            return true;
        }

        // force para transacao retroativa para firstPayee
        if ( ( $transaction['type'] === TransactionTypeEnum::P2P)
            && ( $transaction_details->isFirstPayeeReceivedPaymentOnly() === true )
            && ( ($transaction['first_payee_received_payment'] ?? false) === true ) && $transaction['retroative'] === true)
        {
            $existsTransaction = $this->transactionService->firstTransactionToPayeeOnCampaign($transaction['consumer_id_payee'],$campaign->getId());
            if($existsTransaction) {
                $consumer_campaign->setLogSkipFilter(CampaignSkipFilterEnum::PAYEE_RECEIVED_PAYMENT);
                return true;
            }
        }


        // Verifica se os consumers estão na mesma campaign
        if ($transaction['type'] === TransactionTypeEnum::P2P && !$campaign->getLimits()){

            $consumersByCampaign = $campaign->getConsumers();
            if(!empty($consumersByCampaign)) {

                $consumerPayeeInCampaign = in_array($transaction['consumer_id_payee'], $consumersByCampaign);
                $consumerPayerInCampaign = in_array($consumer_campaign->getConsumerId(), $consumersByCampaign);

                if($consumerPayeeInCampaign && $consumerPayerInCampaign){
                    $consumer_campaign->setLogSkipFilter(CampaignSkipFilterEnum::CASHBACK_FARM);
                    return true;
                }
                
            }

        }

        if(count($campaign->getTransactionDetails()->getConditions()) == 1){
            // Verifica se campanha a vista tem transacao com parcelas maior que 1
            if (in_array(TransactionConditionEnum::IN_CASH, $campaign->getTransactionDetails()->getConditions()) &&
                ($transaction['installments']) > 1) {
                $consumer_campaign->setLogSkipFilter(CampaignSkipFilterEnum::IN_CASH_WITH_INSTALLMENTS);
                return true;
            }

            // Verifica se campanha de parcelados tem transacao menor que minimo de parcelas
            if ( in_array(TransactionConditionEnum::INSTALLMENTS, $campaign->getTransactionDetails()->getConditions())
                && ($transaction['installments'] < $campaign->getTransactionDetails()->getMinInstallments())) {
                $consumer_campaign->setLogSkipFilter(CampaignSkipFilterEnum::MIN_INSTALLMENTS);
                return true;
            }
        }else{ // Caso a campanha seja do tipo in_cash e installments
            if ($transaction['installments'] < $campaign->getTransactionDetails()->getMinInstallments()) {

                if ($transaction['installments'] != 1) { // A vista
                    $consumer_campaign->setLogSkipFilter(CampaignSkipFilterEnum::MIN_INSTALLMENTS_AND_IN_CASH);
                    return true;
                }

            }

        }

        $transactionSellerId = isset($transaction['external_merchant']['id']) ? $transaction['external_merchant']['id'] : null;
        $transactionExternalType = isset($transaction['external_merchant']['type']) ? $transaction['external_merchant']['type'] : null;

        // Pula campanha se ela tem restrição de external merchant (Cielo etc), mas a transação não combina
        if ( ( $campaign->getExternalMerchant() !== null )
            && ( $campaign->getExternalMerchant()->getIds() !== null )
            && ( array_key_exists('external_merchant', $transaction) === true )
            && (
                ( $transactionExternalType !== $campaign->getExternalMerchant()->getType() )
                ||
                ( in_array($transactionSellerId, $campaign->getExternalMerchant()->getIds()) === false))
            )
        {
            $consumer_campaign->setLogSkipFilter(CampaignSkipFilterEnum::RESTRICTED_CAMPAIGN);
            return true;
        }

        return false;
    }

    /**
     * Desfaz alterações causadas por cashback de uma transação
     *
     * @param int $consumer_id
     * @param string $transaction_type
     * @param int $transaction_id
     * @throws MongoDBException
     */
    private function revertChanges(int $consumer_id, string $transaction_type, int $transaction_id)
    {
        // Obtém detalhes da transação que supostamente gerou cashback
        $transaction = $this->reward_service
            ->transaction_repository->getTransaction($consumer_id, $transaction_type, $transaction_id);

        if ($transaction === null)
        {
            \Log::info('Não foi possível desfazer cashback de transação que não havia gerado cashback', [
                'transaction_id' => $transaction_id,
                'transaction_type' => $transaction_type,
                'consumer_id' => $consumer_id
            ]);

            throw new NotFoundHttpException('Transação não havia gerado cashback');
        }

        // Decrementa contador de transações da campanha
        $campaign = $transaction->getCampaign();
        $campaign->decrementCurrentTransactions();

        // Remove registro de transação problemática
        DocumentManager::remove($transaction);
        DocumentManager::flush();

        // Obtém a relação do consumer com a campanha que gerou o cashback
        $current_consumer_campaign = $this->reward_service
            ->consumer_campaign_repository->getOne($consumer_id, $campaign);

        // Decrementa contador e reverte outras alterações dessa relação
        $current_consumer_campaign->revertChanges();

        DocumentManager::flush();

        // Obtém todas as relações canceladas pela transação problemática
        $cancelled = $this->reward_service
            ->consumer_campaign_repository->getCancelledByTransaction($consumer_id, $transaction_type, $transaction_id);

        // Rollback de alterações nas relações ConsumerCampaign
        foreach ($cancelled as $consumer_campaign)
        {
            $consumer_campaign->revertChanges();
        }

        DocumentManager::flush();
    }
}
