<?php

namespace Promo\Documents\Embeded;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class CouponConditions
{
    /** @ODM\Field(type="boolean") */
    protected $first_transaction_only;

    /** @ODM\Field(type="collection") */
    protected $area_codes;

    public function __construct()
    {
    }

    /**
     * Se o cupon é para usuários que nunca fizeram transação alguma
     * na plataforma
     *
     * @return bool
     */
    public function getFirstTransactionOnly(): ?bool
    {
        return $this->first_transaction_only;
    }

    /**
     * Altera o valor de primeira transação
     *
     * @param $first_transaction_only
     * @return self
     */
    public function setFirstTransactionOnly($first_transaction_only)
    {
        $this->first_transaction_only = $first_transaction_only;

        return $this;
    }

    /**
     * Seta os códigos de área (DDD) que o banner é válido
     *
     * @param array|null $codes
     * @return $this
     */
    public function setAreaCodes(?array $codes)
    {
        $this->area_codes = $codes;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAreaCodes()
    {
        return $this->area_codes;
    }
}