<?php


namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class SendCharmActiveRemindCommand extends Command
{
    protected $signature   = 'xiaoquan:charm_remind';
    protected $description = '发送魅力女生七日不活跃短信';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $role     = rep()->role->m()->where('key', Role::KEY_CHARM_GIRL)->first();
        $charmIds = rep()->userRole->m()
            ->where('role_id', $role->id)
            ->get();
        $list     = rep()->user->m()
            ->where(DB::raw('UNIX_TIMESTAMP() - active_at'), '>', 86400 * 7)
            ->where(DB::raw('( UNIX_TIMESTAMP() - active_at ) % 604800'), '<', 86400)
            ->whereIn('id', $charmIds->pluck('user_id')->toArray())
            ->where('destroy_at', 0)
            ->get();
        foreach ($list as $item) {
            pocket()->tengYu->sendCharmActiveRemindMessage($item);
        }

        $list = rep()->user->m()
            ->whereBetween(DB::raw('UNIX_TIMESTAMP() - active_at'), [86400 * 7, 86400 * 8])
            ->whereIn('id', $charmIds->pluck('user_id')->toArray())
            ->where('destroy_at', 0)
            ->get();

        $userIds    = $list->pluck('id')->toArray();
        $userMongos = mongodb('user_mark')->whereIn('_id', $userIds);
        $userMongos->update(['marks.visit' => true]);
        $needInsertIds = array_diff($userIds, $userMongos->get()->pluck('_id')->toArray());
        $createMongo   = [];
        foreach ($needInsertIds as $needInsertId) {
            $createMongo[] = [
                '_id'   => $needInsertId,
                'marks' => [
                    'visit' => true
                ]
            ];
        }
        if (count($createMongo) > 0) {
            mongodb('user_mark')->insert($createMongo);
        }


        foreach ($list as $item) {
            pocket()->push->pushToUser($item, '你已经有一周未打开小圈，系统降低了你的排序减少曝光，快上线恢复吧！');
            pocket()->tengYu->sendSevenDaysActiveMessage($item);
            echo $item->mobile . '发送成功' . PHP_EOL;
        }

        $list = rep()->user->m()
            ->where(DB::raw('UNIX_TIMESTAMP() - active_at'), '>', 86400 * 30)
            ->whereIn('id', $charmIds->pluck('user_id')->toArray())
            ->where('destroy_at', 0)
            ->get();

        $redisKey = config('redis_keys.hide_users.key');
        foreach ($list as $item) {
            if ($item->hide != User::HIDE) {
                $item->update(['hide' => User::AUTO_HIDE]);
                pocket()->esUser->updateUserFieldToEs($item->id, ['hide' => User::AUTO_HIDE]);
                redis()->client()->sAdd($redisKey, $item->id);
                echo $item->id . "隐身成功" . PHP_EOL;
            }
        }
    }
}
