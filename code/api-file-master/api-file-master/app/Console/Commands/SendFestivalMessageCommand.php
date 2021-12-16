<?php


namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendFestivalMessageCommand extends Command
{
    protected $signature   = 'xiaoquan:festival_message';
    protected $description = '发送双旦短信';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        //        $now = time();
        //        if ($now < 1609430400) {
        //            echo "还没到发送时间" . PHP_EOL;
        //            exit;
        //        }
        //        for ($i = 0; true; $i += 5000) {
        //            $mobiles = rep()->user->m()
        //                ->select(['user.id', 'user.mobile'])
        //                ->join('user_detail', 'user.id', '=', 'user_detail.user_id')
        //                ->where('user_detail.os', 'ios')
        //                ->limit(5000)
        //                ->offset($i)
        //                ->get();
        //            if (count($mobiles) == 0) {
        //                break;
        //            }
        //            pocket()->tengYu->sendSpringFestivalMessage($mobiles->pluck('mobile')->toArray());
        //            foreach ($mobiles as $item) {
        //                $message = 'id:' . $item->id . ' mobile:' . $item->mobile . ' time:' . date('Y-m-d H:i:s', $now);
        //                logger()->setLogType('festival_message')->info($message);
        //            }
        //        }
    }
}
