<?php

namespace Promo\Services;

use Dotenv\Exception\ValidationException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use MongoId;
use Carbon\Carbon;
use Promo\Documents\BlackListedConsumer;
use Promo\Documents\Campaign;
use Promo\Documents\Embeded\TransactionDetails;
use Promo\Documents\Enums\TransactionTypeEnum;
use Promo\Documents\Instantcash;
use Promo\Exceptions\PromoException;
use Promo\Jobs\ListenManualPayments;
use Promo\Jobs\SendManualPaymentErrors;
use Promo\Services\Core\CoreService;
use Promo\Documents\ConsumerCampaign;
use GuzzleHttp\Exception\GuzzleException;
use Promo\Exceptions\InstantcashException;
use Promo\Documents\Enums\CampaignTypeEnum;
use Promo\Exceptions\GlobalCampaignException;
use Promo\Documents\Enums\AssociationTypeEnum;
use PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use PicPay\Common\Slack\SlackClient;

class ConsumerCampaignService
{
    const CAMPAIGN_COLUMN = 2;
    const FILE_ROWS_LIMIT = 4000;

    /**
     * ConsumerCampaign Repository
     *
     * @var \Promo\Repositories\ConsumerCampaignRepository
     */
    private $consumer_campaign_repository;

    /**
     * Campaign Repository
     *
     * @var \Promo\Repositories\CampaignRepository
     */
    private $campaign_repository;


    /**
     * BlackListedConsumer Repository
     *
     * @var \Promo\Repositories\BlackListedConsumerRespository
     */
    private $blacklisted_consumer_repository;

    /**
     * @var \Promo\Services\Core\CoreService
     */
    private $core_service;

    /**
     * ConsumerCampaignService constructor.
     * @param CoreService $core_service
     */
    public function __construct(CoreService $core_service)
    {
        $this->consumer_campaign_repository = DocumentManager::getRepository(ConsumerCampaign::class);
        $this->blacklisted_consumer_repository = DocumentManager::getRepository(BlackListedConsumer::class);
        $this->campaign_repository = DocumentManager::getRepository(Campaign::class);
        $this->core_service = $core_service;
    }

    /**
     * Obtém ou verifica a existência/validade de campanha
     *
     * @param string $campaign_id
     * @return null|Campaign
     * @throws GlobalCampaignException
     * @throws NotFoundHttpException
     */
    private function getCampaign(string $campaign_id): ?Campaign
    {
        $campaign = $this->campaign_repository->getOne($campaign_id);

        if ($campaign === null) {
            throw new NotFoundHttpException('Campanha não encontrada');
        } else {
            if ($campaign->isGlobal()) {
                throw new GlobalCampaignException();
            }
        }

        return $campaign;
    }

