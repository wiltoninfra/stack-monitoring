<?php

namespace Promo\Documents\Embeded;

use Promo\Documents\Enums\PaidByEnum;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class CashbackDetails
{
    /** @ODM\Field(type="boolean") */
    protected $fixed;
    
    /** @ODM\Field(type="float") */
    protected $percentage;

    /** @ODM\Field(type="float") */
    protected $ceiling;

    /** @ODM\Field(type="string") */
    protected $paid_by;

    public function __construct() {}

    /**
     * Obtém o valor de cashback
     * 
     * @return float
     */ 
    public function getCashback()
    {
        return $this->percentage;
    }

    /**
     * Altera o valor de cashback, de acordo com o tipo
     *
     * @param float $cashback
     * @return self
     */
    public function setCashback(float $cashback): self
    {
        $this->percentage = $cashback;

        return $this;
    }

    /**
     * Obtém o maior valor possível de cashback
     */ 
    public function getCeiling()
    {
        return $this->ceiling;
    }

    /**
     * Determina o maior valor possível de cashback
     *
     * @param float $max_value
     * @return self
     */
    public function setCeiling(float $max_value): self
    {
        $this->ceiling = abs($max_value);

        return $this;
    }

    /**
     * Obtém o valor de paid_by
     * 
     * @return string
     */ 
    public function getPaidBy()
    {
        return $this->paid_by;
    }

    /**
     * Altera o valor de paid_by
     *
     * @param string $paid_by
     * @return self
     */
    public function setPaidBy(string $paid_by): self
    {
        $types = PaidByEnum::getFields();

        if (!in_array($paid_by, $types))
        {
            throw new ValidationException('Tipo paid_by inesperado.');
        }

        $this->paid_by = $paid_by;

        return $this;
    }

    /**
     * Get the value of fixed
     */ 
    public function isFixed()
    {
        return $this->fixed;
    }
}