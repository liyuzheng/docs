<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\User;
use JPush\Client;
use Illuminate\Support\Facades\Log;

class PushPocket extends BasePocket
{

    /**
     * 点对点推送给用户
     *
     * @param  User    $user
     * @param  string  $content
     */
    public function pushToUser(User $user, string $content)
    {
        $options     = ['apns_production' => true];
        $pushConfigs = rep()->configJpush->getQuery()->select('appname', 'key', 'secret')->get();
        $clients     = [];
        foreach ($pushConfigs as $pushConfig) {
            $clients[$pushConfig->appname] = new Client($pushConfig->key, $pushConfig->secret, null);
        }

        foreach ($clients as $client) {
            try {
                $result = $client->push()->setPlatform('all')->addAlias((string)$user->uuid)
                    ->iosNotification(['title' => $user->nickname, 'body' => $content], ['sound' => 'default'])
                    ->androidNotification($content, [
                        'title'         => $user->nickname,
                        'badge_add_num' => 1,
                        'badge_class'   => 'com.l.peipei.modules.splash.SplashAct'
                    ])->options($options)->send();
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * 推送消息给没有推送的ios用户
     *
     * @param  string  $action
     * @param  int     $sendUserId
     * @param  int     $receiveUserId
     * @param  string  $content
     *
     * @return ResultReturn
     */
    public function pushToNotPushIosUser(string $action, int $sendUserId, int $receiveUserId, string $content)
    {
        $jpushArr = pocket()->configJPush->getIosJPushArr();
        $options  = ['apns_production' => true];
        $clients  = [];
        foreach ($jpushArr as $pushConfig) {
            $clients[$pushConfig['appname']] = new Client($pushConfig['key'], $pushConfig['secret'], null);
        }
        $users       = rep()->user->getByIds([$sendUserId, $receiveUserId]);
        $receiveUser = $users->find($receiveUserId);
        $sendUser    = $users->find($sendUserId);
        $title       = $body = '';
        switch ($action) {
            case 'chat_msg':
                $title = '聊天消息';
                $body  = $sendUser->nickname . ': ' . $content;
                break;
            default:
                break;
        }
        $errorResp = [];
        foreach ($clients as $appName => $client) {
            try {
                $client->push()->setPlatform('all')
                    ->addAlias((string)$receiveUser->uuid)
                    ->iosNotification(['title' => $title, 'body' => $body], ['sound' => 'default'])
                    ->options($options)->send();
            } catch (\Exception $e) {
                $errorResp[$appName] = [
                    'appname' => $appName,
                    'error'   => $e->getMessage(),
                ];
            }
        }

        return ResultReturn::success([
            'error' => $errorResp
        ]);
    }

}
