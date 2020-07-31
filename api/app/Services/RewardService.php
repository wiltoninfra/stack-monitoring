<?php

namespace Promo\Services;

use Illuminate\Support\Facades\Log;
use Promo\Documents\Deposit;
use Promo\Documents\Campaign;
use Promo\Documents\Transaction;
use Promo\Documents\ConsumerCampaign;
use Promo\Documents\Enums\CampaignTypeEnum;
use Promo\Documents\Enums\PaymentMethodsEnum;
use PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager;
use Promo\Services\DigitalAccount\DigitalAccountService;

/**
 * Classe base responsável por todos os rewards distribuídos pelo Promo
 */
class RewardService
{

    /**
     * Repositório de Campaign
     *
     * @var DigitalAccountService
     */
    public $digital_account_service;


    /**
     * Repositório de Campaign
     *
     * @var \Promo\Repositories\CampaignRepository
     */
    public $campaign_repository;

    /**
     * Repositório de ConsumerCampaign
     *
     * @var \Promo\Repositories\ConsumerCampaignRepository
     */
    public $consumer_campaign_repository;

    /**
     * Repositório de TransactionRepository
     *
     * @var \Promo\Repositories\TransactionRepository
     */
    public $transaction_repository;

    /**
     * Repositório de DepositRepository
     *
     * @var \Promo\Repositories\DepositRepository
     */
    public $deposit_repository;

    /**
     * Número de transações para cancelar uma campanha que `sobra`
     */
    const LIMIT_TRANSACTIONS = 1;
    /**
     * @var CampaignVersionService
     */
    private $campaignVersionService;

    public function __construct(
        DigitalAccountService $digital_account_service,
        CampaignVersionService $campaignVersionService
    )
    {
        $this->digital_account_service = $digital_account_service;
        $this->campaignVersionService = $campaignVersionService;
        $this->campaign_repository = DocumentManager::getRepository(Campaign::class);
        $this->transaction_repository = DocumentManager::getRepository(Transaction::class);
        $this->deposit_repository = DocumentManager::getRepository(Deposit::class);
        $this->consumer_campaign_repository = DocumentManager::getRepository(ConsumerCampaign::class);
    }

    /**
     * Obtém lista de ids de campanhas ativas e associadas a um usuário
     *
     * @param integer $consumer_id
     * @param array $transaction
     * @return array
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function getConsumerActiveAssociatedCampaigns(int $consumer_id, array $transaction = []): array
    {
        $associated_campaigns = $this->consumer_campaign_repository->getConsumerActiveAssociatedCampaigns($consumer_id, $transaction);

        $associated = [];
        foreach ($associated_campaigns as $item)
        {
            $id = (string) $item['campaign'];
            $associated[] = $id;
        }

        return $associated;
    }

    /**
     * Obtém array de ids de campanhas desativadas de um consumer
     *
     * @param integer $consumer_id
     * @return array
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function getConsumerDisabledCampaigns(int $consumer_id): array
    {
        $global_campaigns = $this->consumer_campaign_repository->getConsumerDisabledCampaigns($consumer_id);

        $disassociated = [];
        foreach ($global_campaigns as $item)
        {
            $id = (string) $item['campaign'];
            $disassociated[] = $id;
        }

        return $disassociated;
    }

    /**
     * Obtém array de ids de campanhas globais ativas
     *
     * @return array
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function getGlobalActiveCampaignsOnly(): array
    {
        $global_campaigns = $this->campaign_repository->getGlobalActiveCampaignsOnly();

        $global = [];
        foreach ($global_campaigns as $item)
        {
            $id = (string) $item['_id'];
            $global[] = $id;
        }

        return $global;
    }

    /**
     * Retorna (buscando ou criando) uma relação ConsumerCampaign
     *
     * @param integer $consumer_id
     * @param Campaign $campaign
     * @return ConsumerCampaign
     */
    public function getConsumerCampaign(int $consumer_id, Campaign $campaign)
    {
//        dd($campaign);

        $consumer_campaign = $this->consumer_campaign_repository->getOne($consumer_id, $campaign);


        // Se a relação não existir, cria e persiste
        if ($consumer_campaign === null)
        {
            $campaign = $this->campaign_repository->find($campaign->getId());
            $consumer_campaign = new ConsumerCampaign($consumer_id, $campaign);
            DocumentManager::persist($consumer_campaign);
        }

        $consumer_campaign->setGlobal($campaign->isGlobal());

        return $consumer_campaign;
    }

