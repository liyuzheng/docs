<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * 填充经纬度
 * Class FixAuditUserInfoCommand
 * @package App\Console\Commands
 */
class FillLocToMysqlCommand extends Command
{
    protected $signature   = 'xiaoquan:fill_loc_to_mysql';
    protected $description = '填充用户经纬度信息';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $times       = 0;
        $startUserId = $this->ask('start_user_id');
        $endUserId   = $this->ask('end_user_id');
        rep()->userDetail->m()
            ->select(['user_id'])
            ->where('user_id', '>=', $startUserId)
            ->where('user_id', '<=', $endUserId)
            ->where('lng', 0)
            ->where('lat', 0)
            ->orderBy('user_id')
            ->chunk(500, function ($users) use (&$times) {
                $userIds = $users->pluck("user_id")->toArray();
                foreach ($userIds as $userId) {
                    $lat     = $lng = 0;
                    $tmpUser = mongodb('user')->where('_id', $userId)->first();
                    if ($tmpUser) {
                        $lng = (float)($tmpUser['location'][0] ?? 0);
                        $lat = (float)($tmpUser['location'][1] ?? 0);
                    }
                    if ($lat > 0 && $lng > 0) {
                        rep()->userDetail->m()->where('user_id', $userId)->update([
                            'lat' => (float)$lat,
                            'lng' => (float)$lng,
                        ]);
                    }

                    $this->line('user:id:' . $userId);
                }
            });
        $this->line('处理完毕!，累计' . $times);
    }
}
