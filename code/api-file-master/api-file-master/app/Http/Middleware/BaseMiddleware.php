<?php

namespace App\Http\Middleware;

use App\Constant\ApiBusinessCode;
use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class BaseMiddleware
{
    private const NOT_HEADER_OR_SIMPLIFIED_DEFAULT_LANGUAGE = 'zh';
    private const HAS_HEADER_DEFAULT_LANGUAGE               = 'en';
    private const SIMPLIFIED_IDENTIFY                       = 'hans';
    private const SUPPORT_LANGUAGES                         = ['en', 'zh', 'zh_hant_hk', 'zh_hant_tw'];

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  Closure                   $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $language = self::NOT_HEADER_OR_SIMPLIFIED_DEFAULT_LANGUAGE;
        if ($request->has('Cu-Language')) {
            $languageFormat = Str::lower($request->header('cu-language'));
            $languageArr    = explode('-', $languageFormat);
            if (count($languageArr) > 2 && $languageArr[1] == self::SIMPLIFIED_IDENTIFY) {
                $language = self::NOT_HEADER_OR_SIMPLIFIED_DEFAULT_LANGUAGE;
            } elseif (in_array($implodeLanguage = implode('_', $languageArr), self::SUPPORT_LANGUAGES)) {
                $language = $implodeLanguage;
            } else {
                $language = in_array($languageArr[0], self::SUPPORT_LANGUAGES) ? $languageArr[0]
                    : self::HAS_HEADER_DEFAULT_LANGUAGE;
            }
        }

        $request->headers->set('request-cu-language', $language);
        App::setLocale($language);
        Carbon::setLocale($language);

        // 不要删除这一行, 主要为了初始化 context 中的ua
        $appName = user_agent()->appName;
        switch (user_agent()->os) {
            case 'android':
                if (request()->headers->has('Auth-Token')) {
                    $authToken = request()->headers->get('Auth-Token');
                    $user      = pocket()->user->getUserByAuthToken($authToken, ['role', 'gender']);
                    if ($user && version_compare(user_agent()->clientVersion, '2.4.0', '<')) {
                        if ($user->gender === User::GENDER_WOMEN) {
                            $response = [
                                'code'    => ApiBusinessCode::FORCED_TO_UPDATE,
                                'message' => '最新版本上线，新增超多VIP男士、扫脸登录更安全，更多福利请务必升级！',
                                'data'    => [
                                    'latest_version' => '2.4.0',
                                    'redirect_url'   => pocket()->config->getLatestAndroidUrl(),
                                    'jump_btn'       => '去更新',
                                ]
                            ];

                            return response()->json($response, 570, []);
                        }
                    }
                }
            case 'ios':
                if (request()->headers->has('Auth-Token')) {
                    $authToken = request()->headers->get('Auth-Token');
                    $user      = pocket()->user->getUserByAuthToken($authToken, ['role', 'gender']);
                    if ($user && version_compare(user_agent()->clientVersion, '2.4.0', '<')
                        && app()->environment('production')) {
                        if ($user->gender === User::GENDER_WOMEN) {
                            $response = [
                                'code'    => ApiBusinessCode::FORCED_TO_UPDATE,
                                'message' => '升级到最新版本即可认证魅力女生，请立即升级',
                                'data'    => [
                                    'latest_version' => '2.4.0',
                                    'redirect_url'   => pocket()->config->getLatestIosUrl(),
                                    'jump_btn'       => '去更新',
                                ]
                            ];

                            return response()->json($response, 570, []);
                        }
                    }
                }
                break;
        }
        $userUUid = '';
        if (request()->headers->has('Auth-Token')) {
            $authToken = request()->headers->get('Auth-Token');
            $uuidArr   = explode('.', $authToken);
            $userUUid  = $uuidArr[0];
        }
        $header = $next($request);
        $header->headers->set('Route-Name', $this->mapRoute($request->path()));
        $header->headers->set('User-UUid', $userUUid);
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
