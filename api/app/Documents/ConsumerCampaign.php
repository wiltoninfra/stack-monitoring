<?php

namespace Promo\Documents;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Promo\Exceptions\ValidationException;
use Promo\Documents\Enums\AssociationTypeEnum;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="consumers_campaigns", repositoryClass="Promo\Repositories\ConsumerCampaignRepository")
 * @ODM\UniqueIndex(keys={"consumer_id"="asc", "campaign"="asc"})
 */
class ConsumerCampaign extends BaseDocument
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="integer") */
    protected $consumer_id;

    /** @ODM\ReferenceOne(targetDocument="Promo\Documents\Campaign", storeAs="id") */
    protected $campaign;

    /** @ODM\Field(type="int", strategy="increment") */
    protected $completed_transactions;

    /**
     * @ODM\Field(type="integer")
     * @ODM\Index
     */
    protected $cancelled_by_transaction_id;

    /**
     * @ODM\Field(type="string")
     * @ODM\Index
     */
    protected $cancelled_by_transaction_type;

    /**
     * @ODM\Field(type="string")
     * @ODM\Index
     */
    protected $cancelled_by_deposit_id;

    /**
     * @ODM\Field(type="boolean")
     * @ODM\Index
     */
    protected $active;

    /**
     * @ODM\Field(type="boolean")
     * @ODM\Index
     */
    protected $campaign_active;

    /**
     * @ODM\Field(type="boolean")
     * @ODM\Index
     */
    protected $global;

    /** @ODM\Field(type="boolean") */
    protected $restarted;

    /** @ODM\Field(type="string") */
    protected $type;

    /**
     * @ODM\Field(type="string")
     */
    protected $log_discard_filter;

    /**
     * @ODM\Field(type="string")
     */
    protected $log_skip_filter;

    public function __construct(int $consumer_id, Campaign $campaign)
    {
        $this->consumer_id = $consumer_id;
        $this->campaign = $campaign;
        $this->completed_transactions = 0;
        $this->active = true;
        $this->campaign_active = true;
        // Valor default: por padrão são geradas no Mixpanel
        $this->type = AssociationTypeEnum::SEGMENTATION;
        $this->global = $campaign->isGlobal();
    }

    /**
     * Obtém a campanha
     *
     * @return Campaign
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * Aponta para uma campanha
     *
     * @param Campaign $campaign
     * @return void
     */
    public function setCampaign(Campaign $campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * Obtém a quantidade de transações completas do usuário
     * naquela campanha
     *
     * @return integer
     */
    public function getCompletedTransactions()
    {
        return $this->completed_transactions;
    }

    /**
     * Incrementa a quantidade de transações completas do usuário
     * naquela campanha
     *
     * @return integer
     */
    public function incrementCompletedTransactions()
    {
        return $this->completed_transactions++;
    }

    /**
     * Decrementa quantidade de transações para casos de falha
     *
     * @return integer
     */
    public function decrementCompletedTransactions()
    {
        if ($this->completed_transactions > 0)
        {
            $this->completed_transactions--;
        }

        return $this->completed_transactions;
    }

    /**
     * Reinicia todas as características de relação
     * entre usuário e campanha
     *
     * Isso permite que uma campanha seja reassociada a um usuário
     * já associado previamente
     *
     * O histórico de relação é perdido, mas é possível acompanhar as transações já
     * feitas através da coleção de transações (procurar por usuário + campanha)
     *
     * @return void
     */
    public function restartRelationship()
    {
        $this->enable();
        $this->restarted = true;

        $this->completed_transactions = 0;

        $this->cancelled_by_transaction_id = null;
        $this->cancelled_by_transaction_type = null;

        $this->created_at = Carbon::now();
    }

    /**
     * Reverte alterações de um dado reward/cashback falho
     *
     * @return void
     */
    public function revertChanges()
    {
        $this->enable();

        $this->decrementCompletedTransactions();

        $transaction_id = $this->cancelled_by_transaction_id ?? null;
        $transaction_type = $this->cancelled_by_transaction_type ?? null;

        $this->cancelled_by_transaction_id = null;
        $this->cancelled_by_transaction_type = null;

        \Log::info('Alterações de Consumer Campaign desfeitas após erro em transação', [
            'transaction_id' => $transaction_id,
            'transaction_type' => $transaction_type,
            'consumer_id' => $this->getConsumerId(),
            'changes_reverted' => true
        ]);
    }

    /**
     * Se a relação está ativa ou não
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * Ativa relação
     *
     * @return void
     */
    public function enable()
    {
        $this->active = true;
    }

    /**
     * Desativa relação
     *
     * @return void
     */
    public function disable()
    {
        $this->active = false;
    }

    /**
     * Se campanha da relação está ativa ou não
     *
     * @return bool
     */
    public function isCampaignActive()
    {
        return $this->campaign_active;
    }

    /**
     * Altera cópia do estado da campanha
     *
     * @param bool $campaign_active
     */
    public function setCampaignActive(bool $campaign_active)
    {
        $this->campaign_active = $campaign_active;
    }

    /**
     * Obtém o id da transação que cancelou a campanha
     */
    public function getCancelledByTransactionId()
    {
        return $this->cancelled_by_transaction_id;
    }

    /**
     * Obtém o tipo da transação que cancelou a campanha
     */
    public function getCancelledByTransactionType()
    {
        return $this->cancelled_by_transaction_type;
    }

    /**
     * Seta o id da transação que cancelou esta relação entre
     * campanha e usuário
     *
     * @param array $data
     * @return  self
     */
    public function setCancelledByTransaction(array $data)
    {
        $this->cancelled_by_transaction_id = $data['id'] ?? null;
        $this->cancelled_by_transaction_type = $data['type'] ?? null;

        return $this;
    }

    /**
     * Sinaliza que um depósito cancelou a relação
     *
     * @param null|string $id
     * @return  self
     */
    public function setCancelledByDeposit(?string $id)
    {
        $this->cancelled_by_deposit_id = $id;

        return $this;
    }

    /**
     * Inserir o log da justificativa de cancelamento da campanha.
     *
     * @param string $string
     * @return self
     */

    public function setLogDiscardFilter(string $string)
    {
        $this->log_discard_filter = $string;

        Log::info('discarded_campaign', [
            'context' => 'cashback',
            'status' => 'success',
            'consumer_campaign_id' => $this->id,
            'campaign'  => [
                'id' => $this->campaign->getId()
            ],
            'consumer_id' => $this->consumer_id,
            'filter' => $string
        ]);

        return $string;
    }

    /**
     * @return mixed
     */
    public function getLogDiscardedFilter()
    {
        return $this->log_discard_filter;
    }

    /**
     * Inserir o log da justificativa do pulo da campanha.
     *
     * @param string $string
     * @return string
     */

    public function setLogSkipFilter(string $string)
    {
        $this->log_skip_filter = $string;

        Log::info('skipped_campaign', [
            'context' => 'cashback',
            'status' => 'success',
            'consumer_campaign_id' => $this->id,
            'consumer_id' => $this->consumer_id,
            'filter' => $string,
            'campaign' => [
                'id'=>$this->campaign->getId()
            ]
        ]);

        return $string;
    }

    /**
     * @return mixed
     */
    public function getLogSkipFilter()
    {
        return $this->log_skip_filter;
    }

    /**
     * Obtém o consumer_id
     *
     * @return integer
     */
    public function getConsumerId()
    {
        return $this->consumer_id;
    }

    /**
     * Se está relacionado a campanha global ou nao
     *
     * @return boolean
     */
    public function isGlobal()
    {
        return $this->global;
    }

    /**
     * Altera o valor de global
     *
     * @param $global
     * @return self
     */
    public function setGlobal($global)
    {
        $this->global = $global;

        return $this;
    }

    /**
     * Verifica se campanha já foi reiniciada
     * para usuário
     *
     * @return boolean
     */
    public function isRestarted()
    {
        return $this->restarted;
    }

    /**
     * Retorna tipo da associação
     *
     * @return mixed
     */
    public function getType()
    {
        if ($this->type === null)
        {
            return AssociationTypeEnum::SEGMENTATION;
        }

        return $this->type;
    }

    /**
     * Altera o tipo da associação
     *
     * @param mixed $type
     * @throws ValidationException
     * @return ConsumerCampaign
     */
    public function setType(string $type)
    {
        if (!in_array($type, AssociationTypeEnum::getFields()))
        {
            throw new ValidationException('Tipo inesperado de associação.');
        }

        $this->type = $type;

        return $this;
    }
}
