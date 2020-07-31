<?php

namespace Promo\Documents;

use Promo\Exceptions\ValidationException;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="tags")
 * @ODM\UniqueIndex(keys={"abbreviation"="asc", "color"="asc"})
 */
class Tag extends BaseDocument
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="string") */
    protected $name;

    /** @ODM\Field(type="string") */
    protected $abbreviation;

    /** @ODM\Field(type="string") */
    protected $color;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return Tag
     */
    public function setId(string $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return Tag
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAbbreviation(): string
    {
        return $this->abbreviation;
    }

    /**
     * @param mixed $abbreviation
     * @return Tag
     * 
     * @throws ValidationException
     */
    public function setAbbreviation(string $abbreviation)
    {
        if (strlen($abbreviation) !== 3)
        {
            throw new ValidationException('A abreviação de tag precisa de 3 caracteres.');
        }

        $this->abbreviation = $abbreviation;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getColor(): string
    {
        return $this->color;
    }

    /**
     * @param mixed $color
     * @return Tag
     */
    public function setColor(string $color)
    {
        $this->color = $color;

        return $this;
    }
}