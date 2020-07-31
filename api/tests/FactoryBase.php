<?php

namespace Tests;

abstract class FactoryBase
{
    private $app = null;

    public function __construct($app = null)
    {
        $this->app = $app;
    }

    public function setApp($app)
    {
        $this->app = $app;
    }

    public function getApp()
    {
        return $this->app;
    }

    public function fillData($original, $newer)
    {

        foreach ($newer as $key => $val)
        {
            $original[$key] = $val;
        }

        return $original;
    }
}