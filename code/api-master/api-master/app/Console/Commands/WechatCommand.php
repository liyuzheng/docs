<?php

namespace App\Console\Commands;

use App\Models\User;
use EasyWeChat\Factory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WechatCommand extends Command
{
    protected $signature   = 'xiaoquan:wechat';
    protected $description = '计算城市';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $buttons     = [
            [
                "type" => "view",
                "name" => "点击下载",
                "url"  => "https://web.wqdhz.com/download4"
            ],
            [
                "type" => "view",
                "name" => "会员充值",
                "url"  => "https://web-pay.wqdhz.com/wechat/payment"
            ],
        ];
        $config      = config('wechat.official_account.default');
        $weChatApp   = Factory::officialAccount($config);
        $accessToken = pocket()->wechat->getOfficeAccessToken();
        $weChatApp['access_token']->setToken($accessToken, 7200);
        $res = $weChatApp->menu->create($buttons);
        dd($res);
    }
}
