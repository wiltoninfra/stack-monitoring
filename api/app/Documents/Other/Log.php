<?php

namespace Promo\Documents\Other;

use Promo\Documents\BaseDocument;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="logs")
 */
class Log extends BaseDocument
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="hash") */
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Obtém os dados do log
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Altera os dados
     *
     * @param mixed $data
     * @return Log
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }
}