<?php


namespace App\Console\Commands;


use App\Models\Sms;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ListenSmsCount extends Command
{
    protected $signature   = 'xiaoquan:listen_sms_count';
    protected $description = '监测短信当天发送数量';

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
        $startAt  = Carbon::today()->timestamp;
        $endTt    = time();
        $checkArr = [
            Sms::TYPE_MOBILE_SMS            => [
                'count'    => 40000,
                'sms_type' => '登录短信'
            ],
            Sms::TYPE_MOBILE_QUICKLY        => [
                'count'    => 40000,
                'sms_type' => '一键登录'
            ],
            Sms::TYPE_NOT_ACTIVE_CHARM_GIRL => [
                'count'    => 40000,
                'sms_type' => '没活跃的魅力女生'
            ],
            Sms::TYPE_INVITE_BIND           => [
                'count'    => 40000,
                'sms_type' => '绑定手机'
            ],
            Sms::TYPE_RESET_PASSWORD        => [
                'count'    => 40000,
                'sms_type' => '重置密码'
            ],
        ];
        foreach ($checkArr as $type => $item) {
            $smsCount = rep()->sms->m()
                ->whereBetween('created_at', [$startAt, $endTt])
                ->where('type', $type)
                ->count();
            if ($smsCount >= $item['count']) {
                $dingUrl = "https://oapi.dingtalk.com/robot/send?access_token=c8d84555a2b745d5786b021c7ea7c3effb7bf09537e2d07bc49779aade28d07c";
                $params  = [
                    $dingUrl,
                    sprintf('[短信异常]-' . $item['sms_type'] . ': %s', $smsCount)
                ];
                pocket()->dingTalk->sendSimpleMessage(...$params);
            }
            $this->line($smsCount);
        }
    }
}
