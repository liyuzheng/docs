<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class XssMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure                  $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $getParams  = $request->query();
        $postParams = $request->post();
        $this->filterXss($getParams);
        $this->filterXss($postParams);

        $request->query->replace($getParams);
        $request->merge($postParams);

        return $next($request);
    }

    private function filterXss(&$params)
    {
        array_walk_recursive($params, function (&$input) {
            if (is_array($input)) {
                $input = $this->filterXss($input);
            } elseif (is_string($input)) {
                $input = strip_tags($input);
            }
        });

        return $params;
    }
}
