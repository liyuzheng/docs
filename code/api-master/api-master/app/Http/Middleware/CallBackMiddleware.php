<?php

namespace App\Http\Middleware;

use App\Constant\ApiBusinessCode;
use App\Models\User;
use Closure;

class CallBackMiddleware
{
    /**
     * @param           $request
     * @param  Closure  $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $header = $next($request);
        $header->headers->set('Route-Name', $this->mapRoute($request->path()));
        $header->headers->set('Request-Date', date('Y-m-d-H:00:00', time()));

        return $header;
    }

    /**匹配路由
     *
     * @param $route
     *
     * @return mixed|string
     */
    protected function mapRoute($route)
    {
        $routesMap = config('routes_map');
        $route     = ltrim($route, '/');
        preg_match_all('/\/\d{1,}/', $route, $match);
        $replaces = $match[0] ?? [];
        foreach ($replaces as $replace) {
            $route = str_replace($replace, "/*", $route);
        }

        return $routesMap[$route] ?? "all.null";
    }
}
