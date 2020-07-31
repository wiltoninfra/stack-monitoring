<?php

namespace Promo\Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Modelo relacionando a instantcash dado. É usado para guardar a dados da operação
 * para verificações posteriores.
 *
 * @ODM\Document(collection="instantcashes")
 * @ODM\Index(keys={"consumer_id"="asc", "campaign"="desc"})
 */
class Instantcash extends BaseDocument
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="integer") */
    protected $consumer_id;

    /** @ODM\ReferenceOne(targetDocument="Promo\Documents\Campaign", storeAs="id") */
    protected $campaign;

    /** @ODM\Field(type="float") */
    protected $instantcash_given;

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
     * Valor de instantcash aplicado
     *
     * @param float $instantcash_given
     * @return self
     */
    public function setInstantcashGiven(float $instantcash_given)
    {
        $this->instantcash_given = $instantcash_given;

        return $this;
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
}