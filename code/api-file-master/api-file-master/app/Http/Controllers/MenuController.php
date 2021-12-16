<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserReview;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MenuController extends BaseController
{
    /**
     * 获取首页菜单
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function users(Request $request)
    {
        $clientVersion = user_agent()->clientVersion;
        $user          = $this->getAuthUser();
        $user          = rep()->user->getById($user->id);
        /**强制升级后，低版本的可删除 */
        if (version_compare($clientVersion, '1.5.0', '<')) {
            $menus = config('custom.menu.feed');
            $type  = version_compare($clientVersion, '1.2.0', '>=') ? 'list' : 'single';
            $style = $user->gender === User::GENDER_MAN ? $type : 'twice';
            foreach ($menus as &$menu) {
                $menu['style'] = $style;
            }

            if ($user->gender === User::GENDER_WOMEN) {
                $charmGril =
                    ['key' => 'charm_girl', 'name' => 'menus.charm_girl', 'action' => 0, 'style' => $type];
                $menus[]   = $charmGril;
            } else {
                $menus = array_values(collect($menus)->sortByDesc('sort')->toArray());
            }
        } else {
            $versionMenus = collect(config('custom.menu.version'))
                ->where('version', '<=', version_to_integer($clientVersion))
                ->sortByDesc('version')->first();
            if ($user->gender === User::GENDER_WOMEN) {
                $menus = $versionMenus['man'] ?? (object)[];
            } else {
                $menus = $versionMenus['women'] ?? (object)[];
            }
        }
        pocket()->account->showUser($user->id);
        foreach ($menus as $index => $menu) {
            $menu['name']  = trans($menu['name']);
            $menus[$index] = $menu;
        }

        return api_rr()->getOK($menus);
    }

    /**
     * 动态菜单
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function moments(Request $request): JsonResponse
    {
        $topicUUid     = request('topic_uuid');
        $clientVersion = user_agent()->clientVersion;
        $momentMenus   = collect(config('custom.menu.moment'))
            ->where('version', '<=', version_to_integer($clientVersion))
            ->sortByDesc('version')
            ->first();
        if ($topicUUid) {
            $menus = $momentMenus['topic'] ?? (object)[];
        } else {
            $menus = $momentMenus['moment'] ?? (object)[];
        }

        foreach ($menus as $index => $menu) {
            $menu['name']  = trans($menu['name']);
            $menus[$index] = $menu;
        }

        return api_rr()->getOK($menus);
    }


    /**
     * 首页小红点
     * @return JsonResponse
     */
    public function dots()
    {
        $newUserTime = request('new_user', 0);
        $time        = $newUserTime ? $newUserTime : request('lbs_new', 0);
        $user        = $this->getAuthUser();
        $user        = rep()->user->getById($user->id);
        $dots        = [
            'new_user' => 0,
            'lbs_new'  => 0,
        ];
        if ($time) {
            $gender = $user->gender == User::GENDER_MAN ? User::GENDER_WOMEN : User::GENDER_MAN;
            $count  = pocket()->esUser->getNewUserCount($user->id, $gender, $time);

            $dots['new_user'] = $count;
            $dots['lbs_new']  = $count;
        }

        return api_rr()->getOK($dots);
    }

    /**
     * 附近的人的菜单
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function lbsMenu(Request $request): JsonResponse
    {
        $city = request('city');
        if ($city === '附近') {
            $city = "";
        }
        $clientVersion = user_agent()->clientVersion;
        $user          = rep()->user->getById($this->getAuthUserId());
        $lbsMenus      = collect(config('custom.menu.lbs_menu'))
            ->where('version', '<=', version_to_integer($clientVersion))
            ->sortByDesc('version')
            ->first();
        if ($user->gender === User::GENDER_WOMEN) {
            $menus = $lbsMenus['woman'] ?? (object)[];
        } else {
            if ($city) {
                $menus = $lbsMenus['man']['city'] ?? (object)[];
            } else {
                if (is_odd($user->id)) {
                    $menus = $lbsMenus['man']['a'] ?? (object)[];
                } else {
                    $menus = $lbsMenus['man']['b'] ?? (object)[];
                }
            }
        }
        pocket()->account->showUser($user->id);

        foreach ($menus as $index => $menu) {
            $menu['name']  = trans($menu['name']);
            $menus[$index] = $menu;
        }

        return api_rr()->getOK($menus);
    }
}
