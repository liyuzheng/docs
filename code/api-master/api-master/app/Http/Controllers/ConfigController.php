<?php

namespace App\Http\Controllers;

use App\Constant\ApiBusinessCode;
use App\Http\Requests\Config\BanExactWorldRequest;
use App\Models\Config;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Version;

class ConfigController extends BaseController
{
    /**
     * 获取全局配置
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function global(Request $request)
    {
        $clientVersion = user_agent()->clientVersion;
        $appName       = user_agent()->appName;
        $query         = rep()->config->getQuery()->select('key', 'value', 'type')
            ->where('show_type', Config::SHOW_TYPE_UNIVERSAL);
        $commonQuery   = clone $query;

        $configs       = $query->where('appname', $appName)->get();
        $commonConfigs = $commonQuery->where('appname', Config::COMMON_APP_NAME)
            ->when($configs->count(), function ($query) use ($configs) {
                $query->whereNotIn('key', $configs->pluck('key')->toArray());
            })->get();

        $configs = $commonConfigs->concat($configs);
        $data    = [];
        foreach ($configs as $config) {
            if ($config->type == Config::TYPE_JSON) {
                $data[$config['key']] = json_decode($config['value'], true);
            } else {
                $data[$config['key']] = $config['value'];
            }
            if ($config['key'] === 'hide_set_enable' && version_compare($clientVersion, '1.8.0', '<')) {
                $data[$config['key']] = '0';
            }
        }
        $jpushConfigiRep       = pocket()->config->getJPushConfigByAppName($appName);
        $data['jpush_app_key'] = $jpushConfigiRep->getStatus() ? ($jpushConfigiRep->getData()['key']) : '';
        if (isset($data['chat_allow_jump_url'])) {
            $data['chat_allow_jump_url'] = explode(',', $data['chat_allow_jump_url']);
        }

        return api_rr()->getOK($data);
    }

    /**
     * 获取身高
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function height(Request $request)
    {
        return api_rr()->getOK(config('custom.config.height'));
    }

    /**
     * 获取体重
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function weight(Request $request)
    {
        return api_rr()->getOK(config('custom.config.weight'));
    }

    /**
     * 获取职业
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function job(Request $request)
    {
        $userId = $this->getAuthUserId();
        $gender = rep()->user->getById($userId)->gender;
        $jobs   = rep()->job->m()->select(['uuid', 'name'])->where('gender', $gender)->get();

        return api_rr()->getOK($jobs);
    }

    /**
     * 非强制更新检查接口
     *
     * @return JsonResponse
     */
    public function update()
    {
        $latestVersion = '2.4.0';
        $needUpdate    = false;
        $message       = '附近新入超多魅力女生，还有更多福利功能立即更新不要错过！';
//        $os            = user_agent()->os;
//        if (version_compare(user_agent()->clientVersion, $latestVersion, '<')) {
//            $user = $this->getAuthUserByHeaderAuthToken();
//            if ($os == 'ios') {
//                $message    = '附近新入超多魅力女生，还有更多福利功能立即更新不要错过！';
//                //只给指定用户发
//                if ($user) {
//                    if ($user->getOriginal('gender') === User::GENDER_MAN) {
//                        $needUpdate = true;
//                        $message = '附近新入超多魅力女生，还有更多福利功能立即更新不要错过！';
//                    }
//                }
//            }
//        }

        return api_rr()->getOK([
            'need_update'    => $needUpdate,
            'latest_version' => $latestVersion,
            'redirect_url'   => pocket()->config->getLatestIosUrl(),
            'jump_btn'       => trans('messages.upgrade_button'),
        ], $message);
    }

    /**
     * 判断审核版本
     * @return JsonResponse
     */
    public function orange()
    {
        $version = request('version');
        $appName = user_agent()->appName;
        $data    = ['status' => 1];
        $group   = [
            ['1.1', 'com.FuyaBusiness.you'],
            ['1.1', 'com.jiequ.jiequkong'],
            ['1.1', 'com.ljjz.Rock']
        ];
        //        $group   = rep()->version->m()
        //            ->where('audited_at', 0)
        //            ->where('os', Version::OS_IOS)
        //            ->pluck('bundle_id', 'version')
        //            ->toArray();
        if (in_array([$version, $appName], $group, true)) {
            $data = ['status' => 0];
        }

        return api_rr()->getOK($data, 'success');
    }

    /**
     * 违禁词
     *
     * @return JsonResponse
     */
    public function banWorld()
    {
        $os        = user_agent()->os;
        $pointWord = $banWord = [];
        if (strtolower($os) == 'ios') {
            $pointWord = rep()->spamWord->m()->pluck('word')->toArray();
        }
        foreach ($pointWord as $item) {
            $banWord[] = ['word' => $item, 'regex_type' => '1'];
        }
        $word = [
            'ban_word'  => $banWord,
            'ban_regex' => [
                [
                    'regex_type' => 1,
                    'regex'      => '(.{0,5})',
                ]
            ],
            'regex'     => []
        ];

        return api_rr()->getOK($word);
    }

    /**
     * 获得ban的精确的词
     *
     * @param  BanExactWorldRequest  $request
     *
     * @return JsonResponse
     */
    public function banExactWorld(BanExactWorldRequest $request)
    {
        $version    = $request->get('version');
        $addWord    = rep()->spamWord->m()
            ->where('version', '>', $version)
            ->pluck('word')->toArray();
        $delWord    = rep()->spamWord->m()
            ->withTrashed()
            ->where('deleted_at', '>', 0)
            ->where('version', '<=', $version)
            ->pluck('word')->toArray();
        $latestWord = rep()->spamWord->m()->orderBy('id', 'desc')->first();

        return api_rr()->getOK([
            'version' => $latestWord->version,
            'add'     => $addWord,
            'delete'  => $delWord
        ]);
    }
}
