<?php

namespace Promo\Documents\Embeded;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument
 *
 * Informações de campanhas instantcash
 */
class InstantcashDetails
{
    /** @ODM\Field(type="float") */
    protected $value;

    /**
     * @return mixed
     */
    public function getInstantcash()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setInstantcash($value): void
    {
        $this->value = $value;
    }
}