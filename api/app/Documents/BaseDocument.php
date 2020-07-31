<?php

namespace Promo\Documents;

use Carbon\Carbon;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\MappedSuperclass @ODM\HasLifecycleCallbacks */
class BaseDocument
{
    /** @ODM\Field(type="date") */
    protected $created_at;

    /** @ODM\Field(type="date") */
    protected $updated_at;

    /** @ODM\Field(type="date") */
    protected $deleted_at;

    public function setCreatedAt($date)
    {
        $this->created_at = Carbon::parse($date);
    }

    /**
     * Método chamado na inserção
     * 
     * @ODM\PrePersist
     */
    public function onPrePersist()
    {
        $this->created_at = Carbon::now();
    }

    /**
     * Método invocado em toda atualização
     * 
     * @ODM\PreUpdate
     */
    public function onPreUpdate()
    {
        $this->updated_at = Carbon::now();
    }

    /**
     * Obtém data de criação
     *
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Obtém data de atualização
     *
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Soft delete um documento
     *
     * @return void
     */
    public function delete()
    {
        $this->deleted_at = Carbon::now();
    }

    /**
     * Obtém a data de exclusão
     *
     * @return mixed
     */
    public function getDeletedAt()
    {
        return $this->deleted_at;
    }
}