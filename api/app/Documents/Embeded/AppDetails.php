<?php

namespace Promo\Documents\Embeded;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class AppDetails
{
    /** @ODM\Field(type="string") */
    protected $description;

    /** @ODM\Field(type="string") */
    protected $image;

    /** @ODM\Field(type="string") */
    protected $action_type;

    /** @ODM\Field(type="string") */
    protected $action_data;

    /** @ODM\Field(type="string") */
    protected $category;

    /** @ODM\Field(type="string") */
    protected $tracking;

    /**
     * @return mixed
     */
    public function getActionType()
    {
        return $this->action_type;
    }

    /**
     * @param $action_type
     * @return $this
     */
    public function setActionType($action_type): self
    {
        $this->action_type = $action_type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getActionData()
    {
        return $this->action_data;
    }

    /**
     * @param $action_data
     * @return $this
     */
    public function setActionData($action_data): self
    {
        $this->action_data = $action_data;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param $category
     * @return $this
     */
    public function setCategory($category): self
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param $description
     * @return $this
     */
    public function setDescription($description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param $image
     * @return $this
     */
    public function setImage($image): self
    {
        $this->image = $image;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTracking()
    {
        return $this->tracking;
    }

    /**
     * @param $tacking
     * @return mixed
     */
    public function setTracking($tacking)
    {
        $this->tracking = $tacking;
        return $this;
    }

    /**
     * @return bool
     */
    public function showInApp()
    {
        return $this->category && $this->action_data && $this->action_type;
    }

}
