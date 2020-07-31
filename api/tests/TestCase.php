<?php

namespace Tests;

use Laravel\Lumen\Testing\TestCase as Base;
use PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager;
use Tests\Traits\ExceptionHandler;
use Illuminate\Http\Request;

abstract class TestCase extends Base
{
    use ExceptionHandler;
    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $uri = $app->make('config')->get('app.url', 'http://localhost');

        $components = parse_url($uri);

        $server = $_SERVER;

        if (isset($components['path'])) {
            $server = array_merge($server, [
                'SCRIPT_FILENAME' => $components['path'],
                'SCRIPT_NAME' => $components['path'],
            ]);
        }

        $app->instance('request', Request::create(
            $uri, 'GET', [], [], [], $server
        ));

        return $app;
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        DocumentManager::getSchemaManager()->dropCollections();
    }
}
