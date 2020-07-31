<?php

namespace Promo\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    /**
     * Handle an incoming request.
     * @codeCoverageIgnore
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $headers = [
            'Access-Control-Allow-Origin'      => '*',
            'Access-Control-Allow-Methods'     => 'POST, GET, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age'           => '86400',
            'Access-Control-Allow-Headers'     => 'Content-Type, Authorization, X-Page-Size',
            'Access-Control-Expose-Headers'    => 'X-Total-Count, X-Total-Active'
        ];

        if ($request->isMethod('OPTIONS'))
        {
            return response()->json(['method' => 'OPTIONS'], 200, $headers);
        }

        $response = $next($request);

        foreach ($headers as $key => $value)
        {
            $response->header($key, $value);
        }

        return $response;
    }
}
