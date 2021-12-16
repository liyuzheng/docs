<?php


namespace App\Http\Middleware\Adapter;


use Closure;
use Illuminate\Http\JsonResponse;

class AndroidIncomeDecimalAdapter
{

    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (user_agent()->os == 'android' && version_compare(user_agent()->clientVersion, '1.6.0', '<')) {
            if ($response instanceof JsonResponse) {
                $content = json_decode($response->getContent(), true);
                if (isset($content['data']) && isset($content['data']['income'])) {
                    $content['data']['income'] = intval($content['data']['income']);
                    $response->setContent(json_encode($content));
                }
            }
        }

        return $response;
    }
}
