<?php


namespace App\Console\Commands;


use App\Models\User;
use Illuminate\Console\Command;
use JPush\Client;

class TimingPushToManUserCommand extends Command
{
    protected $signature = 'xiaoquan:timing_push_to_man_user';
    protected $description = '修复用户角色';

    public function handle()
    {
        $count     = mongodb('user')->where('charm_girl', 1)->count();
        $offset    = mt_rand(1, $count);
        $girl      = mongodb('user')->where('charm_girl', 1)->offset($offset - 1)->first();
        $images    = rep()->resource->getQuery()->where('related_id', $girl['_id'])->whereIn('related_type', [100, 101])
            ->pluck('resource.resource')->toArray();
        $randImage = cdn_url($images[array_rand($images)]);

        $currentHour = date('H');
        if ($currentHour == 21) {
            $query = rep()->user->getQuery()->where('gender', User::GENDER_MAN);
            $body  = '今日新入' . mt_rand(50, 150) . '位魅力女神，快来看看有木有喜欢的~';
            $this->batchPush(null, $body, $randImage, $query);
        } else {
            $query = rep()->user->getQuery()->leftJoin('member', 'member.user_id', 'user.id')
                ->where('user.gender', User::GENDER_MAN)->where(function ($query) {
                    /** @var \Illuminate\Database\Eloquent\Builder $query */
                    $query->whereNull('member.user_id')->orWhereRaw('member.start_at + member.duration < ' . time());
                });
            if ($currentHour == 23) {
                $body = '离得很近认识一下❤️';
                $this->batchPush(null, $body, $randImage, $query);
            } else {
                switch ($currentHour) {
                    case 9:
                        $body = '最近' . mt_rand(1, 10) . '位女生查看了你，看看她是谁';
                        $this->batchPush(null, $body, null, $query);
                        break;
                    case 12:
                        $body = '她上传了' . mt_rand(2, 6) . '张新头像';
                        $this->batchPush(null, $body, $randImage, $query);
                        break;
                    case 15:
                        $title = sprintf('距离你%.1fkm的女生喜欢了你😍', mt_rand(5, 200) / 10);
                        $body  = '马上看看她是谁';
                        $this->batchPush($title, $body, $randImage, $query);
                        break;
                    case 18:
                        $body = '附近' . mt_rand(1, 10) . '位女生今日有空~';
                        $this->batchPush(null, $body, $randImage, $query);
                        break;
                    default:
                        $this->error('不支持的时间段推送');
                }
            }
        }
    }

    /**
     * @param  string                                 $title
     * @param  string                                 $body
     * @param  string                                 $image
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function batchPush($title, $body, $image, $query)
    {
        $offset       = 0;
        $limit        = 500;
        $ios_argument = $android_argument = [];
        if ($image) {
            $android_argument['extras'] = ['thumbnail' => $image];
            $ios_argument               = array_merge($android_argument, ['mutable-content' => true]);
        }

        $options['apns_production'] = true;

        $ios_body['body'] = $body;
        if ($title) {
            $ios_body['title'] = $title;
            $android_argument  = array_merge($android_argument, ['title' => $title]);
        }
        $pushConfigs = rep()->configJpush->getQuery()->select('appname', 'key', 'secret')->get();
        $clients     = [];
        foreach ($pushConfigs as $pushConfig) {
            $clients[$pushConfig->appname] = new Client($pushConfig->key, $pushConfig->secret, null);
        }

        $android_argument['badge_add_num'] = 1;
        $android_argument['badge_class']   = 'com.l.peipei.modules.splash.SplashAct';
        do {
            $tmpQuery = clone $query;
            $uids     = $tmpQuery->orderBy('id')->offset($offset)->limit($limit)->pluck('uuid')->toArray();

            foreach ($clients as $client) {
                $tmpClient = $client->push()->setPlatform('all');

                foreach ($uids as $uid) {
                    $tmpClient->addAlias((string)$uid);
                }

                try {
                    $tmpClient->iosNotification($ios_body, $ios_argument)->androidNotification($body,
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

    /**
     * @param  string                                 $body
     * @param  string                                 $image
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \JPush\Client                          $client
     */
    public function singlePush($body, $image, $query, $client)
    {
        $offset                     = 0;
        $limit                      = 500;
        $ios_argument               = $android_argument = [];
        $options['apns_production'] = true;
        if ($image) {
            $android_argument['extras'] = ['thumbnail' => $image];
            $ios_argument               = array_merge($android_argument, ['mutable-content' => true]);
        }

        $android_argument['badge_add_num'] = 1;
        $android_argument['badge_class']   = 'com.l.peipei.modules.splash.SplashAct';
        do {
            $tmpQuery = clone $query;
            $users    = $tmpQuery->select('nickname', 'uuid')->orderBy('id')->offset($offset)->limit($limit)->get();

            foreach ($users as $user) {
                $ios_body           = ['body' => $body, 'title' => $user->nickname];
                $tmpAndroidArgument = array_merge($android_argument, ['title' => $user->nickname]);
                try {
                    $tmpClient = $client->push()->setPlatform('all');
                    $tmpClient->addAlias((string)$user->uuid)->iosNotification($ios_body, $ios_argument)
                        ->androidNotification($body, $android_argument)
                        ->options($options)->send();
                } catch (\Exception $e) {
                }
                unset($ios_body);
                unset($tmpAndroidArgument);
                unset($tmpClient);
            }

            $count  = $users->count();
            $offset += $count;

            unset($tmpQuery);
            unset($users);
        } while ($count >= $limit);
    }
}
