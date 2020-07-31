<?php

require_once __DIR__ . '/../vendor/autoload.php';

try {
    (new Dotenv\Dotenv(__DIR__ . '/../'))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    //
}



$app = new Laravel\Lumen\Application(
    realpath(__DIR__ . '/../')
);

$app->withFacades(true, ['\PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager' => 'DocumentManager']);

$app->singleton('filesystem', function ($app) {
    return $app->loadComponent('filesystems', 'Illuminate\Filesystem\FilesystemServiceProvider', 'filesystem');
});

$app->instance(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    new Nord\Lumen\ChainedExceptionHandler\ChainedExceptionHandler(
        new Promo\Exceptions\Handler(),
        [new Nord\Lumen\NewRelic\NewRelicExceptionHandler()]
    )
);

$app->register(SwooleTW\Http\LumenServiceProvider::class);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    Promo\Console\Kernel::class
);

$app->middleware([
    Promo\Http\Middleware\Cors::class,
    Nord\Lumen\NewRelic\NewRelicMiddleware::class,
    Promo\Http\Middleware\AccessLogMiddleware::class,
]);

$app->routeMiddleware([
    'pagination' => Promo\Http\Middleware\PaginationMiddleware::class,
    'request' => Promo\Http\Middleware\RequestIdMiddleware::class,
]);

$app->register(\SwaggerLume\ServiceProvider::class);
$app->register(\Illuminate\Redis\RedisServiceProvider::class);
$app->register(Promo\Providers\AppServiceProvider::class);
$app->register(\PicPay\Common\Lumen\Doctrine\ODM\Providers\DocumentServiceProvider::class);
$app->register(Promo\Providers\FormRequestServiceProvider::class);
$app->register(Nord\Lumen\NewRelic\NewRelicServiceProvider::class);
$app->register(Promo\Providers\EventServiceProvider::class);
// New HeathCheck
$app->register(\UKFast\HealthCheck\HealthCheckServiceProvider::class);
$app->configure('healthcheck');

$app->router->group([
    'namespace' => 'Promo\Http\Controllers',
], function ($router) {
    require __DIR__ . '/../routes/api.php';
});

// Health Check Default

$app->register(\PicPay\Brokers\BrokerProvider::class);
$app->configure('enqueue');

$app->configure('app');
$app->configure('redis');
$app->configure('cache');
$app->configure('logging');
$app->configure('database');
$app->configure('doctrine');
$app->configure('filesystems');
$app->configure('swagger-lume');
$app->configure('microservices');
$app->configure('queue');
$app->configure('client');

return $app;