    /**
     * Conta quantas transações um consumer fez para uma campanha
     * em no dia do momento da execução
     *
     * @param int $consumer_id
     * @param Campaign $campaign
     * @return int
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function countUsesOfToday(int $consumer_id, Campaign $campaign): int
    {
        switch ($campaign->getType())
        {
            case CampaignTypeEnum::CASHBACK:
                // deprecado para cashback
                return 0;
            break;

            case CampaignTypeEnum::CASHFRONT:
                return $this->deposit_repository->countDepositsOfToday($consumer_id, $campaign);
            break;
        }

        return 0;
    }

    /**
     * Informa se é o primeiro reward do consumer
     *
     * @param int $consumer_id
     * @return bool
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */

    public function isFirstConsumerReward(int $consumer_id):bool {
        return !$this->transaction_repository->hasTransactions($consumer_id);
    }
    
    /**
     * Calcula o valor da recompensa de uma campanha,
     * considerando o tipo da campanha e os valores da transação
     *
     * @param Campaign $campaign
     * @param array $data
     * @return float
     */
    public function calculateRewardValue(Campaign $campaign, array $data): float
    {
        $total = 0;
        $max_value = 0;
        $reward_value = 0;

        switch ($campaign->getType())
        {
            case CampaignTypeEnum::CASHBACK:
                $payment_methods = $campaign->getTransactionDetails()->getPaymentMethods();

                // Muda o valor base do cálculo, de acordo com os métodos de pagamento da campanha
                if (in_array(PaymentMethodsEnum::WALLET, $payment_methods))
                {
                    $total += $data['wallet'];
                }

                if (in_array(PaymentMethodsEnum::CREDIT_CARD, $payment_methods))
                {
                    $total += $data['credit_card'];
                }

                $cashback_details = $campaign->getCashbackDetails();

                // Porcentagem do cashback a ser aplicada
                $reward_value = $cashback_details->getCashback();

                // Limita o valor da transação pelo valor máximo
                $max_value = $cashback_details->getCeiling();

                break;

            case CampaignTypeEnum::CASHFRONT:
                $total = $data['total'];

                $cashfront_details = $campaign->getCashfrontDetails();

                // Porcentagem do cashback a ser aplicada
                $reward_value = $cashfront_details->getCashfront();

                // Limita o valor da transação pelo valor máximo
                $max_value = $cashfront_details->getCeiling();

                break;
        }

        // Limita pelo valor máximo
        if ($total > $max_value)
        {
            $total = $max_value;
        }

        // Calcula o valor do reward
        $reward = $total * ($reward_value / 100);
        
        return number_format($reward, 2, '.', '');
    }

    /**
     * Processa a melhor campanha e descarta as similares, se existir uma melhor campanha
     *
     * @param array $fittest
     * @param array $data
     * @param array $possibly_discarded
     * @param array $discarded
     * @param int $consumer_id
     * @return bool
     */
    public function proccessFittestCampaign(array $fittest, array $data, array $possibly_discarded, array $discarded, int $consumer_id):bool
    {
        Log::info('starting_proccess_fittest_campaign', [
            'context' => 'cashback',
            'status' => 'success',
            'transaction' => $data,
            'consumer_id' => $consumer_id
        ]);

        $campaign = $fittest['campaign'] ?? null;
        $consumer_campaign = $fittest['consumer_campaign'] ?? null;
        $isTransactionRetroative = $data['retroative'] ?? false;

        // Verifica se existe uma melhor campanha selecionada
        if ($campaign === null || $consumer_campaign === null  ) {
            Log::info('no_cashback_given', [
                'context' => 'cashback',
                'status' => 'success',
                'consumer_id' => $consumer_id,
                'transaction' => $data,
            ]);

            return false;
        }

        // Verifica se esta campanha esta dentro dos limites do Digital Account
        if (!$this->digital_account_service->isAccountUnderLimits($consumer_id,$fittest['cashback'] ?? $fittest['cashfront']))  {
            Log::info('max_digital_account_limit', [
                'context' => 'cashback',
                'status' => 'success',
                'consumer_id' => $consumer_id,
                'transaction' => $data,
                'campaign' => $campaign->getArray()
            ]);
            return false;
        }

        $consumer_campaign->incrementCompletedTransactions();

        // Transacao retroativa deve ser incrementada na campanha atual.
        if($isTransactionRetroative) {
            Log::info(
                'campaign_version_increment_transactions',
                [
                    'context' => 'cashback',
                    'status' => 'success',
                    'version_id' => $campaign->transactionVersion,
                    'transaction' => [
                        'id' => $data['id']
                    ]
                ]
            );

            unset($campaign->transactionVersion);

            $campaignVersionedId = $campaign->getId();
            $campaign = $this->campaign_repository->find($campaignVersionedId);
        }

        $campaign->getStats()->incrementCurrentTransactions();

        // Possível desativa a campanha escolhida
        $this->disableConsumerCampaign($consumer_campaign, $data);

        // E possivelmente desativa as puladas e escolhidas, mas que não tiveram o maior valor de recompensa
        $this->disableDiscardedConsumerCampaigns($possibly_discarded, $data);

        // E desativa forçadamente as da fila de descarte
        $this->disableDiscardedConsumerCampaigns($discarded, $data, true);

        $this->storeTransactionLog($data, $consumer_id, $fittest);

        Log::info('cashback_given', [
            'context' => 'cashback',
            'status' => 'success',
            'consumer_id' => $consumer_id,
            'campaign' => [
                'id' => $campaign->getId()
            ],
            'transaction' => $data,
            'cashback' => $fittest['cashback'] ?? 0,
            'cashfront' => $fittest['cashfront'] ?? 0,
        ]);

        return true;
    }

