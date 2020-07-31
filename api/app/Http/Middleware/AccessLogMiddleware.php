<?php

namespace Promo\Http\Middleware;

use Closure;
use Promo\Services\Logging\AuditLogService;
use Illuminate\Http\Request;

class AccessLogMiddleware
{
    protected $audit_log_service;

    public function __construct(AuditLogService $audition_log_service)
    {
        $this->audit_log_service = $audition_log_service;
    }

    /**
     * Loga toda requisição de criação ou modificação de dados
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Se a requisição se trata de alteração de dados e possui ID do consumer que requisita
        // Isso indica que ela veio de uma chamada do internal-gateway
        if ($request->hasHeader('X-Internal-User-ID'))
        {

            $data = [
                // Obtém o header enviado pelo internal-gateway
                'consumer_id' => $request->header('X-Internal-User-ID'),
                'ip'          => $request->getClientIp(),
                'endpoint'    => $request->getRequestUri(),
                'method'      => $request->getMethod(),
                'body'        => $request->except('q'),
            ];

            $this->audit_log_service->sendLog($data);
        }

        return $next($request);
    }
}
