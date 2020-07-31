<?php

namespace Promo\Documents\Embeded;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class CampaignStats
{
    /** @ODM\Field(type="int", strategy="increment") */
    protected $transactions_total;

    /** @ODM\Field(type="int", strategy="increment") */
    protected $associations_total;

    public function __construct()
    {
        $this->transactions_total = 0;
        $this->associations_total = null;
    }

    /**
     * Obtém o valor de transactions_total
     *
     * @return integer
     */
    public function getCurrentTransactions(): int
    {
        return $this->transactions_total;
    }

    /**
     * Incrementa o contador de transações da campanha
     *
     * @return integer
     */
    public function incrementCurrentTransactions(): int
    {
        return $this->transactions_total++;
    }

    /**
     * Decrementa o contador de transações da campanha,
     * para casos de recuperação de falha
     *
     * @return integer
     */
    public function decrementCurrentTransactions(): int
    {
        return $this->transactions_total--;
    }

    /**
     * Obtém o valor total de associações (para o caso de campanhas não globais)
     *
     * @return integer|null
     */
    public function getCurrentAssociations(): ?int
    {
        return $this->associations_total;
    }

    /**
     * Incrementa o contador de associações da campanha
     *
     * @return integer
     */
    public function incrementCurrentAssociations(): int
    {
        // Checa se o valor é null e transforma em integer com valor 0
        if ($this->associations_total === null)
        {
            $this->associations_total = 0;
        }

        return $this->associations_total++;
    }

    /**
     * Decrementa o contador de associações da campanha
     *
     * @return integer
     */
    public function decrementCurrentAssociations(): int
    {
        // Checa se o valor é null e transforma em integer com valor 0
        if ($this->associations_total === null)
        {
            $this->associations_total = 0;
        }

        return $this->associations_total--;
    }
}
