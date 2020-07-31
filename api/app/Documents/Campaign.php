<?php

namespace Promo\Documents;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Promo\Documents\Embeded\AppDetails;
use Promo\Documents\Embeded\CampaignPermissions;
use Promo\Events\Campaign\CampaignChangeEvent;
use Promo\Exceptions\ValidationException;
use Promo\Documents\Embeded\CampaignStats;
use Promo\Documents\Enums\CampaignTypeEnum;
use Promo\Documents\Embeded\DepositDetails;
use Promo\Documents\Embeded\CampaignLimits;
use Promo\Documents\Embeded\CashbackDetails;
use Promo\Documents\Embeded\DurationDetails;
use Promo\Documents\Embeded\ExternalMerchant;
use Promo\Documents\Embeded\CashfrontDetails;
use Promo\Documents\Embeded\TransactionDetails;
use Promo\Documents\Embeded\InstantcashDetails;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="campaigns", repositoryClass="Promo\Repositories\CampaignRepository")
 * @ODM\HasLifecycleCallbacks
 */
class Campaign extends BaseDocument
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="string") */
    protected $name;

    /** @ODM\Field(type="string") */
    protected $description;

    /**
     * @ODM\Field(type="string")
     * @ODM\Index
     */
    protected $type;

    /**
     * @ODM\Field(type="boolean")
     * @ODM\Index
     */
    protected $active;

    /**
     * @ODM\Field(type="boolean")
     * @ODM\Index
     */
    protected $global;

    /**
     * @ODM\Field(type="boolean")
     * @ODM\Index
     */
    protected $communication;

    /**
     * @ODM\Field(type="collection")
     * @ODM\Index
     */
    protected $consumers;

    /**
     * @ODM\Field(type="collection")
     * @ODM\Index
     */
    protected $sellers;

    /**
     * @ODM\Field(type="collection")
     * @ODM\Index
     */
    protected $except_sellers;

    /**
     * @ODM\Field(type="collection")
     * @ODM\Index
     */
    protected $sellers_types;

    /** @ODM\Field(type="string") */
    protected $webhook_url;

    /** @ODM\Field(type="string") */
    protected $webview_url;

    /** @ODM\ReferenceOne(targetDocument="Promo\Documents\Webhook", storeAs="id") */
    protected $webhook;

    /** @ODM\EmbedOne(targetDocument="Promo\Documents\Embeded\DurationDetails") */
    protected $duration;

    /** @ODM\EmbedOne(targetDocument="Promo\Documents\Embeded\CashbackDetails") */
    protected $cashback;

    /** @ODM\EmbedOne(targetDocument="Promo\Documents\Embeded\AppDetails") */
    protected $app;

    /** @ODM\EmbedOne(targetDocument="Promo\Documents\Embeded\CashfrontDetails") */
    protected $cashfront;

    /** @ODM\EmbedOne(targetDocument="Promo\Documents\Embeded\InstantcashDetails") */
    protected $instantcash;

    /** @ODM\EmbedOne(targetDocument="Promo\Documents\Embeded\TransactionDetails") */
    protected $transaction;

    /** @ODM\EmbedOne(targetDocument="Promo\Documents\Embeded\DepositDetails") */
    protected $deposit;

    /** @ODM\EmbedOne(targetDocument="Promo\Documents\Embeded\ExternalMerchant") */
    protected $external_merchant;

    /** @ODM\EmbedOne(targetDocument="Promo\Documents\Embeded\CampaignLimits") */
    protected $limits;

    /** @ODM\EmbedOne(targetDocument="Promo\Documents\Embeded\CampaignStats") */
    protected $stats;

    /** @ODM\ReferenceMany(targetDocument="Promo\Documents\Tag", storeAs="id") */
    protected $tags;

    /** @ODM\EmbedOne(targetDocument="Promo\Documents\Embeded\CampaignPermissions") */
    protected $permissions;

    /** @ODM\Field(type="collection") */
    protected $versions;

    /**
     * Campaign constructor
     *
     * @param string $type
     * @throws ValidationException
     */
    public function __construct(string $type)
    {
        // As campanhas sempre iniciam ativas
        $this->active = true;

        $this->setType($type);

        // Monta o objeto de acordo com o tipo da campanha
        switch ($type) {
            case CampaignTypeEnum::CASHBACK:
                $this->cashback = new CashbackDetails();
                $this->transaction = new TransactionDetails();
                break;

            case CampaignTypeEnum::CASHFRONT:
                $this->cashfront = new CashfrontDetails();
                $this->deposit = new DepositDetails();
                break;

            case CampaignTypeEnum::INSTANTCASH:
                $this->instantcash = new InstantcashDetails();
                break;
        }

        // Inicializa documentos embedados default
        $this->duration = new DurationDetails();
        $this->stats = new CampaignStats();
        $this->app = new AppDetails();
    }

    /**
     * Obtém o valor de name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Altera o valor de name
     *
     * @param $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return bool
     */
    public function getActive()
    {
        return (bool)$this->active;
    }

    /**
     * Obtém o valor de description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Altera o valor de description
     *
     * @param $description
     * @return self
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Verifica se campanha está ativa
     *
     * E desativa campanha que já passou do prazo de validade
     *
     * @return boolean
     */
    public function isActive()
    {
        $result = ($this->isExpired() === false) && $this->active;

        if ($this->active != $result) {
            $this->active = $result;
            event(new CampaignChangeEvent($this->getId()));
        }

        $this->active = $result;

        return $result;
    }

    /**
     * Verifica se campanha é Comunicação
     *
     * Apenas para envio de comunicação - não dá cashback
     *
     * @return boolean
     */
    public function isCommunication()
    {
        if ($this->communication == null) {
            $this->setCommunication(false);
        }

        return $this->communication;
    }

    /**
     * Verifica, dentro das restrições de integridade, se a campanha
     * já expirou
     *
     * @return boolean
     */
    public function isExpired($transaction_date = '')
    {
        $now = (!empty($transaction_date) ? (new Carbon($transaction_date)) : Carbon::now());

        // Passou do limite de transações total
        if ($this->transaction !== null && $this->transaction->getMaxTransactions() !== null
            && ($this->stats->getCurrentTransactions() >= $this->transaction->getMaxTransactions())) {
            \Log::info('Campanha atingiu o número max total de transações', [
                'id' => $this->id,
            ]);

            return true;
        }

        if ($this->deposit !== null && $this->deposit->getMaxDeposits() !== null
            && ($this->stats->getCurrentTransactions() >= $this->deposit->getMaxDeposits())) {
            \Log::info('Campanha atingiu o número max total de depósitos', [
                'id' => $this->id,
            ]);

            return true;
        }

        if ($this->duration->isFixed()) {
            // Em caso de duração fixa, verifica se passou do prazo
            $end_date = $this->duration->getEndDate();

            if($end_date instanceof \DateTime) {
                $end_date = Carbon::parse($end_date->format('Y-m-d H:i:s'));
            }

            return $end_date->lt($now);
//           return ($end_date->toIso8601String() < $now->toIso8601String());
        }

        return false;
    }

    /**
     * Dado a data de associação de um usuário a campanha,
     * verifica se a campanha já expirou para ele
     *
     * Leva em consideração o prazo em horas de campanhas
     *
     * @param $user_push_date
     * @return boolean
     */
    public function isExpiredForConsumer($user_push_date, $transaction_date = '')
    {
        if ($this->duration->isFixed()) {
            return $this->isExpired($transaction_date);
        } else {
            $transaction = (!empty($transaction_date) ? (new Carbon($transaction_date)) : Carbon::now());
            $end = Carbon::instance($user_push_date)
                ->addHours($this->duration->getHours())
                ->addDay($this->duration->getDays())
                ->addWeek($this->duration->getWeeks())
                ->addMonth($this->duration->getMonths());
            $now = Carbon::instance($user_push_date);

            // Se data da transacao for menor  que data de associacao
            if($now->diffInMinutes($transaction, false) < 0) {
                return true;
            }

            return $transaction > $end;
        }
    }

    /**
     * Altera o valor de active para true
     *
     * @return self
     */
    public function enable()
    {
        $this->active = true;

        return $this;
    }

    /**
     * Altera o valor de active para false
     *
     * @return self
     */
    public function disable()
    {
        $this->active = false;

        \Log::info('Campanha desativada pelo método disable', [
            'id' => $this->id,
        ]);


        return $this;
    }

    /**
     * Altera o valor de active
     *
     * @param bool $active
     * @return self
     */
    public function setActive(bool $active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Obtém o valor de global
     *
     * @return bool
     */
    public function isGlobal()
    {
        return $this->global;
    }

    /**
     * Altera o valor de global
     *
     * @param bool $global
     * @return self
     */
    public function setGlobal(bool $global)
    {
        $this->global = $global;

        return $this;
    }

    /**
     * Altera o valor de communication
     *
     * @param bool $communication
     * @return self
     */
    public function setCommunication(bool $communication)
    {
        $this->communication = $communication;

        return $this;
    }

    /**
     * Obtém o valor de type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Altera o valor de type
     *
     * @param string $type
     * @return self
     * @throws ValidationException
     */
    public function setType(string $type)
    {
        if (!in_array($type, CampaignTypeEnum::getFields())) {
            throw new ValidationException('Tipo inesperado de campanha.');
        }

        $this->type = $type;

        return $this;
    }

    /**
     * Obtém o id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Altera o id
     *
     * Para casos de upserting
     * @param string $id
     * @return Campaign
     */
    public function setId(string $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Incrementa o contador de transações da campanha
     *
     * @return integer
     */
    public function incrementCurrentTransactions()
    {
        $transactions = $this->stats->incrementCurrentTransactions();

        if ($transactions >= $this->transaction->getMaxTransactions()) {
            $this->disable();

            \Log::info('Campanha desativada por atingir o número max de transações', [
                'id' => $this->id,
            ]);
        }

        return $transactions;
    }

    /**
     * Decrementa o contador de transações da campanha
     *
     * @return integer
     */
    public function decrementCurrentTransactions()
    {
        $transactions = $this->stats->decrementCurrentTransactions();

        return $transactions;
    }

    /**
     * Seta os consumers restritos da campanha
     *
     * @param $consumers
     * @return self
     */
    public function setConsumers(?array $consumers)
    {
        $this->consumers = $consumers;

        return $this;
    }

    /**
     * Retorna os consumers restritos
     *
     * @return array
     */
    public function getConsumers()
    {
        return $this->consumers;
    }


    /**
     * Adiciona id de consumers à lista de consumers da campanha
     *
     * @param integer $consumer_id
     * @return void
     */
    public function addConsumer(int $consumer_id)
    {
        if ($this->consumers === null) {
            $this->consumers = [];
        } else {
            if (in_array($consumer_id, $this->consumers)) {
                return;
            }
        }

        $this->consumers[] = $consumer_id;
    }


    /**
     * Seta os sellers restritos da campanha
     *
     * @param $sellers
     * @return self
     */
    public function setSellers(?array $sellers)
    {
        $this->sellers = $sellers;

        return $this;
    }

    /**
     * Retorna os sellers restritos
     *
     * @return array
     */
    public function getSellers()
    {
        return $this->sellers;
    }

    /**
     * Retorna os sellers de exceção
     *
     * @return array
     */
    public function getExceptSellers()
    {
        return $this->except_sellers;
    }

    /**
     * Adiciona id de seller à lista de sellers da campanha
     *
     * @param integer $seller_id
     * @return void
     */
    public function addSeller(int $seller_id)
    {
        if ($this->sellers === null) {
            $this->sellers = [];
        } else {
            if (in_array($seller_id, $this->sellers)) {
                return;
            }
        }

        $this->sellers[] = $seller_id;
    }

    /**
     * Adiciona id de seller à lista de sellers da campanha
     *
     * @param integer $seller_id
     * @return void
     */
    public function addExceptSeller(int $seller_id)
    {
        if ($this->except_sellers === null) {
            $this->except_sellers = [];
        } else {
            if (in_array($seller_id, $this->except_sellers)) {
                return;
            }
        }

        $this->except_sellers[] = $seller_id;
    }

    /**
     * Seta os sellers restritos da campanha
     *
     * @param $sellers
     * @return self
     */
    public function setExceptSellers(?array $sellers)
    {
        $this->except_sellers = $sellers;

        return $this;
    }

    /**
     * Obtém duration
     *
     * @return DurationDetails
     */
    public function getDurationDetails(): DurationDetails
    {
        return $this->duration;
    }

    /**
     * Obtém chashback
     *
     * @return CashbackDetails
     */
    public function getCashbackDetails(): ?CashbackDetails
    {
        return $this->cashback;
    }

    /**
     * @return AppDetails
     */
    public function getAppDetails(): ?AppDetails
    {
        if (!$this->app) {
            $this->app = new AppDetails();
        }
        return $this->app;
    }


    /**
     * Obtém detalhes de cashfront
     *
     * @return CashfrontDetails
     */
    public function getCashfrontDetails(): ?CashfrontDetails
    {
        return $this->cashfront;
    }

    /**
     * Obtém detalhes de instantcash
     *
     * @return null|InstantcashDetails
     */
    public function getInstantcashDetails(): ?InstantcashDetails
    {
        return $this->instantcash;
    }

    /**
     * Obtém transaction
     *
     * @return TransactionDetails
     */
    public function getTransactionDetails(): ?TransactionDetails
    {
        return $this->transaction;
    }

    /**
     * Obtém transaction
     *
     * @return DepositDetails
     */
    public function getDepositDetails(): ?DepositDetails
    {
        return $this->deposit;
    }

    /**
     * Obtém estatísticas
     *
     * @return CampaignStats
     */
    public function getStats(): CampaignStats
    {
        return $this->stats;
    }

    /**
     * Obtém os tipos de sellers
     *
     * @return array|null
     */
    public function getSellersTypes()
    {
        return $this->sellers_types;
    }

    /**
     * Altera os tipos de sellers que a campanha cobre
     *
     * @param $sellers_types
     * @return  self
     */
    public function setSellersTypes(?array $sellers_types)
    {
        $this->sellers_types = $sellers_types;

        return $this;
    }

    /**
     * Além de marcar como deletada, desativa
     *
     * @return void
     */
    public function delete()
    {
        parent::delete();
        $this->disable();
    }

    /**
     * Obtém external_merchant
     *
     * @return ExternalMerchant|null
     */
    public function getExternalMerchant(): ?ExternalMerchant
    {
        return $this->external_merchant;
    }

    /**
     * Altera as propriedades de external_merchant
     *
     * @param array|null $external_merchant
     * @return self
     */
    public function setExternalMerchant(?array $external_merchant)
    {
        if ($external_merchant !== null) {
            $this->external_merchant = new ExternalMerchant();

            $this->external_merchant->setType($external_merchant['type']);
            $this->external_merchant->setIds($external_merchant['ids']);
        } else {
            $this->external_merchant = null;
        }

        return $this;
    }

    /**
     * Obém o endereço do webview de condições de campanha
     *
     * @return mixed
     */
    public function getWebviewUrl()
    {
        return $this->webview_url;
    }

    /**
     * Altera o endereço de webview
     *
     * @param mixed $webview_url
     * @return Campaign
     */
    public function setWebviewUrl($webview_url)
    {
        $this->webview_url = $webview_url;

        return $this;
    }

    /**
     * Obtém URL de webhook
     *
     * @return mixed
     */
    public function getWebhookUrl()
    {
        return $this->webhook_url;
    }

    /**
     * Altera URL de webhook
     *
     * @param mixed $webhook_url
     * @return Campaign
     */
    public function setWebhookUrl($webhook_url)
    {
        $this->webhook_url = $webhook_url;

        return $this;
    }

    /**
     * Obtém Webhook
     *
     * @return mixed
     */
    public function getWebhook()
    {
        return $this->webhook;
    }

    /**
     * Altera Webhook
     *
     * @param mixed $webhook
     * @return Campaign
     */
    public function setWebhook($webhook)
    {
        $this->webhook = $webhook;

        return $this;
    }

    /**
     * @param $versions
     * @return $this
     */
    public function setVersions($versions)
    {
        $this->versions = $versions;

        return $this;
    }

    /**
     * @return array
     */
    public function getVersions()
    {
        return $this->versions;
    }

    /**
     * Recebe um array de objetos Tag
     *
     * @param array $tags
     * @return Campaign
     */
    public function replaceTags(array $tags)
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Obter as permissoes
     *
     * @return mixed
     */
    public function getPermissions(): ?CampaignPermissions
    {
        if ($this->permissions === null) {
            $this->permissions = new CampaignPermissions();
        }

        return $this->permissions;
    }

    /**
     * setar um array com as permissoes
     *
     * @param array $permissions
     * @return Campaign
     */
    public function setPermissions(?array $permissions)
    {
        if ($permissions !== null) {

            $this->permissions = new CampaignPermissions();

            if (isset($permissions['update'])) {
                $this->permissions->setUpdate($permissions['update']);
            }

            if (isset($permissions['delete'])) {
                $this->permissions->setDelete($permissions['delete']);
            }

        } else {
            $this->permissions = null;
        }

        return $this;
    }

    /**
     * Obtém tags de um documento
     *
     * @return array|null
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Obtém possíveis limits de uso de campanha
     *
     * @return null|CampaignLimits
     */
    public function getLimits(): ?CampaignLimits
    {
        return $this->limits;
    }

    /**
     * Substitui limites de campanha
     *
     * @param null|array $limits
     * @return $this
     */
    public function setLimits(?array $limits)
    {
        if ($limits === null) {
            $this->limits = null;
        } else {
            $this->limits = new CampaignLimits();
            $this->limits->setCampaignLimits($limits);
        }

        return $this;
    }

    /**
     * @ODM\PostLoad
     *
     * Chamado toda vez que o modelo é carregado do banco para a árvore da UoW
     * Migra formas antigas para novos modelos
     */
    public function postLoad()
    {
        // Não foi inclúido no postLoad do TransactionDetails porque lida com outro
        // subdocumento que está na raiz de Campaign
        if ($this->transaction !== null) {
            $limits = [];

            // Migra o método antigo de limites
            if ($this->transaction->getMaxTransactionsPerConsumer() !== null) {
                $limits['uses_per_consumer'] = [
                    'uses' => $this->transaction->getMaxTransactionsPerConsumer(),
                    'type' => 'count'
                ];

                $this->limits = new CampaignLimits();
                $this->limits->setCampaignLimits($limits);
                // $this->transaction->setMaxTransactionsPerConsumer(null);
            }

            // Migra o método de limites por período
            if ($this->transaction->getMaxTransactionsPerConsumerPerDay() !== null) {
                $limits['uses_per_consumer_per_period'] = [
                    'uses' => $this->transaction->getMaxTransactionsPerConsumerPerDay(),
                    'type' => 'count',
                    'period' => 'day'
                ];

                $this->limits = new CampaignLimits();
                $this->limits->setCampaignLimits($limits);
                // $this->transaction->setMaxTransactionsPerConsumerPerDay(null);
            }

            $this->transaction->postLoad();
        }
    }

    /**
     * @return bool
     */
    public function isGlobalSeller()
    {
        return !$this->sellers;
    }

    /**
     * @return bool
     */
    public function isConsumerAsSeller()
    {
        return !!$this->consumers;
    }


    /**
     * @inheritDoc
     */
    public function getArray()
    {
        return [
            'id' => $this->getId(),
        ];
    }
}
