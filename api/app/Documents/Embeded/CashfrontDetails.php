<?php

namespace Promo\Documents\Embeded;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class CashfrontDetails
{
    /** @ODM\Field(type="float") */
    protected $percentage;

    /** @ODM\Field(type="float") */
    protected $ceiling;

    /** @ODM\Field(type="string") */
    protected $recharge_method;

    /**
     * Obtém o valor de cashback
     *
     * @return float
     */
    public function getCashfront()
    {
        return $this->percentage;
    }

    /**
     * Altera o valor de cashfront
     *
     * @param float $cashfront
     * @return self
     */
    public function setCashfront(float $cashfront): self
    {
        $this->percentage = $cashfront;

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
     * Determina o maior valor possível de cashfront
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
     * @return mixed
     */
    public function getRechargeMethod()
    {
        return $this->recharge_method;
    }

    /**
     * @param mixed $recharge_method
     */
    public function setRechargeMethod($recharge_method)
    {
        $this->recharge_method = $recharge_method;

        return $this;
    }

}