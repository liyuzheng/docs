<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DiscountNoticeCommand extends Command
{
    protected $signature   = 'xiaoquan:discount_notice';
    protected $description = '优惠券剩余时间不足消息通知';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $hour    = app()->environment('production') ? 6 * 60 * 60 : 10 * 60;
        $crontab = 1 * 60;
        $time    = time();
        rep()->discount->m()
            ->where('deleted_at', 0)
            ->where('expired_at', '>', $time - $hour - $crontab)
            ->where('expired_at', '<', $time - $hour)
            ->where('done_at', 0)
            ->chunk(500, function ($discounts) {
                $userIds = $discounts->pluck('user_id')->toArray();
                Log::error(json_encode($userIds));
                $users = rep()->user->m()->whereIn('id', array_unique($userIds))->get();
                foreach ($users as $user) {
                    try {
                        pocket()->push->pushToUser($user, 'VIP充值立减30%优惠剩余时间不足6小时，请尽快使用~');
                    } catch (\Exception $e) {
                    }
                }
            });

    }
}
