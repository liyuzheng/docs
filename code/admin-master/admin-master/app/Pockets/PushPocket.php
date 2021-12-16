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

}
