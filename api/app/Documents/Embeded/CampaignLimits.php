<?php

namespace Promo\Documents\Embeded;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument
 *
 * Limites de um consumer, numa campanha, por período ou geral
 * O modelo aceita qualquer valor, mas por convenção:
 *
 * Períodos aceitam `day`, `week` e `month`
 * Type aceita `sum` e `count`
 * Uses podem ser inteiro para contagem e float para valores (soma)
 */
class CampaignLimits
{
    /**
     * @ODM\EmbedOne(targetDocument="Promo\Documents\Embeded\UsesPerConsumer")
     * @var \Promo\Documents\Embeded\UsesPerConsumer
     */
    protected $uses_per_consumer;

    /**
     * @ODM\EmbedOne(targetDocument="Promo\Documents\Embeded\UsesPerConsumerPerPeriod")
     * @var \Promo\Documents\Embeded\UsesPerConsumerPerPeriod
     */
    protected $uses_per_consumer_per_period;

    /**
     * Repassa dados para os subdocumentos e seta limites
     * @param array $data
     */
    public function setCampaignLimits(?array $data)
    {
        if ($data === null || empty($data))
        {
            $this->uses_per_consumer = null;
            $this->uses_per_consumer_per_period = null;

            return;
        }

        if (array_key_exists('uses_per_consumer', $data) === true)
        {
            $this->uses_per_consumer = new UsesPerConsumer();
            $this->uses_per_consumer->setUsePerConsumerLimits($data['uses_per_consumer']);
        }

        if (array_key_exists('uses_per_consumer_per_period', $data) === true)
        {
            $this->uses_per_consumer_per_period = new UsesPerConsumerPerPeriod();
            $this->uses_per_consumer_per_period->setUsePerConsumerPerPeriodLimits($data['uses_per_consumer_per_period']);
        }
    }

    /**
     * Obtém limites de campanha, prontos para retorno de recurso
     *
     * @return array|null
     */
    public function getCampaignLimits(): ?array
    {
        $result = null;

        if ($this->uses_per_consumer !== null)
        {
            $result['uses_per_consumer'] = $this->uses_per_consumer->getUsesPerConsumerLimits();
        }

        if ($this->uses_per_consumer_per_period !== null)
        {
            $result['uses_per_consumer_per_period'] = $this->uses_per_consumer_per_period->getUsesPerConsumerPerPeriodLimits();
        }

        return $result;
    }

    /**
     * @return null|UsesPerConsumer
     */
    public function getUsesPerConsumer(): ?UsesPerConsumer
    {
        return $this->uses_per_consumer;
    }

    /**
     * @return null|UsesPerConsumerPerPeriod
     */
    public function getUsesPerConsumerPerPeriod(): ?UsesPerConsumerPerPeriod
    {
        return $this->uses_per_consumer_per_period;
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class UsesPerConsumer
{
    /** @ODM\Field(type="integer") */
    protected $uses;

    /** @ODM\Field(type="string") */
    protected $type;

    /**
     * @return mixed
     */
    public function getUses()
    {
        return $this->uses;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array|null
     */
    public function getUsesPerConsumerLimits(): ?array
    {
        return [
            'uses' => $this->uses,
            'type' => $this->type
        ];
    }

    /**
     * @param array $data
     */
    public function setUsePerConsumerLimits(array $data)
    {
        $this->uses = $data['uses'];
        $this->type = $data['type'];
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class UsesPerConsumerPerPeriod
{
    /** @ODM\Field(type="string") */
    protected $period;

    /** @ODM\Field(type="integer") */
    protected $uses;

    /** @ODM\Field(type="string") */
    protected $type;

    /**
     * @return mixed
     */
    public function getPeriod()
    {
        return $this->period;
    }

    /**
     * @return mixed
     */
    public function getUses()
    {
        return $this->uses;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array|null
     */
    public function getUsesPerConsumerPerPeriodLimits(): ?array
    {
        return [
            'period' => $this->period,
            'uses'   => $this->uses,
            'type'   => $this->type
        ];
    }

    /**
     * @param array $data
     */
    public function setUsePerConsumerPerPeriodLimits(array $data)
    {
        $this->uses = $data['uses'] ?? null;
        $this->type = $data['type'] ?? null;
        $this->period = $data['period'] ?? null;
    }
}
