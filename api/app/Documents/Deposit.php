<?php

namespace Promo\Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="deposits", repositoryClass="Promo\Repositories\DepositRepository")
 * @ODM\Index(keys={"consumer_id"="asc", "campaign"="desc"})
 */
class Deposit extends BaseDocument
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="integer") */
    protected $consumer_id;

    /** @ODM\ReferenceOne(targetDocument="Promo\Documents\Campaign", storeAs="id") */
    protected $campaign;

    /** @ODM\Field(type="float") */
    protected $deposit_value;

    /** @ODM\Field(type="float") */
    protected $cashfront_given;

    /** @ODM\Field(type="hash") */
    protected $details;

    public function __construct(int $consumer_id)
    {
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
     * Retorna valor do depósito
     *
     * @return mixed
     */
    public function getDepositValue()
    {
        return $this->deposit_value;
    }

    /**
     * Valor do depósito
     *
     * @param float $deposit_value
     * @return self
     */
    public function setDepositValue(float $deposit_value)
    {
        $this->deposit_value = $deposit_value;

        return $this;
    }

    /**
     * Valor de cashfront aplicado
     *
     * @param float $cashfront_given
     * @return self
     */
    public function setCashfrontGiven(float $cashfront_given)
    {
        $this->cashfront_given = $cashfront_given;

        return $this;
    }

    /**
     * Retorna valor do depósito
     *
     * @return mixed
     */
    public function getCashfrontGiven()
    {
        return $this->cashfront_given;
    }

    /**
     * Aponta para uma campanha
     *
     * @param Campaign $campaign
     * @return self
     */
    public function setCampaign(Campaign $campaign)
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * Insere os detalhes do depósito vindos da chamada do Core
     *
     * @param array $details
     * @return self
     */
    public function setDetails(array $details)
    {
        $this->details = $details;

        return $this;
    }
}