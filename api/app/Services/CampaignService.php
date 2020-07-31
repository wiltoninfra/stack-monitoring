<?php

namespace Promo\Services;

use Promo\Documents\Campaign;
use Doctrine\MongoDB\Query\Builder;
use Promo\Documents\Webhook;
use Promo\Events\Campaign\CampaignChangeEvent;
use Promo\Exceptions\PromoException;
use Promo\Exceptions\ValidationException;
use Promo\Documents\Enums\CampaignTypeEnum;
use Promo\Documents\Enums\TransactionTypeEnum;
use Promo\Exceptions\ExpiredCampaignException;
use PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use PicPay\Common\Slack\SlackClient;
use Carbon\Carbon;

class CampaignService
{
    /**
     * Repositório de Campaign
     *
     * @var \Promo\Repositories\CampaignRepository
     */
    private $repository;

    /**
     * Serviço de Transação
     *
     * @var TransactionService
     */
    private $transaction_service;

    /**
     * @var TagService
     */
    private $tag_service;

    public function __construct(TransactionService $transaction_service, TagService $tag_service)
    {
        $this->tag_service = $tag_service;
        $this->transaction_service = $transaction_service;
        $this->repository = DocumentManager::getRepository(Campaign::class);
    }

    /**
     * Retorna todos os possíveis tipos de campanha
     *
     * @return array
     */
    public function getTypes(): array
    {
        return [
            'types' => array_values(CampaignTypeEnum::getFields())
        ];
    }


    /**
     * Cria uma campanha
     *
     * @param array data
     * @return Campaign
     *
     * @throws ValidationException
     */
    public function create(array $data): Campaign
    {
        $campaign = new Campaign($data['type']);

        // Popula o objeto com os dados recebidos
        $this->fill($campaign, $data);
        DocumentManager::persist($campaign);

        event(new CampaignChangeEvent($campaign->getId()));
        DocumentManager::flush();

        return $campaign;
    }

    /**
     * Substitui (atualiza) conteúdo de campanha
     *
     * @param array $data
     * @param string $campaign_id
     * @return null|Campaign
     *
     * @throws ValidationException
     */
    public function update(array $data, string $campaign_id): ?Campaign
    {
        $campaign = $this->getOne($campaign_id);
        // Atualiza os dados do objeto
        $this->fill($campaign, $data);
        event(new CampaignChangeEvent($campaign->getId()));
        DocumentManager::flush();

        return $campaign;
    }

    /**
     * Obtém uma campanha, por id
     *
     * @param string $campaign_id
     * @return Campaign
     */
    public function get(string $campaign_id): Campaign
    {
        $campaign = $this->getOne($campaign_id);

        // Desativa campanha expirada
        if ($campaign->isExpired()) {
            $campaign->disable();
        }

        DocumentManager::flush();

        return $campaign;
    }

    /**
     * Add Consumers na campanha (p2p que recebem pagamentos)
     *
     * @param int $consumer_id
     * @param string $campaign_id
     * @return null|Campaign
     *
     * @throws ValidationException
     */
    public function addConsumer(int $consumer_id, string $campaign_id): ?Campaign
    {
        $campaign = $this->getOne($campaign_id);

        $campaign->addConsumer($consumer_id);
        event(new CampaignChangeEvent($campaign->getId()));
        DocumentManager::flush();

        return $campaign;
    }