    /**
     * @param array $consumers
     * @param string $campaign_id
     * @param string $type
     * @return array
     * @throws GlobalCampaignException
     * @throws \MongoException
     */
    public function associateConsumers(
        array $consumers,
        string $campaign_id,
        string $type = AssociationTypeEnum::SEGMENTATION
    ) {
        $campaign = $this->getCampaign($campaign_id);

        if (!$campaign->getActive() || $campaign->isExpired()) {
            throw new \Exception('Campanha expirada ou inativa', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $blacklisted_consumers = $this->getBlackListedConsumersByCampaign($campaign, $consumers);


        $blacklisted_consumers = array_map(function ($object) {

            return $object->getConsumerId();

        }, $blacklisted_consumers->toArray());


        $consumers = array_filter($consumers, function ($element) use ($blacklisted_consumers) {
            return !in_array($element, $blacklisted_consumers);
        }
        );

        // Desvia o fluxo no caso de campanhas Instantcash
        if ($campaign->getType() === CampaignTypeEnum::INSTANTCASH) {
            $this->instantcashConsumerAssociation($consumers, $campaign);
        } else {
            $this->regularConsumerAssociation($consumers, $campaign, $type);
        }

        return $blacklisted_consumers;
    }

    /**
     * Desassocia uma campanha a consumers (campanhas de fluxo "normal")
     *
     * @param array $consumers
     * @param string $campaign_id
     * @param string $type
     * @throws GlobalCampaignException
     * @throws \MongoException
     */
    public function disassociateConsumers(
        array $consumers,
        string $campaign_id,
        string $type = AssociationTypeEnum::SEGMENTATION
    ): void {
        $campaign = $this->getCampaign($campaign_id);

        foreach ($consumers as $consumer_id) {
            $consumer_campaign = $this->consumer_campaign_repository
                ->getOne($consumer_id, $campaign);

            if ($consumer_campaign !== null) {
                $consumer_campaign->disable();
            }
        }

        DocumentManager::flush();

        \Log::info('Desassociando usuários a campanha', [
            'total' => count($consumers),
            'consumers' => $consumers,
            'campaign_id' => $campaign->getId()
        ]);
    }

    /**
     * @param Campaign $campaign
     * @param array $consumer_ids
     * @return mixed
     */
    protected function getBlackListedConsumersByCampaign(Campaign $campaign, $consumer_ids = [])
    {

        //@todo trocar essa string vazia por uma verificação mais elegante.

        $transaction_types = [];
        $details = $campaign->getTransactionDetails();
        if ($details instanceof TransactionDetails) {

            $transaction_type = $details->getType();

            // Campaign nao trabalha com o array de opcao, tem somente MIXED, PQP e PAV, necessita converter
            if ($transaction_type == TransactionTypeEnum::MIXED) {
                $transaction_types = [TransactionTypeEnum::P2P, TransactionTypeEnum::PAV];
            } else {
                $transaction_types = [$transaction_type];
            }
        }


        $blacklisted_consumers = $this->blacklisted_consumer_repository->getBlacklistedConsumersByCampaignTransactionTypes($campaign->getType(),
            $transaction_types, $consumer_ids);

        return $blacklisted_consumers;

    }


    /**
     * Em caso de campanhas Instantcash, desvia o fluxo para uma associacão
     * diferenciada que aplica reward + insere associação desativada
     *
     * @param array $consumers
     * @param Campaign $campaign
     * @param $type
     * @throws \MongoException
     */

    public function getConsumersAssociatedByCampaign($consumers, $campaign_id){
        return $this->consumer_campaign_repository->getConsumersAssociatedByCampaign($consumers, $campaign_id);
    }

    private function regularConsumerAssociation(array $consumers, Campaign $campaign, $type)
    {
        $log = Log::channel('log_php_command_stdout');

        foreach ($consumers as $consumer_id) {
            $consumer = new ConsumerCampaign($consumer_id, $campaign);
            $consumer->setCampaignActive($campaign->isActive());
            $consumer->setType($type);

            DocumentManager::persist($consumer);

            // Incrementa o contador de associações da campanha
            $campaign->getStats()->incrementCurrentAssociations();

            $log->info("associate-campaign", [
                'status_job' => 'success',
                'message_job' => "Success to associate consumers to campaign",
                'associate_type' => 'associate',
                'consumer_id' => $consumer_id,
                'campaign_id' => $campaign->getId()
            ]);
        }

        try{
            DocumentManager::flush();
        }catch (\Exception $exception){

            $log->info("associate-campaign", [
                'status_job' => 'false',
                'message_job' => "Fail to associate consumers to campaign",
                'associate_type' => 'associate',
                'campaign_id' => $campaign->getId(),
                'exception_message' => $exception->getMessage()
            ]);
        }

    }

    /**
     * Em caso de campanhas Instantcash, desvia o fluxo para uma associacão
     * diferenciada que aplica reward + insere associação desativada
     *
     * @param array $consumers
     * @param Campaign $campaign
     * @param $type
     * @throws \MongoException
     */
    private function instantcashConsumerAssociation(
        array $consumers,
        Campaign $campaign,
        string $type = AssociationTypeEnum::INSTANTCASH
    ) {
        $reward_value = $campaign->getInstantcashDetails()->getInstantcash();

        foreach ($consumers as $consumer_id) {
            try {
                // Tenta criar o documento com a relação da associação
                DocumentManager::createQueryBuilder(ConsumerCampaign::class)
                    ->insert()
                    ->setNewObj([
                        'consumer_id' => (int)$consumer_id,
                        'campaign' => new MongoId($campaign->getId()),
                        'completed_transactions' => 1, // sempre fixo como 1
                        'active' => false,
                        'campaign_active' => $campaign->isActive(),
                        'global' => false,
                        'type' => $type,
                        'created_at' => Carbon::now()
                    ])
                    ->getQuery()
                    ->execute();

                // De fato adiciona o crédito (oculto) à carteira do consumer
                $result = $this->core_service->addConsumerCredit($consumer_id, $reward_value, 'manual_credit_hidden');

                if ($result === true) {
                    // Incrementa o contador de associações da campanha e rewards dados
                    $campaign->getStats()->incrementCurrentAssociations();
                    $campaign->getStats()->incrementCurrentTransactions();

                    // Guarda registro de instantcash dado, para verificações posteriores
                    $instantcash = new Instantcash($consumer_id);
                    $instantcash->setInstantcashGiven($reward_value);
                    DocumentManager::persist($instantcash);

                    \Log::info('Instantcash dado', [
                        'consumer_id' => $consumer_id,
                        'campaign_id' => $campaign->getId(),
                        'instantcash' => true
                    ]);
                } else {
                    throw new InstantcashException;
                }
            } catch (\MongoDuplicateKeyException $e) {
                \Log::info('Usuário já recebeu benefício instantcash', [
                    'consumer_id' => $consumer_id,
                    'campaign_id' => $campaign->getId()
                ]);

                continue;
            } catch (GuzzleException | InstantcashException $ee) {
                \Log::error('Erro ao requisitar benefício instantcash ao Core', [
                    'consumer_id' => $consumer_id,
                    'campaign_id' => $campaign->getId()
                ]);

            }
        }

        DocumentManager::flush();
    }

    /**
     * Obtém as associações de consumer, considerando critérios. Paginado.
     *
     * @param int $consumer_id
     * @param array $criteria
     * @param array $sort
     * @param int $limit
     * @param int $skip
     * @return \Illuminate\Support\Collection
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function getConsumerAssociations(
        int $consumer_id,
        array $criteria = [],
        array $sort = [],
        int $limit = 10,
        int $skip = 0
    ) {
        $qb = $this->getConsumerAssociationsQuery($consumer_id, $criteria);

        // Aplica as ordenações
        foreach ($sort as $field => $order) {
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
     * Avalia os pagamentos realizados em lote de acordo com o a campanha e envia-os para processamento
     *
     * @param string $campaignId
     * @param array $payments
     * @return void
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function processBatchPayments(array $data, string $justification): void
    {
        if (count($data) > self::FILE_ROWS_LIMIT) {
            throw new PromoException("Limite de ".self::FILE_ROWS_LIMIT." linhas do arquivo ultrapassado.");
        }

        $campaignIds = $this->arrayUniqueMultidimensional($data, self::CAMPAIGN_COLUMN);

        $campaignCeilings = $this->getCampaignsCeilingById($campaignIds);


        \Log::info("Justificativa: {$justification}");

        $row = 0;
        $errors = [];
        $pendings = [];

        foreach ($data as $payment) {
            $row++;

            if (!isset($payment[1]) or !isset($payment[2])) {
                \Log::info("Ausência de informações para incluir pagamento (linha: {$row})");
                $errors[] = "Ausência de informações para incluir pagamento (linha: {$row})\n";
                continue;
            }

            $consumer = trim($payment[0]);
            $amount = trim($payment[1]);
            $campaignId = trim($payment[2]);

            if(!isset($campaignCeilings[$campaignId])) {
                \Log::info("Campanha inválida (linha: {$row})");
                $errors[] = "Campanha inválida (linha: {$row})\n";
                continue;
            }

            if (!is_numeric($consumer) or !is_numeric($amount) or !$campaignId) {
                \Log::info("Pagamento não realizado para o par de valores {$consumer}, {$amount}, {$campaignId}:
                    - Informações não correspondem a um ID/Valor válidos (linha: {$row})");

                $errors[] = "ID do Consumidor: {$consumer} - Valor: {$amount} - Campanha ID: {$campaignId} (linha: {$row})\n";

                continue;
            }

            if ($amount > $campaignCeilings[$campaignId]) {
                \Log::info("Pagamento não realizado para o consumer {$consumer}: 
                    - Teto da Campanha: R$ {$campaignCeilings[$campaignId]} - Credito pretendido: R$ {$amount} (linha: {$row})");

                $pendings[] = "ID do Consumidor: {$consumer} - Valor: {$amount} / Teto: {$campaignCeilings[$campaignId]} - Campanha ID: {$campaignId} (linha: {$row})\n";

                continue;
            }

            $info = array(
                "Consumer" => $consumer,
                "Valor" => $amount,
                "Campanha" => $campaignId
            );

            \Log::info("Disparando job de pagamento manual para consumer", $info);

            dispatch(new ListenManualPayments($consumer, $amount));
        }

        if ($pendings) {
            //SNSPP doesnt bear on a really big job. Slack doesnt bear on a really big message.
            $chunks = array_chunk($errors, 250);
            foreach ($chunks as $chunk) {
                dispatch(new SendManualPaymentErrors(
                    $chunk,
                    "Pagamentos não realizados - Teto da campanha ultrapassado."
                ));
            }

        }

        if ($errors) {
            //SNSPP doesnt bear on a really big job. Slack doesnt bear on a really big message.
            $chunks = array_chunk($errors, 250);
            foreach ($chunks as $chunk) {
                dispatch(new SendManualPaymentErrors(
                    $chunk,
                    "Pagamentos não realizados - Erro de processamento."
                ));
            }
        }

        return;
    }

    private function arrayUniqueMultidimensional(array $data, string $column): array
    {
        //Todo Put it in a helper.
        $result = [];

        foreach ($data as $row) {
            if (!isset($row[$column])) {
                continue;
            }

            $columnValue = trim($row[$column]);

            if (!in_array($columnValue, $result)) {
                $result[] = $columnValue;
            }
        }

        return $result;
    }

    private function getCampaignsCeilingById(array $campaignIds): array
    {
        $result = [];

        $campaigns = $this->campaign_repository
            ->getMany($campaignIds);

        foreach ($campaigns as $campaign) {
            $cashbackDetails = $campaign->getCashbackDetails();
            if ($cashbackDetails) {
                $result[$campaign->getId()] = $cashbackDetails->getCeiling();
            }
        }

        return $result;
    }

    /**
     * Conta as associações de um consumer a campanhas considerando critérios
     *
     * @param int $consumer_id
     * @param array $criteria
     * @return int
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function countConsumerAssociations(int $consumer_id, array $criteria): int
    {
        $total = $this->getConsumerAssociationsQuery($consumer_id, $criteria)
            ->count()
            ->getQuery()
            ->execute();

        return $total;
    }

    /**
     * Função privada que gera a query de associações de um consumer a campanhas
     *
     * @param int $consumer_id
     * @param array $criteria
     * @return \Doctrine\ODM\MongoDB\Query\Builder
     */
    public function getConsumerAssociationsQuery(int $consumer_id, array $criteria)
    {
        $qb = $this->consumer_campaign_repository->createQueryBuilder()
            ->field('consumer_id')->equals($consumer_id)
            ->field('campaign')->prime(true);


        // Filtro para `active` usando booleano em string
        if (array_key_exists('active', $criteria) === true) {
            $qb->field('active')->equals(
                filter_var($criteria['active'], FILTER_VALIDATE_BOOLEAN)
            );
        }



        // Filtro para status da associação com consumer/campaign
        if (array_key_exists('campaign_active', $criteria) === true) {
            $qb->field('campaign_active')->equals(
                filter_var($criteria['campaign_active'], FILTER_VALIDATE_BOOLEAN)
            );
        }

        if (array_key_exists('start_date', $criteria) === true) {
            $qb->field('created_at')->gte(
                new \MongoDate(strtotime($criteria['start_date']))
            );
        }

        if (array_key_exists('end_date', $criteria) === true) {
            $qb->field('created_at')->lte(
                new \MongoDate(strtotime($criteria['end_date']))
            );
        }

        return $qb;
    }
}
