<?php


namespace App\Http\Middleware;


use Illuminate\Http\Request;

class CorsMiddleware
{
    public function handle(Request $request, \Closure $next)
    {
        $response = $next($request);
//        $response->header('Access-Control-Allow-Origin',
//            'https://web-pay-test.wqdhz.com,https://panda-test.wqdhz.com,https://web-test.wqdhz.com');
//        $response->header('Access-Control-Allow-Headers',
//            'DNT,X-Mx-ReqToken,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Authorization,Access-Token,Auth-Token,Ua-Custom');
//        $response->header('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, OPTIONS');
//        $response->header('Access-Control-Allow-Credentials', 'true');
//        $response->header('Access-Control-Max-Age', 1728000);

        return $response;
    }
}