    /**
     * Desativa as relações de campanha e consumer
     * das campanhas descartadas no processo de seleção
     * da melhor campanha
     *
     * @param array $discarded
     * @param array $data
     * @param bool $force_disable
     * @return void
     */
    private function disableDiscardedConsumerCampaigns(array $discarded, array $data, bool $force_disable = false)
    {
        foreach ($discarded as $consumer_campaign)
        {
            $this->disableConsumerCampaign($consumer_campaign, $data, $force_disable);
        }
    }

    /**
     * Desativa campanha para o usuário e informa qual transação a cancelou
     *
     * @param ConsumerCampaign $consumer_campaign
     * @param array $data
     * @param bool $force_disable
     * @return void
     */
    private function disableConsumerCampaign(ConsumerCampaign $consumer_campaign, array $data, bool $force_disable = false): void
    {
        $campaign = $consumer_campaign->getCampaign();

        switch($campaign->getType())
        {
            case CampaignTypeEnum::CASHBACK:
                $this->disableCashbackConsumerCampaign($consumer_campaign, $data, $force_disable);
                break;

            case CampaignTypeEnum::CASHFRONT:
                $this->disableCashfrontConsumerCampaign($consumer_campaign, $data, $force_disable);
                break;
        }
    }

    /**
     * Cancelamento específico para cashback
     *
     * @param ConsumerCampaign $consumer_campaign
     * @param array $data
     * @param bool $force_disable
     */
    private function disableCashbackConsumerCampaign(ConsumerCampaign $consumer_campaign, array $data, bool $force_disable = false)
    {
        $campaign = $consumer_campaign->getCampaign();
        $info = [
            'consumer_id' => $consumer_campaign->getConsumerId(),
            'campaign_id' => $campaign->getId(),
        ];

        // Se force_disable estiver marcado, porém, desativa as relações, independente dos limites
        if (($force_disable === true)
             || ($campaign->getLimits() !== null
                 && $campaign->getLimits()->getUsesPerConsumer() !== null
                 && $campaign->getLimits()->getUsesPerConsumer()->getUses() === $campaign->getStats()->getCurrentTransactions()))
        {
            $consumer_campaign->setCancelledByTransaction($data);
            $consumer_campaign->disable();

            $info = array_merge([
                'transaction_id' => $data['id'] ?? null,
                'transaction_type' => $data['type'] ?? null,
            ], $info);

            \Log::info('Campanha descartada para consumer', $info);
        }
    }

    /**
     * Cancelamento específico para cashfront
     *
     * @param ConsumerCampaign $consumer_campaign
     * @param array $data
     * @param bool $force_disable
     */
    private function disableCashfrontConsumerCampaign(ConsumerCampaign $consumer_campaign, array $data, bool $force_disable = false)
    {
        $campaign = $consumer_campaign->getCampaign();
        $info = [
            'consumer_id' => $consumer_campaign->getConsumerId(),
            'campaign_id' => $campaign->getId(),
        ];

        // Se force_disable estiver marcado, porém, desativa as relações, independente dos limites
        // ou verifica se alcançou limites de cancelamento
        if ((($force_disable === true)
             || ($campaign->getDepositDetails() !== null
                 && $campaign->getDepositDetails()->getMaxDepositsPerConsumer() !== null
                 && $consumer_campaign->getCompletedTransactions() >= $campaign->getDepositDetails()->getMaxDepositsPerConsumer())))
        {
            $consumer_campaign->setCancelledByDeposit($data['id']);
            $consumer_campaign->disable();

            $info = array_merge([
                'consumer_id' => $consumer_campaign->getConsumerId(),
                'campaign_id' => $campaign->getId(),
                'deposit_id' => $data['id'],
            ], $info);

            \Log::info('Campanha descartada para consumer', $info);
        }
    }

