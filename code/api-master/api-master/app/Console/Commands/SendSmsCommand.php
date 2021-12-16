<?php


namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendSmsCommand extends Command
{
    protected $signature   = 'xiaoquan:send_sms {active} {parameter}';
    protected $description = '发送魅力女生七日不活跃短信';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $args   = $this->arguments();
        $active = $args['active'];
        switch ($active) {
            case 'ios_down_call_20210711':
                $parameter = $args['parameter'];
                switch ($parameter) {
                    case 'all':
                        $bizKey      = 'ok_ios_down_call_20210711';
                        $smsAds      = rep()->smsAd->m()
                            ->where('biz_key', 'ios_down_call_20210711')
                            ->get();
                        $mobiles     = $smsAds->pluck('mobile')->toArray();
                        $sendMobiles = array_chunk($mobiles, 1000);
                        foreach ($sendMobiles as $mobile) {
                            $createData = [];
                            $now        = time();
                            foreach ($mobile as $item) {
                                $createData[] = [
                                    'biz_key'    => $bizKey,
                                    'mobile'     => $item,
                                    'send_at'    => time(),
                                    'status'     => 100,
                                    'created_at' => $now,
                                    'updated_at' => $now
                                ];
                            }
                            rep()->smsAd->m()->insert($createData);
                            pocket()->tengYu->sendIosDownCallSmsMessage($mobile);
                            echo '已发送' . count($mobile) . '条短信' . PHP_EOL;
                        }
                        break;
                    case 'yuexing':
                        $bizKey        = 'ok_ios_down_call_20210711';
                        $smsAds        = rep()->smsAd->m()
                            ->where('biz_key', 'ios_down_call_20210711')
                            ->get();
                        $mobiles       = $smsAds->pluck('mobile')->toArray();
                        $preAddMobiles = [13777178285, 15565136668, 18610389870, 18519666644, 18600698620];
                        foreach ($preAddMobiles as $preAddMobile) {
                            $mobiles[] = $preAddMobile;
                        }
                        $sendMobiles = array_chunk($mobiles, 90);
                        foreach ($sendMobiles as $mobile) {
                            $createData = [];
                            $now        = time();
                            foreach ($mobile as $item) {
                                $createData[] = [
                                    'biz_key'    => $bizKey,
                                    'mobile'     => $item,
                                    'send_at'    => time(),
                                    'status'     => 100,
                                    'created_at' => $now,
                                    'updated_at' => $now
                                ];
                            }
                            rep()->smsAd->m()->insert($createData);
                            pocket()->yueXin->sendIosDownCallSms($mobile);
                            echo '已发送' . count($mobile) . '条短信' . PHP_EOL;
                        }
                        break;
                    case 'test':
                        $bizKey = 'ok_ios_down_call_20210711';
                        //                        $mobiles     = [13777178285, 15565136668, 18610389870, 18519666644, 18600698620];
                        $mobiles     = [13777178285];
                        $sendMobiles = array_chunk($mobiles, 2);
                        foreach ($sendMobiles as $mobile) {
                            $now = time();
                            foreach ($mobile as $item) {
                                $createData[] = [
                                    'biz_key'    => $bizKey,
                                    'mobile'     => $item,
                                    'send_at'    => time(),
                                    'status'     => 100,
                                    'created_at' => $now,
                                    'updated_at' => $now
                                ];
                            }
                            //                            rep()->smsAd->m()->insert($createData);
                            pocket()->yueXin->sendIosDownCallSms($mobile);
                            echo '已发送' . count($mobile) . '条短信' . PHP_EOL;
                        }
                        break;
                }
                break;
        }
    }
}
