<?php

namespace Promo\Documents\Embeded;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument
 */
class CampaignPermissions
{
    /** @ODM\Field(type="boolean") */
    protected $update;

    /** @ODM\Field(type="boolean") */
    protected $delete;

    /**
     * Obtem a permissao de atualizacao
     *
     * @return boolean
     */
    public function getUpdate()
    {
        return $this->update ?? true;
    }

    /**
     * Alterar a permissao de atualizacao
     *
     * @param $update
     * @return self
     */
    public function setUpdate($update)
    {
        $this->update = $update;

        return $this;
    }

    /**
     * Obtem a permissao de delete
     *
     * @return boolean
     */
    public function getDelete()
    {
        return $this->delete ?? true;
    }

    /**
     * Alterar a permissao de delete
     *
     * @param $delete
     * @return self
     */
    public function setDelete($delete)
    {
        $this->delete = $delete;

        return $this;
    }
}