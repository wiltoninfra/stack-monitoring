<?php

namespace Promo\Exceptions;

use Exception;
use PicPay\Common\Laravel\Exceptions\PicPayHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @SWG\Definition(
 *     definition="Error",
 *     description="",
 *     type="object",
 *     @SWG\Property(property="error", type="string"),
 *     @SWG\Property(property="code", type="integer")
 * ),
 * @SWG\Definition(
 *     definition="ValidationError",
 *     type="object",
 *     @SWG\Property(property="attribute", type="array", @SWG\Items(type="string")),
 *     description="Returns an object, where each \*attribute\* is an array of errors of this attribute"
 * )
 */
class Handler extends PicPayHandler
{
    /**
     * Here you can set exceptions class that shoudnt be reported  or shoundt be send to Slack
     */
    public function __construct () {
        // A list of the exception types that should not be reported.
        $this->dontReport([
        ]);

        // A list of the exception types that should not be reported to Slack.
        $this->dontReportSlack([
            HttpException::class,
            InvalidCouponException::class,
            InvalidCampaignException::class
        ]);
    }

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        return parent::render($request, $exception);
    }
}
