<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * 填充历史用户坐标
 * Class FillCoordinateCommand
 * @package App\Console\Commands
 */
class FillCoordinateCommand extends Command
{
    protected $signature   = 'xiaoquan:fill_coordinate';
    protected $description = '填充历史用户坐标';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        /**
         * 执行脚本后需要执行如下命令
         *
         * db.user.ensureIndex({location:"2dsphere"});
         */
        $users = rep()->user->m()->select(['id', 'gender', 'role'])->get();
        foreach ($users as $user) {
            $userLoc = mongodb('user')->where('_id', $user->id)->first();
            if ($userLoc && isset($userLoc['location'])) {
                $lng = $userLoc['location'][0] ?? 0;
                $lat = $userLoc['location'][1] ?? 0;
                $this->line('user_id-'.$user->id.'$lng'.$lng.'-$lat'.$lat);
                if ($lng != 0 && $lat != 0) {
                    $cityName = pocket()->userDetail->getCityByLoc($lng, $lat);
                    if ($cityName) {
                        $this->line($cityName);
                        rep()->userDetail->m()->where('user_id', $user->id)->update(['region' => $cityName]);
                    }
                }
                pocket()->account->updateLocation($user->id, $lng, $lat);
            }
        }
    }
}
