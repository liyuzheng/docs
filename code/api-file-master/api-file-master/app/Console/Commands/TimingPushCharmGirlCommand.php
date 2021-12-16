<?php


namespace App\Console\Commands;


use Illuminate\Console\Command;
use JPush\Client;

class TimingPushCharmGirlCommand extends Command
{
    protected $signature = 'xiaoquan:timing_push_charm_girl';
    protected $description = '定时给魅力女生推送消息';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $currentHour = date('H');
        $message     = '';
        switch ($currentHour) {
            case 9:
                $message = '附近有人查看了你~';
                break;
            case 12:
                $message = '附近新入' . mt_rand(10, 50) . '位vip男生 快来看看吧~';
                break;
            case 15:
                $message = '多打开APP活跃，拥有更多曝光，让更多男生看到你哦~';
                break;
            case 18:
                $message = '附近多个男生对你感兴趣快来看看吧~';
                break;
            case 21:
                $message = '附近有男生发起了约会，打开看看~';
                break;
        }

        if ($message) {
            $this->push($message);
        }
    }

    public function push($body)
    {
        $query       = rep()->user->getQuery()->where('role', 'like', '%charm_girl%');
        $pushConfigs = rep()->configJpush->getQuery()->select('appname', 'key', 'secret')->get();
        $clients     = [];
        foreach ($pushConfigs as $pushConfig) {
            $clients[$pushConfig->appname] = new Client($pushConfig->key, $pushConfig->secret, null);
        }

        $options          = ['apns_production' => true];
        $android_argument = [
            'badge_add_num' => 1,
            'badge_class'   => 'com.l.peipei.modules.splash.SplashAct',
        ];
        $ios_body         = ['body' => $body];

        $offset = 0;
        $limit  = 500;

        do {
            $tmpQuery = clone $query;
            $uids     = $tmpQuery->orderBy('id')->offset($offset)->limit($limit)->pluck('uuid')->toArray();

            foreach ($clients as $client) {
                $tmpClient = $client->push()->setPlatform('all');

                foreach ($uids as $uid) {
                    $tmpClient->addAlias((string)$uid);
                }

                try {
                    $tmpClient->iosNotification($ios_body)->androidNotification($body,
                        $android_argument)->options($options)->send();
                } catch (\Exception $e) {
                }

                unset($tmpClient);
            }

            $count  = count($uids);
            $offset += $count;

            unset($tmpQuery);
            unset($uids);
        } while ($count >= $limit);
    }
}
