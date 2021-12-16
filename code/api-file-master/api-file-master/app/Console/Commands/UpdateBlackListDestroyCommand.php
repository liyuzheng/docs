<?php


namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Blacklist;

class UpdateBlackListDestroyCommand extends Command
{
    protected $signature   = 'xiaoquan:update_black_list_destroy';
    protected $description = '更新当天要过期解锁的云信ID';

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $todayStart = strtotime(date('Y-m-d', time()));
        $todayEnd   = $todayStart + 86399;
        $list       = rep()->blacklist->m()
            ->whereBetween('expired_at', [$todayStart, $todayEnd])
            ->where('related_type', Blacklist::RELATED_TYPE_OVERALL)
            ->get();
        $users      = rep()->user->m()
            ->whereIn('id', $list->pluck('related_id')->toArray())
            ->get();
        $userUuids  = [];
        $userModels = [];
        foreach ($users as $user) {
            $userUuids[$user->id]  = $user->uuid;
            $userModels[$user->id] = $user;
        }
        foreach ($list as $item) {
            pocket()->common->commonQueueMoreByPocketJob(
                pocket()->netease,
                'userUnblock',
                [$userUuids[$item->related_id]],
                $item->expired_at - time()
            );
            pocket()->common->commonQueueMoreByPocketJob(
                pocket()->netease,
                'msgSendMsg',
                [config('custom.little_helper_uuid'), $userUuids[$item->related_id], '你已被解除拉黑，可以继续使用小圈App了，请注意言行，共同维护绿色、健康的平台环境。'],
                $item->expired_at - time()
            );
            pocket()->common->commonQueueMoreByPocketJob(
                pocket()->push,
                'pushToUser',
                [$userModels[$item->related_id], '你已被解除拉黑，快打开小圈App查看吧。'],
                $item->expired_at - time()
            );
            pocket()->common->commonQueueMoreByPocketJob(
                pocket()->tengYu,
                'sendUnlockBlackMessage',
                [$userModels[$item->related_id]],
                $item->expired_at - time()
            );
            echo '解锁ID：' . $userUuids[$item->related_id] . '解锁时间：' . ($item->expired_at - $todayStart) . '秒后' . PHP_EOL;
        }
    }
}
