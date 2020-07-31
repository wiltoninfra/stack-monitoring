<?php

namespace Promo\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PaginationMiddleware
{
    /**
     * Adapta paginação do PicPay para o modo Mongo/Doctrine
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->isMethod('post'))
        {
            return $next($request);
        }

        $page = intval($request->get('page', 0));
        $page_size = intval($request->get('page_size', 10));

        $skip = ($page <= 0) ? 0 : ($page * $page_size);

        // Traduz para método de paginação do Mongo
        $request->merge([ 'skip' => $skip, 'limit' => $page_size ]);

        return $next($request);
    }
}
