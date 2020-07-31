<?php

namespace Promo\Documents\Embeded;

use Promo\Exceptions\ValidationException;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class DepositDetails
{
    /** @ODM\Field(type="integer") */
    protected $max_deposits;

    /** @ODM\Field(type="integer") */
    protected $max_deposits_per_consumer;

    /** @ODM\Field(type="integer") */
    protected $max_deposits_per_consumer_per_day;

    /** @ODM\Field(type="float") */
    protected $min_deposit_value;

    /** @ODM\Field(type="boolean") */
    protected $first_deposit_only;

    public function __construct()
    {
        $this->min_deposit_value = 0;
    }

    /**
     * Obtém o máximo de transações de uma campanha
     *
     * @return null|int
     */
    public function getMaxDeposits()
    {
        return $this->max_deposits;
    }

    /**
     * Altera o máximo de transações de uma campanha
     *
     * @param $value
     * @return self
     */
    public function setMaxDeposits(?int $value): self
    {
        $this->max_deposits = $value;

        return $this;
    }

    /**
     * Obtém o máximo de transações por consumer
     * para esta campanha
     *
     * @return mixed
     */
    public function getMaxDepositsPerConsumer()
    {
        return $this->max_deposits_per_consumer;
    }

    /**
     * Altera o máximo de transações de um consumer, para esta campanha
     * antes de desativá-la para o consumer
     *
     * @param $value
     * @return self
     */
    public function setMaxDepositsPerConsumer(?int $value)
    {
        $this->max_deposits_per_consumer = $value;

        return $this;
    }

    /**
     * O valor mínimo que a transação precisa ter
     * para obter o cashback
     */
    public function getMinDepositValue()
    {
        if ($this->min_deposit_value === null)
        {
            return 0;
        }

        return $this->min_deposit_value;
    }

    /**
     * Altera o valor mínimo que a transação precisa ter
     * para obter o cashback
     *
     * @param $value
     * @return self
     */
    public function setMinDepositValue(?int $value)
    {
        $this->min_deposit_value = $value;

        return $this;
    }

    /**
     * Obtém o máximo de transações de um usuário por dia
     *
     * @return mixed
     */
    public function getMaxDepositsPerConsumerPerDay()
    {
        return $this->max_deposits_per_consumer_per_day;
    }

    /**
     * Altera o máximo de transações por dia
     *
     * @param mixed $max_transactions_per_consumer_per_day
     * @return self
     *
     * @throws ValidationException
     */
    public function setMaxDepositsPerConsumerPerDay(?int $max_transactions_per_consumer_per_day)
    {
        if ($this->max_deposits_per_consumer !== null && $max_transactions_per_consumer_per_day > $this->max_deposits_per_consumer)
        {
            throw new ValidationException('A quantidade de depósitos por dia precisa ao menos ser a mesma que o max. por usuário da campanha.');
        }

        $this->max_deposits_per_consumer_per_day = $max_transactions_per_consumer_per_day;

        return $this;
    }

    /**
     * @return mixed
     */
    public function isFirstDepositOnly()
    {
        return $this->first_deposit_only;
    }

    /**
     * @param mixed $first_deposit_only
     * @return self
     */
    public function setFirstDepositOnly($first_deposit_only)
    {
        $this->first_deposit_only = $first_deposit_only;

        return $this;
    }
}