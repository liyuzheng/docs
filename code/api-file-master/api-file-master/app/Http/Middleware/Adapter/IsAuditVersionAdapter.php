<?php


namespace App\Http\Middleware\Adapter;


use App\Models\Version;
use Closure;
use Illuminate\Http\JsonResponse;

/**
 * ios 审核版本处理中间件
 *
 * Class IosIsAuditVersionAdapter
 * @package App\Http\Middleware\Adapter
 */
class IsAuditVersionAdapter
{

    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $clientOs = user_agent()->os;
        if ($clientOs == 'ios') {
            if ($response instanceof JsonResponse) {
                $clientVersion = user_agent()->clientVersion;
                $version       = rep()->version->getRecordByOsAndVersionAndAppName(Version::OS_MAPPING[$clientOs],
                    $clientVersion, user_agent()->appName);
                $content       = json_decode($response->getContent(), true);

                /** 预先标记当前为正式非审核版本 */
                $content['data']['qvhqcvhg'] = false;

                if ((!$version || !$version->audited_at) && $response instanceof JsonResponse) {
                    $content['data']['qvhqcvhg'] = true;
                    if (app()->environment('production')) {
                        $content['data']['gem_url']    = '';
                        $content['data']['member_url'] = '';
                    }
                }

                if (version_compare($clientVersion, '2.2.0', '>=')) {
                    $content['data']['member_url'] = '';
                }

                $response->setContent(json_encode($content));
            }
        }

        return $response;
    }
}
