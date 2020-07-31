<?php

namespace Promo\Documents\Embeded;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class CouponStats
{
    /** @ODM\Field(type="int", strategy="increment") */
    protected $uses;

    /** @ODM\Field(type="int", strategy="increment") */
    protected $associations;

    public function __construct()
    {
        $this->uses = 0;
        $this->associations = null;
    }

    /**
     * Obtém o total de usos do cupom
     *
     * @return integer
     */
    public function getCurrentUses(): int
    {
        return $this->uses;
    }

    /**
     * Incrementa o contador de usos de um cupom
     *
     * @return integer
     */
    public function incrementCurrentUses(): int
    {
        return $this->uses++;
    }

    /**
     * Obtém o total de associações do cupom
     *
     * @return int
     */
    public function getCurrentAssociations(): ?int
    {
        return $this->associations;
    }

    /**
     * Incrementar o contador de associações do cupom
     *
     * @return int
     */
    public function incrementCurrentAssociations(): int
    {
        // Checa se o valor é null e transforma em integer com valor 0
        if ($this->associations === null)
        {
            $this->associations = 0;
        }

        return $this->associations++;
    }

}
