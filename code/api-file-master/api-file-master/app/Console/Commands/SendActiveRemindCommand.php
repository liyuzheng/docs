<?php


namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class SendActiveRemindCommand extends Command
{
    protected $signature   = 'xiaoquan:remind';
    protected $description = '给不活跃用户发信息';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $users   = rep()->user->m()
            ->where('active_at', '<', strtotime('2021-02-12'))
            ->where('gender', User::GENDER_MAN)
            ->where('hide', User::SHOW)
            ->get();
        $mobiles = $users->pluck('mobile')->toArray();
        $k       = 0;
        while (true) {
            $sendMobiles = array_slice($mobiles, $k, 5000);
            if (count($sendMobiles) == 0) {
                break;
            }
            $createData = [];
            $uid        = pocket()->util->getSnowflakeId();
            foreach ($sendMobiles as $mobile) {
                $now          = time();
                $createData[] = [
                    'biz_key'    => $uid,
                    'mobile'     => $mobile,
                    'send_at'    => 0,
                    'status'     => 0,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }
            rep()->smsAd->m()->insert($createData);
            pocket()->tengYu->sendActiveRemindMessage($sendMobiles);
            $k += 5000;
            echo '已发送' . $k . '条短信' . PHP_EOL;
        }
    }
}
