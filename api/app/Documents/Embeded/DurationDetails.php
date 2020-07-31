<?php

namespace Promo\Documents\Embeded;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Carbon\Carbon;

/** @ODM\EmbeddedDocument @ODM\HasLifecycleCallbacks*/
class DurationDetails
{
    /** @ODM\Field(type="boolean") */
    protected $fixed;

    /** @ODM\Field(type="date") */
    protected $start_date;

    /** @ODM\Field(type="date") */
    protected $end_date;

    /** @ODM\Field(type="integer") */
    protected $hours;

    /** @ODM\Field(type="integer") */
    protected $days;

    /** @ODM\Field(type="integer") */
    protected $weeks;

    /** @ODM\Field(type="integer") */
    protected $months;

    /** @ODM\Field(type="integer") */
    protected $hours_total;

    
    /**
     * Obtém o valor de start_date
     */ 
    public function getStartDate()
    {
        return $this->start_date;
    }

    /**
     * Método chamado na inserção
     *
     * @ODM\PrePersist
     */
    public function onPrePersist()
    {
        $this->getHoursTotal();
    }

    /**
     * Altera o valor de start_date
     *
     * @param $start_date
     * @return self
     */
    public function setStartDate($start_date): self
    {
        if ($start_date !== null)
        {
            $this->start_date = Carbon::parse($start_date);
        }
        else
        {
            $this->start_date = null;
        }

        return $this;
    }

    /**
     * Obtém o valor de end_date
     */ 
    public function getEndDate()
    {
        return $this->end_date;
    }

    /**
     * Altera o valor de end_date
     *
     * @param $end_date
     * @return self
     */
    public function setEndDate($end_date): self
    {
        if ($end_date !== null)
        {
            $this->end_date = Carbon::parse($end_date);
        }
        else
        {
            $this->end_date = null;
        }

        return $this;
    }

    /**
     * Obtém a duração em dias, a partir do recebimento do push
     */ 
    public function getDays()
    {
        return $this->days ?? 0;
    }

    /**
     * Altera a duração em dias, a partir do recebimento do push
     *
     * @param $days
     * @return  self
     */
    public function setDays($days)
    {
        $this->days = $days;

        return $this;
    }

    /**
     * Obtém a duração em horas, a partir do recebimento do push
     */
    public function getHours()
    {
        return $this->hours ?? 0;
    }

    /**
     * Altera a duração em horas, a partir do recebimento do push
     *
     * @param $hours
     * @return  self
     */
    public function setHours($hours)
    {
        $this->hours = $hours;

        return $this;
    }

    /**
     * Obtém a duração em semanas, a partir do recebimento do push
     */
    public function getWeeks()
    {
        return $this->weeks ?? 0;
    }

    /**
     * Altera a duração em semanas, a partir do recebimento do push
     *
     * @param $weeks
     * @return  self
     */
    public function setWeeks($weeks)
    {
        $this->weeks = $weeks;

        return $this;
    }

    /**
     * Obtém a duração em meses, a partir do recebimento do push
     */
    public function getMonths()
    {
        return $this->months ?? 0;
    }

    /**
     * Altera a duração em meses, a partir do recebimento do push
     *
     * @param $months
     * @return  self
     */
    public function setMonths($months)
    {
        $this->months = $months;

        return $this;
    }

    /**
     * Obtém a duração em horas da soma de todos campos, a partir do recebimento do push
     */
    public function getHoursTotal()
    {
        if (is_null($this->hours_total)){
            $this->hours_total =  $this->periodToHours();
        }

        return $this->hours_total;
    }

    /**
     * Altera a duração em horas, a partir do recebimento do push
     *
     * @param $hours
     * @return  self
     */
    public function setHoursTotal($hours)
    {
        $this->hours_total = $hours;

        return $this;
    }



    /**
     * Get the value of fixed
     */ 
    public function isFixed()
    {
        return $this->fixed;
    }

    /**
     * Set the value of fixed
     *
     * @param bool $fixed
     * @return  self
     */
    public function setFixed(bool $fixed)
    {
        $this->fixed = $fixed;

        return $this;
    }

    /**
     * @return float|int
     * Converte tudo em horas, dias (24 horas), semanas (168 horas), meses (720 horas).
     */
    private function periodToHours(){
        return $this->getHours() + (24 * $this->getDays()) + (168 * $this->getWeeks()) + (720 * $this->getMonths());
    }
}