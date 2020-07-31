<?php

namespace Promo\Documents\Embeded;

use Promo\Documents\BaseDocument;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument
 *
 */
class BlackListedConsumerDetail extends BaseDocument
{

    /** @ODM\Field(type="string") */
    protected $origin;

    /** @ODM\Field(type="string") */
    protected $created_by;

    /** @ODM\Field(type="string") */
    protected $description;

    /**
     * Obtém o origem do bloqueio
     *
     * @return null|int
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * Origem do bloqueio
     *
     * @param $origin
     *
     */
    public function setOrigin(string $origin){
        $this->origin = $origin;
    }



    /**
     * Obtém descricao
     *
     * @return null|int
     */
    public function getDescription()
    {
        return $this->origin;
    }

    /**
     * Usuario que efetuou o bloqueio
     *
     * @param $origin
     *
     */
    public function setCreatedBy(string $created_by){
        $this->created_by = $created_by;
    }



    /**
     * Retorna usuario que efetuou o bloqueio
     *
     * @return null|int
     */
    public function getCreatedBy()
    {
        return $this->created_by;
    }

    /**
     * Altera a descricao
     *
     * @param $description
     *
     */
    public function setDescription(string $description){
        $this->description = $description;
    }


}