    /**
     * Obtém todas as campanhas e aplica as condições
     *
     * @param array $criteria
     * @param array $sort
     * @param int $limit
     * @param int $skip
     * @return mixed
     */
    public function getAll(array $criteria = [], array $sort = [], int $limit = 10, int $skip = 0)
    {
        $qb = $this->getAllQuery($criteria);

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
     * Conta todos os resultados de campanha
     *
     * @param array $criteria
     * @return int
     */
    public function countAll(array $criteria = []): int
    {
        $total = $this->getAllQuery($criteria)
            ->count()
            ->getQuery()
            ->execute();

        return $total;
    }

    /**
     * Conta somente os ativos
     *
     * @param array $criteria
     * @return int
     */
    public function countActive(array $criteria = []): int
    {
        if (array_key_exists('active', $criteria)
            && filter_var($criteria['active'], FILTER_VALIDATE_BOOLEAN) == false) {
            return 0;
        }

        $criteria['active'] = true;

        $active = $this->getAllQuery($criteria)
            ->count()
            ->getQuery()
            ->execute();

        return $active;
    }

    /**
     * Constrói a query a ser utilizada no retorno de todas as campanhas
     * e na contagem delas
     *
     * @param array $criteria
     * @return Builder
     */
    public function getAllQuery(array $criteria): Builder
    {

        $qb = $this->repository->createQueryBuilder()
            ->field('deleted_at')->exists(false)
            ->field('tags')->prime(true);

        // Filtro para webhook
        if (array_key_exists('webhook', $criteria) === true) {
            $dm = $this->repository->getDocumentManager();
            $repo = $dm->getRepository(Webhook::class);
            $webhook = $repo->find($criteria['webhook']);

            if (!is_null($webhook)) {
                $campanha = $webhook->getCampaign();
                $qb->field('id')->equals($campanha->getId());
            } else {
                $qb->field('id')->equals($criteria['webhook']);
            }
        }

        // Filtro para `communication`
        if (array_key_exists('communication', $criteria) === true) {
            $qb->field('communication')->equals(
                (bool)filter_var($criteria['communication'], FILTER_VALIDATE_BOOLEAN)
            );
        }

        // Filtro para camapanhas só do tipo PAV
        if (array_key_exists('transaction.type', $criteria) === true) {
            $qb->field('transaction.type')->equals($criteria['transaction.type']);
        }

        // Filtro para `active` usando booleano em string
        if (array_key_exists('active', $criteria) === true) {
            $qb->field('active')->equals(
                (bool)filter_var($criteria['active'], FILTER_VALIDATE_BOOLEAN)
            );
        }


        // Filtro para tipo de campanha
        if (array_key_exists('type', $criteria) === true) {
            $qb->field('type')->equals($criteria['type']);
        }

        // Filtro para nome de campanha
        if (array_key_exists('name', $criteria) === true) {
            $name = $criteria["name"];
            $qb->field('name')->equals(new \MongoRegex("/$name/"));
        }

        // Filtro para `global` usando booleano em string
        if (array_key_exists('global', $criteria) === true) {
            $qb->field('global')->equals(
                (bool)filter_var($criteria['global'], FILTER_VALIDATE_BOOLEAN)
            );
        }

        if (array_key_exists('start_date', $criteria) === true) {
            $qb->field('duration.start_date')->gte(
                new \MongoDate(strtotime($criteria['start_date']))
            );
        }

        if (array_key_exists('end_date', $criteria) === true) {
            $qb->field('duration.end_date')->lte(
                new \MongoDate(strtotime($criteria['end_date']))
            );
        }
        
        if (array_key_exists('expire_date', $criteria) === true) {
            $day = Carbon::parse($criteria['expire_date']);

            $start_of_day = $day->copy()->startOfDay();
            $end_of_day = $day->copy()->endOfDay();

            $qb->field('duration.fixed')->equals(true);

            $qb->field('duration.end_date')->gte(
                new \MongoDate(strtotime($start_of_day))
            );

            $qb->field('duration.end_date')->lte(
                new \MongoDate(strtotime($end_of_day))
            );
        }

        // Filtro por lista de ids separados por vírgula
        if (array_key_exists('ids', $criteria) === true) {
            $ids = explode(',', $criteria['ids']);

            $qb->field('_id')->in($ids);
        }

        // Filtro por lista de sellers
        if (array_key_exists('sellers', $criteria) === true) {
            $sellers_ids = array_map("intval", explode(',', $criteria['sellers']));
            $qb->addAnd(
                $qb->expr()->field('sellers')->in($sellers_ids)
            );
        }

        // Filtro para consumers
        if (array_key_exists('consumers', $criteria) === true) {
            $consumers_ids = array_map("intval", explode(',', $criteria['consumers']));
            $qb->addAnd(
                $qb->expr()->field('consumers')->in($consumers_ids)
            );
        }

        // Filtro por lista de tags
        if (array_key_exists('tags', $criteria) === true) {
            $ids = explode(',', $criteria['tags']);

            foreach ($ids as $id) {
                $qb->addOr(
                    $qb->expr()->field('tags')->equals($id)
                );
            }
        }

        // Filtra por tipos de sellers
        if (array_key_exists('sellers_types', $criteria) === true) {
            $types = explode(',', $criteria['sellers_types']);

            foreach ($types as $type) {
                $qb->addAnd(
                    $qb->expr()->field('sellers_types')->equals($type)
                );
            }
        }

        // E tipo da transação
        if (array_key_exists('transaction_type', $criteria) === true) {
            $qb->addAnd(
                $qb->expr()->field('transaction.type')->in([$criteria['transaction_type'], TransactionTypeEnum::MIXED])
            );
        }

        return $qb;
    }

    /**
     * Atualiza status de campanha não expirada
     *
     * @param boolean $status
     * @param string $campaign_id
     * @return Campaign
     * @throws ExpiredCampaignException
     */
    public function updateStatus(bool $status, string $campaign_id): Campaign
    {
        $campaign = $this->getOne($campaign_id);

        if ($campaign->isExpired()) {
            throw new ExpiredCampaignException();
        }

        $campaign->setActive($status);
        event(new CampaignChangeEvent($campaign->getId()));
        DocumentManager::flush();

        return $campaign;
    }

    /**
     * Atualiza sellers de campanha
     *
     * @param array $data
     * @param string $campaign_id
     * @return null|Campaign
     *
     * @throws ValidationException
     */
    public function updateSellers(array $data, string $campaign_id): ?Campaign
    {
        $campaign = $this->getOne($campaign_id);

        $campaign->setSellers(array_unique(array_merge($data, (array)$campaign->getSellers())));

        if (
            empty($campaign->getSellers()) &&
            $campaign->isGlobal() &&
            $campaign->getTransactionDetails()->getType() == TransactionTypeEnum::PAV
        ) {
            $campaign->disable();
        }

        event(new CampaignChangeEvent($campaign->getId()));
        DocumentManager::flush();

        return $campaign;
    }

    /**
     * @param array $data
     * @param string $campaignId
     * @return Campaign|null
     */
    public function removeSellers(array $data, string $campaignId): ?Campaign
    {
        $campaign = $this->getOne($campaignId);

        $campaign->setSellers(array_diff((array)$campaign->getSellers(), $data));

        if (
            empty($campaign->getSellers()) &&
            $campaign->isGlobal() &&
            $campaign->getTransactionDetails()->getType() == TransactionTypeEnum::PAV
        ) {
            $campaign->disable();
        }

        event(new CampaignChangeEvent($campaign->getId()));
        DocumentManager::flush();

        return $campaign;
    }

    /**
     * Obtém uma campanha, por id
     *
     * @param string $campaign_id
     * @return Campaign
     */
    public function getOne(string $campaign_id): Campaign
    {
        $campaign = $this->repository->getOne($campaign_id);
        //todo verificar serviços que chamam esse método
        if ($campaign === null) {
            throw new NotFoundHttpException('Campanha não encontrada.');
        }

        return $campaign;
    }

    /**
     * Remove uma campanha do banco de dados (soft delete)
     *
     * @param string $campaign_id
     * @return void
     */
    public function delete(string $campaign_id, bool $soft = true)
    {
        $campaign = $this->getOne($campaign_id);


        if ($soft) {

            $campaign->delete();
        } else {
            DocumentManager::remove($campaign);
        }
        event(new CampaignChangeEvent($campaign->getId()));
        DocumentManager::flush();
    }

    /**
     * @param array $data
     * @return Campaign
     * @throws ValidationException
     */
    public function campaignVersionToCampaign(array $data)
    {
        $campaign = new Campaign($data['type']);
        return $this->fill($campaign, $data);

    }

    /**
     * Preenche os dados de campanha
     *
     * @param Campaign $campaign
     * @param array $data
     * @return Campaign
     *
     * @throws ValidationException
     */
    private function fill(Campaign $campaign, array $data): Campaign
    {
        switch ($campaign->getType()) {
            case CampaignTypeEnum::CASHBACK:
                return $this->fillCashbackCampaign($campaign, $data);
                break;

            case CampaignTypeEnum::CASHFRONT:
                return $this->fillCashfrontCampaign($campaign, $data);
                break;

            case CampaignTypeEnum::INSTANTCASH:
                return $this->fillInstantcashCampaign($campaign, $data);
                break;
        }

        throw new ValidationException('Tipo inválido de campanha');
    }

    /**
     * Preenche os dados de campanha de cashback
     *
     * @param Campaign $campaign
     * @param array $data
     * @return Campaign
     */
    private function fillCashbackCampaign(Campaign $campaign, array $data): Campaign
    {


        // Seta as informações gerais de campanha
        $campaign
            ->setName($data['name'])
            ->setDescription($data['description'])
            ->setGlobal($data['global'])
            ->setCommunication($data['communication'] ?? false)
            ->setConsumers($data['consumers'] ?? null)
            ->setSellers($data['sellers'] ?? null)
            ->setSellersTypes($data['sellers_types'] ?? null)
            ->setExceptSellers($data['except_sellers'] ?? null)
            ->setWebhookUrl($data['webhook_url'] ?? null)
            ->setWebviewUrl($data['webview_url'] ?? null);

            if (isset($data['id'])) {
                $campaign->setCreatedAt($data['created_at']);
                $campaign->setId($data['id']);
                $campaign->setActive($data['active']);
            }


        // Informações e restrições sobre transação de campanha
        $campaign->getTransactionDetails()
            ->setType($data['transaction']['type'])
            ->setPaymentMethods($data['transaction']['payment_methods'])
            ->setMaxTransactions($data['transaction']['max_transactions'] ?? null)
            ->setMaxTransactionsPerConsumer($data['transaction']['max_transactions_per_consumer'] ?? null)
            ->setMaxTransactionsPerConsumerPerDay($data['transaction']['max_transactions_per_consumer_per_day'] ?? null)
            ->setRequiredMessage($data['transaction']['required_message'] ?? null)
            ->setMinTransactionValue($data['transaction']['min_transaction_value'] ?? 0)
            ->setFirstPayment($data['transaction']['first_payment'] ?? null)
            ->setFirstPaymentToSeller($data['transaction']['first_payment_to_seller'] ?? null)
            ->setFirstPayeeReceivedPaymentOnly($data['transaction']['first_payee_received_payment_only'] ?? null)
            ->setRequiredCreditCardBrands($data['transaction']['credit_card_brands'] ?? null)
            ->setMinInstallments($data['transaction']['min_installments'])
            ->setConditions($data['transaction']['conditions']);

        // Informações sobre cashback
        $campaign->getCashbackDetails()
            ->setCashback($data['cashback']['percentage'])
            ->setPaidBy($data['cashback']['paid_by'])
            ->setCeiling($data['cashback']['max_value']);

        // Informações sobre campanhas no APP (tela Minhas promoões
        if (@$data["app"]) {
            $campaign->getAppDetails()
                ->setActionType($data['app']['action_type'])
                ->setActionData($data['app']['action_data'])
                ->setImage($data['app']['image'])
                ->setDescription($data['app']['description'])
                ->setCategory($data['app']['category'])
                ->setTracking($data['app']['tracking']);
        }


        // Informações sobre duração
        $campaign->getDurationDetails()
            ->setFixed($data['duration']['fixed'])
            ->setStartDate($data['duration']['start_date'] ?? null)
            ->setEndDate($data['duration']['end_date'] ?? null)
            ->setHours($data['duration']['hours'] ?? 0)
            ->setDays($data['duration']['days'] ?? 0)
            ->setMonths($data['duration']['months'] ?? 0)
            ->setWeeks($data['duration']['weeks'] ?? 0);


        // Configura o sub objeto de limites
        $campaign->setLimits($data['limits'] ?? null);

        // Informações de campanhas para lojistas que não estão na base do PicPay (Cielo etc.)
        $campaign->setExternalMerchant($data['external_merchant'] ?? null);

        // Substitui tags da campanha
        $tags = $this->tag_service
            ->getMany($data['tags'] ?? null);

        $campaign->replaceTags($tags);

        return $campaign;
    }

    /**
     * Preenche os dados de campanha de cashfront
     *
     * @param Campaign $campaign
     * @param array $data
     * @return Campaign
     * @throws ValidationException
     */
    private function fillCashfrontCampaign(Campaign $campaign, array $data): Campaign
    {
        // Seta as informações gerais de campanha
        $campaign->setName($data['name'])
            ->setDescription($data['description'])
            ->setGlobal($data['global'])
            ->setCommunication($data['communication'] ?? false)
            ->setWebhookUrl($data['webhook_url'] ?? null)
            ->setWebviewUrl($data['webview_url'] ?? null);

        // Informações sobre depósitos
        $campaign->getDepositDetails()
            ->setMaxDepositsPerConsumer($data['deposit']['max_deposits_per_consumer'] ?? null)
            ->setMaxDepositsPerConsumerPerDay($data['deposit']['max_deposits_per_consumer_per_day'] ?? null)
            ->setMaxDeposits($data['deposit']['max_deposits'] ?? null)
            ->setFirstDepositOnly($data['deposit']['first_deposit_only'] ?? false)
            ->setMinDepositValue($data['deposit']['min_deposit_value'] ?? 0);

        // Informações sobre cashback
        $campaign->getCashfrontDetails()
            ->setCashfront($data['cashfront']['percentage'] ?? null)
            ->setCeiling($data['cashfront']['max_value'] ?? null)
            ->setRechargeMethod($data['cashfront']['recharge_method'] ?? null);

        // Informações sobre duração
        $campaign->getDurationDetails()
            ->setFixed($data['duration']['fixed'])
            ->setStartDate($data['duration']['start_date'] ?? null)
            ->setEndDate($data['duration']['end_date'] ?? null)
            ->setHours($data['duration']['hours'] ?? 0)
            ->setDays($data['duration']['days'] ?? 0)
            ->setMonths($data['duration']['months'] ?? 0)
            ->setWeeks($data['duration']['weeks'] ?? 0);


        // Substitui tags da campanha
        $tags = $this->tag_service
            ->getMany($data['tags'] ?? null);

        $campaign->replaceTags($tags);

        return $campaign;
    }

    /**
     * Preenche os dados de campanha instantcash
     *
     * @param array $data
     * @param Campaign $campaign
     * @return Campaign
     */
    private function fillInstantcashCampaign(Campaign $campaign, array $data): Campaign
    {
        // Seta as informações gerais de campanha
        $campaign->setName($data['name'])
            ->setDescription($data['description'])
            ->setGlobal(false)
            ->setCommunication($data['communication'] ?? false)
            ->setWebhookUrl($data['webhook_url'] ?? null)
            ->setWebviewUrl($data['webview_url'] ?? null);

        // Informações sobre cashback
        $campaign->getInstantcashDetails()
            ->setInstantcash($data['instantcash']['value'] ?? 0.0);

        // Informações sobre duração
        $campaign->getDurationDetails()
            ->setFixed(true)
            ->setStartDate($data['duration']['start_date'])
            ->setEndDate($data['duration']['end_date']);

        // Substitui tags da campanha
        $tags = $this->tag_service
            ->getMany($data['tags'] ?? null);

        $campaign->replaceTags($tags);

        return $campaign;
    }

    /**
     * Retorna o objeto de stats de uma campanha dado seu ID
     *
     * @param string $campaign_id
     * @return array
     */
    public function getStats(string $campaign_id): array
    {
        $campaign_stats = $this->getOne($campaign_id)
            ->getStats();

        $stats = $this->transaction_service
            ->getCampaignStats($campaign_id);

        $stats['associations'] = $campaign_stats->getCurrentAssociations();
        $stats['uses'] = $campaign_stats->getCurrentTransactions();

        return $stats;
    }

    /**
     * Substitui as tags de uma campanha
     *
     * @param string $campaign_id
     * @param array $tag_ids
     */
    public function replaceTags(string $campaign_id, array $tag_ids)
    {
        $campaign = $this->getOne($campaign_id);

        $tags = $this->tag_service->getMany($tag_ids);

        $campaign->replaceTags($tags);

        DocumentManager::flush();
    }

    /**
     * @param string $campaign_id
     * @param array $data
     * @return Campaign
     */
    public function applyPermissions(string $campaign_id, array $data)
    {
        $campaign = $this->getOne($campaign_id);

        $campaign->setPermissions($data);

        DocumentManager::flush();

        return $campaign;
    }

    /**
     * Publica uma campanha no Slack
     *
     * @param string $campaign_id
     * @return void
     */
    public function publishOnSlack(string $campaignId)
    {
        //todo $this->getOne() retorna 404. Trocar chamada abaixo aós
        $campaign = $this->repository->getOne($campaignId);

        if (!$campaign) {
            throw new PromoException('Campanha não encontrada!');
        }

        $result = $this->formatCampaignText($campaign);

        $channel = config('app.promos-slack-channel');
        $client = new SlackClient($channel, 'Promo Alerta!', ':picpay:');
        $client->send($result);

        return;
    }

    /**
     * Formata o texto seguindo padrão de promos
     *
     * @param Campaign $campaign
     * @return string
     */
    private function formatCampaignText(Campaign $campaign): string
    {
        //todo Persistir texto e realizar substituição dos valores
        $name = $campaign->getName();
        $descricao = $campaign->getDescription();
        $id = $campaign->getId();

        switch ($campaign->getTransactionDetails()->getType()) {
            case TransactionTypeEnum::P2P:
                $objeto = 'P2P';
                break;
            case TransactionTypeEnum::PAV :
                $objeto = 'PAV';
                break;
            default:
                $objeto = 'P2P e PAV';
                break;
        }

        $cashbackCeiling = $campaign->getCashbackDetails()->getCeiling();
        $cashbackPercent = $campaign->getCashbackDetails()->getCashback();

        if ($cashbackPercent == 100) {
            $valorCashback = "*Até R\${$cashbackCeiling}*";
        } else {
            $valorCashback = "*{$cashbackPercent}% até R\${$cashbackCeiling}*";
        }

        $metodoPagamentoList = $campaign->getTransactionDetails()->getPaymentMethods();
        $limiteUso = $campaign->getTransactionDetails()->getMaxTransactions();
        $limiteUsoConsumer = $campaign->getTransactionDetails()->getMaxTransactionsPerConsumer();
        $limiteUsoConsumerDia = $campaign->getTransactionDetails()->getMaxTransactionsPerConsumerPerDay();
        $endDate = $campaign->getDurationDetails()->getEndDate();
        $endHours = $campaign->getDurationDetails()->getHours();
        $endDays = $campaign->getDurationDetails()->getDays();
        $associacao = $campaign->isGlobal() ? 'Global' : 'Exclusiva';
        $limiteTotal = $cashbackCeiling * $limiteUso;
        $limiteDiario = $limiteUsoConsumerDia * $cashbackCeiling;

        $webhookService = new WebhookService();
        $webhooks = $webhookService->getAll(['campaign_id' => $id]);

        if ($webhooks) {
            $campaign->setWebhook($webhooks);
        }

        $webhookVariants = "";
        $pushTitle = "";
        $pushMessage = "";
        $smsMessage = "";
        $inAppMessage = "";
        $webViewUrl = "";

        foreach ($webhooks as $row) {
            foreach ($row->getVariants() as $variant) {
                $values = $variant->getVariants();

                if (isset($values['push']['title'])) {
                    $pushTitle = "```{$values['push']['title']}```";
                }

                if (isset($values['push']['message'])) {
                    $pushMessage = "```{$values['push']['message']}```";
                }

                if (isset($values['sms']['message'])) {
                    $smsMessage = "```{$values['sms']['message']}```";
                }

                if (isset($values['in_app']['message'])) {
                    $inAppMessage = "```{$values['in_app']['message']}```";
                }

                if (isset($values['target']['href'])) {
                    $webViewUrl = "{$values['target']['href']}";
                }

                $webhookVariants .= "

                    _Push Título:_ {$pushTitle} \n
                    Push Corpo de Texto: {$pushMessage} \n
                    _SMS:_ {$smsMessage} \n
                    Notificação In-App: {$inAppMessage} \n
                   Visualização In-App:
                   {$webViewUrl} \n
                ";
            }
        }

        $webhookVariants = str_replace('        ', '', $webhookVariants);

        if ($endDate) {
            $date = "Até {$endDate->format('d/m/Y')} às {$endDate->format('h:m')}";
        } else {
            $date = "{$endDays} dias e {$endHours} horas após o recebimento";
        }

        $saldo = in_array('wallet', $metodoPagamentoList);
        $cartao = in_array('credit_card', $metodoPagamentoList);

        if ($saldo && $cartao) {
            $metodoPagamento = "Cartão de Crédito e Saldo";
        } elseif ($saldo) {
            $metodoPagamento = "Saldo";
        } else {
            $metodoPagamento = "Cartão de Crédito";
        }

        $texto = "
            _Nome:_
            ```{$name}```
            _Descrição:_
            ```{$descricao}```
            _ID:_
            `{$id}`
            _Objeto(s):_
            `{$objeto}`
            _________________________________
            
            _Valor de Cashback:_
            {$valorCashback}
            
            _Método de Pagamento:_
            *{$metodoPagamento}*.
            
            _Limite de Uso:_
            - *{$limiteUsoConsumer} uso(s)*.
            - *Valor total em transações não superior a R\${$limiteTotal}*.
            
            _Limite de período:_
            - *{$limiteUsoConsumerDia} uso(s) / dia*.
            - R\${$limiteDiario} de valor transacionado por dia.
            
            _Duração:_
            - *{$date}*
            
            _Associação:_
            - *{$associacao}*
            
            _________________________________
            {$webhookVariants}
        ";

        $texto = str_replace('           ', '', $texto);

        return $texto;
    }

    public function notificationTemplateRuler(string $campaign_id, Campaign $campaign = null){

        if(empty($campaign)){
            $campaign = $this->repository->getOne($campaign_id);

            if(!$campaign){
                throw new NotFoundHttpException('Campanha não encontrada');
            }
        }

        $data = [
            "campaign.name" => $campaign->getName()
        ];

        if(!empty($campaign->getDurationDetails())){
            if($campaign->getDurationDetails()->isFixed()){
                $data["campaign.duration.start_date"] = $campaign->getDurationDetails()->getStartDate()->format('Y-m-d H:i:s');
                $data["campaign.duration.end_date"] = $campaign->getDurationDetails()->getEndDate()->format('Y-m-d H:i:s');
            }
        }

        if(!empty($campaign->getCashbackDetails())){
            $data["campaign.cashback.percentage"] = $campaign->getCashbackDetails()->getCashback();
            $data["campaign.cashback.max_value"] = $campaign->getCashbackDetails()->getCeiling();
        }

        if(!empty($campaign->getTransactionDetails())){
            $data["campaign.transaction.type"] = $campaign->getTransactionDetails()->getType();
            $data["campaign.transaction.min_transaction_value"] = $campaign->getTransactionDetails()->getMinTransactionValue();
            $data["campaign.transaction.max_transactions"] = $campaign->getTransactionDetails()->getMaxTransactions();
            $data["campaign.transaction.max_transactions_per_consumer"] = $campaign->getTransactionDetails()->getMaxTransactionsPerConsumer();
            $data["campaign.transaction.max_transactions_per_consumer_per_day"] = $campaign->getTransactionDetails()->getMaxTransactionsPerConsumerPerDay();
            $data["campaign.transaction.first_payment_to_seller"] = $campaign->getTransactionDetails()->isFirstPaymentToSeller();
            $data["campaign.transaction.first_payee_received_payment_only"] = $campaign->getTransactionDetails()->isFirstPayeeReceivedPaymentOnly();
            $data["campaign.transaction.payment_methods"] = $campaign->getTransactionDetails()->getPaymentMethods();
        }

        if(!empty($campaign->getLimits())){

            if(!empty($campaign->getLimits()->getUsesPerConsumer())){
                $data["campaign.limits.uses_per_consumer.type"] = $campaign->getLimits()->getUsesPerConsumer()->getType();
                $data["campaign.limits.uses_per_consumer.uses"] = $campaign->getLimits()->getUsesPerConsumer()->getUses();
            }

            if(!empty($campaign->getLimits()->getUsesPerConsumerPerPeriod())){
                $data["campaign.limits.uses_per_consumer_per_period.type"] = $campaign->getLimits()->getUsesPerConsumerPerPeriod()->getType();
                $data["campaign.limits.uses_per_consumer_per_period.period"] = $campaign->getLimits()->getUsesPerConsumerPerPeriod()->getPeriod();
                $data["campaign.limits.uses_per_consumer_per_period.uses"] = $campaign->getLimits()->getUsesPerConsumerPerPeriod()->getUses();
            }
        }

        $collection = collect($data);
        $collection = $collection->filter(function($value, $key){
            return $value != null;
        });

        return $collection;
    }

    /**
     * @param array $sellers
     * @param $campaignId
     * @return array|bool
     * @throws \Exception
     */
    public function checkSellers(array $sellers, $campaignId)
    {
        $campaign = $this->repository->find($campaignId);

        if (!$campaign instanceof Campaign) {
           throw new \Exception("Campanha $campaignId inexistente");
        }

        $notInCampaign = [];

        foreach ($sellers as $sellerId) {
            if (!in_array($sellerId, $campaign->getSellers())) {
                $notInCampaign[] = $sellerId;
            }
        }

        return empty($notInCampaign) ? true : $notInCampaign;
    }

    /**
     * @param int $sellerId
     * @param int $limit
     * @param int $skip
     * @return \Illuminate\Support\Collection
     */
    public function getBySeller(int $sellerId, int $limit, int $skip)
    {
        $criteria = [
            'sellers' => $sellerId,
            'communication' => false,
            'active' => true,
        ];

        $campaigns = $this->getAll($criteria,[],$limit,$skip);

        return collect($campaigns);
    }

}
