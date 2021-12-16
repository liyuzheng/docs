<?php


namespace App\Console\Commands;

use PHPUnit\Util\Exception;
use App\Models\Good;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use JPush\Client;
use App\Models\User;
use App\Models\Card;

class DiscountMessageCommand extends Command
{
    protected $signature   = 'xiaoquan:discount_message';
    protected $description = '打折消息';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $currentNow     = time();
        $cardId         = rep()->card->m()->where('level', Card::LEVEL_FREE_VIP)->first()->id;
        $memberSubQuery = rep()->member->getQuery()->whereRaw('start_at + duration > ' . $currentNow)
            ->whereRaw('start_at + duration < ' . ($currentNow + Good::DISCOUNT_MINIMUM_SECONDS))->where('continuous',
                0)
            ->where('card_id', '!=', $cardId);

        $limit          = 500;
        $offset         = 0;
        $neTeaseSendUid = config('custom.recharge_helper_uuid');
        $template       = '【小圈】您的VIP还剩余%d天，现在续费，立打八折！';
        $query          = rep()->user->getQuery()->select('user.id', 'user.uuid', 'user.mobile', 'user.language',
            DB::raw('members.start_at + members.duration as expired_at'))
            ->join('user_detail as ud', 'ud.user_id', 'user.id')
            ->joinSub($memberSubQuery, 'members', 'user.id', 'members.user_id')
            ->where('user.gender', User::GENDER_MAN)
            ->where('ud.os', 'android')->orderBy('user.id', 'asc');

        $pushConfigs = rep()->configJpush->getQuery()->select('appname', 'key', 'secret')->get();
        $clients     = [];
        foreach ($pushConfigs as $pushConfig) {
            $clients[$pushConfig->appname] = new Client($pushConfig->key, $pushConfig->secret, null);
        }

        do {
            $tmpQuery = clone $query;
            $users    = $tmpQuery->orderBy('id')->offset($offset)->limit($limit)->get();

            foreach ($users as $user) {
                $residualDays = (int)ceil(($user->expired_at - $currentNow) / 86400);
                $message      = sprintf($template, $residualDays);

                foreach ($clients as $client) {
                    $tmpClient = $client->push()->setPlatform('all');

                    try {
                        $tmpClient->addAlias((string)$user->uuid)->iosNotification(['body' => $message])
                            ->androidNotification($message,
                                ['badge_add_num' => 1, 'badge_class' => 'com.l.peipei.modules.splash.SplashAct'])
                            ->options(['apns_production' => true])->send();
                    } catch (\Exception $e) {
                    }

                    unset($tmpClient);
                }

                try {
                    pocket()->netease->msgSendMsg($neTeaseSendUid, $user->uuid, $message);
                    pocket()->tengYu->sendDiscountMessage($user, $residualDays);
                } catch (\Exception $e) {
                    throw new Exception($e->getMessage());
                }
            }

            $offset += $users->count();
            unset($tmpQuery);
        } while ($users->count() >= $limit);
    }
}
