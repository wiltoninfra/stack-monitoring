<?php

namespace Promo\Logging;

use Illuminate\Http\Request;

/**
 * Classe para tratar o request_id
 * @package App\Services
 */
class RequestId
{
    /**
     * Header principal de request_id
     * @var string
     */
    const HEADER_PRIMARY = 'x-request-id';

    /**
     * Header secundário de request_id
     * @var string
     */
    const HEADER_SECONDARY = 'x-amzn-requestid';

    /**
     * @var string
     */
    private static $requestId;

    /**
     * RequestIdService constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        if (!static::$requestId) {
            static::$requestId = $this->handle($request);
        }
    }

    /**
     * Pega o request ID dos headers, caso não exista, cria um
     * @param Request $request
     * @return string
     */
    private function handle(Request $request): string
    {
        $headers = $request->headers;
        if ($headers->has(static::HEADER_PRIMARY)) {
            return $headers->get(static::HEADER_PRIMARY);
        }

        if ($headers->has(static::HEADER_SECONDARY)) {
            return $headers->get(static::HEADER_SECONDARY);
        }

        return hash('sha256', uniqid(rand()));
    }

    /**
     * Get Request ID
     * @return string
     */
    public function get(): string
    {
        return static::$requestId;
    }
}
