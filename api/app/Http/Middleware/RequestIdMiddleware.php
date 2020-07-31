<?php

namespace Promo\Http\Middleware;

use Promo\Logging\RequestId;
use Closure;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestIdMiddleware
{
    /**
     * @var RequestId
     */
    private $requestId;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * LoggerMiddleware constructor.
     * @param RequestId $requestId
     * @param LoggerInterface $logger
     */
    public function __construct(RequestId $requestId, LoggerInterface $logger)
    {
        $this->requestId = $requestId;
        $this->logger = $logger;
    }

    /**
     * Seta o request_id no header de resposta e armazena os logs de requisição e resposta
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $this->logRequest($request);
        $response = $next($request);
        $this->logResponse($response);

        $response->headers->set(RequestId::HEADER_PRIMARY, $this->requestId->get());

        return $response;
    }

    /**
     * Log da requisição
     * @param Request $request
     */
    private function logRequest(Request $request): void
    {
        $requestId = $request->headers->get(RequestId::HEADER_PRIMARY) ?? $this->requestId->get();

        $context = [
            'url' => $request->url(),
            'request' => $request->all(),
            'method' => $request->getMethod(),
            'headers' => $request->headers->all(),
            'extra' => ['request-id'=> $requestId]
        ];

        if ($request->isJson()) {
            $context['request'] = $request->json()->all();
        }

        $this->logger->info('log-request-data', $context);
    }

    /**
     * Log da resposta
     * @param Response $response
     */
    private function logResponse(Response $response): void
    {
        $context = [
            'response' => json_decode($response->getContent(), true),
            'status' => $response->getStatusCode(),
            'extra' => ['headers-response'=> $response->headers->all()]
        ];
        $this->logger->info('log-response-data', $context);
    }
}