    /**
     * Transações que geram recompensa são armazenadas no banco,
     * para efeitos de log
     *
     * @param array $data - dados vindos da requisição do Core
     * @param integer $consumer_id
     * @param array $reward - dados que serão retornados
     * @return void
     */
    private function storeTransactionLog(array $data, int $consumer_id, array $reward): void
    {
//        dd($reward);
        $campaign = $reward['campaign'];
        $reward['campaign'] = $this->campaign_repository->find($campaign->getId());
//        $campaign = $reward['campaign'];


        if ($campaign !== null && $campaign->getType() === CampaignTypeEnum::CASHBACK
            && isset($reward['cashback']) && $reward['cashback'] > 0)
        {
            // Remove dados duplicados do payload extra que já vão para a raiz do modelo
            $filtered_details = array_diff_key($data, array_flip(['id', 'type']));

            $transaction_obj = new Transaction($data['id'], $data['type'], $consumer_id);
            $transaction_obj->setTransactionValue($data['total'])
                ->setDetails($filtered_details)
                ->setCampaign($reward['campaign'])
                ->setCashbackGiven($reward['cashback']);

            DocumentManager::persist($transaction_obj);
        }
        // Em rewards do tipo depósito, guarda log apropriado
        else if ($campaign !== null && $campaign->getType() === CampaignTypeEnum::CASHFRONT
                 && isset($reward['cashfront']) && $reward['cashfront'] > 0)
        {
            $deposit_obj = new Deposit($consumer_id);
            $deposit_obj->setDepositValue($data['total'])
                ->setDetails($data)
                ->setCampaign($reward['campaign'])
                ->setCashfrontGiven($reward['cashfront']);

            DocumentManager::persist($deposit_obj);
        }
    }

    /**
     * Método que verifica condições de pulo de campanha, de acordo com limites
     *
     * @param Campaign $campaign
     * @param ConsumerCampaign $consumer_campaign
     * @return bool
     */
    public function checkCampaignSkipLimits(Campaign $campaign, ConsumerCampaign $consumer_campaign): bool
    {
        $limits = $campaign->getLimits();

        // Se existem limites de consumer por periodo e foram atingidos
        return ( $limits !== null && $limits->getUsesPerConsumerPerPeriod() !== null
                && $limits->getUsesPerConsumerPerPeriod()->getUses() !== null
                && $limits->getUsesPerConsumerPerPeriod()->getUses() <=
                   $this->transaction_repository->processTransactionsOfPeriod($consumer_campaign,
                       $limits->getUsesPerConsumerPerPeriod()->getType(),
                       $limits->getUsesPerConsumerPerPeriod()->getPeriod())
        );
    }

    /**
     * Método que verifica condições de descarte de campanha, de acordo com limites
     *
     * @param Campaign $campaign
     * @param ConsumerCampaign $consumer_campaign
     * @return bool
     */
    public function checkCampaignDiscardLimits(Campaign $campaign, ConsumerCampaign $consumer_campaign): bool
    {
        $limits = $campaign->getLimits();

        // Se existem limites gerais por consumer e se eles foram atingidos
        return ( $limits !== null && $limits->getUsesPerConsumer() !== null
                 && $limits->getUsesPerConsumer()->getUses() !== null
                 && $limits->getUsesPerConsumer()->getUses() <=
                    $this->transaction_repository->processTransactionsOfPeriod($consumer_campaign,
                        $limits->getUsesPerConsumer()->getType())
        );
    }

    /**
     * @param array $associated
     * @param array $disassociated
     * @param array $transaction
     * @return mixed
     */
    public function getCompatibleCashbackCampaigns(array $associated, array $disassociated, array $transaction)
    {
        Log::info('get_compatible_campaigns', [
            'context' => 'cashback',
            'status' => 'success',
            'transaction' => $transaction,
            'campaigns_associated' => $associated,
            'campaigns_disassociated' => $disassociated
        ]);

        $isTransactionRetroative = $transaction['retroative'];

        if ($isTransactionRetroative) {
           return $this->campaignVersionService->getAllCompatibleCashbackRetroativeCampaigns($associated, $disassociated, $transaction);
        }

        return $this->campaign_repository->getAllCompatibleCashbackCampaigns($associated, $disassociated, $transaction);



    }
}
