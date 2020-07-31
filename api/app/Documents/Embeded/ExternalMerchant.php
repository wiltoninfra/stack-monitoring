<?php

namespace Promo\Documents\Embeded;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument
 * 
 * @ODM\Indexes({
 *    @ODM\Index(keys={"ids"="asc"}),
 *    @ODM\Index(keys={"type"="asc"})
 * })
 */
class ExternalMerchant
{
    /** @ODM\Field(type="string") */
    protected $type;

    /** @ODM\Field(type="collection") */
    protected $ids;

    /**
     * Obtém o valor de type
     */ 
    public function getType()
    {
        return $this->type;
    }

    /**
     * Altera o valor de type
     *
     * @param string $type
     * @return  self
     */
    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Obtém o valor de ids
     */ 
    public function getIds()
    {
        return $this->ids;
    }

    /**
     * Altera o valor de ids
     *
     * @param array $ids
     * @return  self
     */
    public function setIds(array $ids)
    {
        $this->ids = $ids;

        return $this;
    }
}