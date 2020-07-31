<?php

namespace Promo\Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="transactions", repositoryClass="Promo\Repositories\TransactionRepository")
*/
class Transaction extends BaseDocument
{
    /** @ODM\Id */
    protected $id;

    /**
     * @ODM\Field(type="integer")
     * @ODM\Index
     */
    protected $consumer_id;

    /**
     * @ODM\ReferenceOne(targetDocument="Promo\Documents\Campaign", storeAs="id")
     * @ODM\Index
     */
    protected $campaign;

    /**
     * @ODM\Field(type="string")
     * @ODM\Index
     */
    protected $type;

    /**
     * @ODM\Field(type="integer")
     * @ODM\Index
     */
    protected $transaction_id;

    /** @ODM\Field(type="float") */
    protected $transaction_value;

    /** @ODM\Field(type="float") */
    protected $cashback_given;

    /** @ODM\Field(type="hash") */
    protected $details;

    public const TIME_IN_MINUTES_TO_BE_RETROATIVE = 30;

    public function __construct(int $transaction_id, string $transaction_type, int $consumer_id)
    {
        $this->transaction_id = $transaction_id;
        $this->type = $transaction_type;
        $this->consumer_id = $consumer_id;
    }

    /**
     * Obtém o id
     *
     * @return string
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Obtém a campanha
     */ 
    public function getCampaign(): ?Campaign
    {
        return $this->campaign;
    }

    /**
     * Valor da transação
     *
     * @param float $transaction_value
     * @return  self
     */ 
    public function setTransactionValue(float $transaction_value)
    {
        $this->transaction_value = $transaction_value;

        return $this;
    }

    /**
     * Valor de cashback aplicado à transação
     *
     * @param float $cashback_given
     * @return  self
     */ 
    public function setCashbackGiven(float $cashback_given)
    {
        $this->cashback_given = $cashback_given;

        return $this;
    }

    /**
     * Insere os detalhes de transação vindos da chamada do Core
     *
     * @param array $details
     * @return  self
     */ 
    public function setDetails(array $details)
    {
        $this->details = $details;

        return $this;
    }

    /**
     * Aponta para uma campanha
     *
     * @param Campaign $campaign
     * @return  self
     */ 
    public function setCampaign(Campaign $campaign)
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * Obtém os detalhes de transação enviados pelo
     * Core
     *
     * @return array
     */ 
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * Obtém o valor do tipo da transação
     *
     * @return string
     */ 
    public function getTransactionType()
    {
        return $this->type;
    }

    /**
     * Obtém o id da transação
     *
     * @return int
     */ 
    public function getTransactionId()
    {
        return $this->transaction_id;
    }

    /**
     * Obtém dados de cashback dado
     *
     * @return mixed
     */
    public function getCashbackGiven()
    {
        return $this->cashback_given;
    }

    /**
     * Obtém o valor total pago da transação
     *
     * @return mixed
     */
    public function getTransactionValue()
    {
        return $this->transaction_value;
    }
}