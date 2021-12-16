<?php


namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Constant\NeteaseCustomCode;

class SendErrorUserMsgCommand extends Command
{
    protected $signature   = 'xiaoquan:send_error_user';
    protected $description = '给异常用户发消息';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $status  = $this->ask('请输入模式 1:查看需要发送的手机号 2:发送短信');
        $userIds = DB::table('fix_url_error_user')
            ->where('latest_active_at', '<=', 1620144000)
            ->where('os', 'ios')
            ->whereNotIn('mobile', [17303888669])
            ->get()
            ->pluck('user_id')
            ->toArray();
        $users   = rep()->user->m()
            ->select(['uuid', 'mobile'])
            ->whereIn('id', $userIds)
            ->where('active_at', '<=', 1620144000)
            ->get();
        if ($status == 1) {
            dd($users->toArray());
        } elseif ($status == 2) {
            pocket()->tengYu->sendErrorUserMsg($users->pluck('mobile')->toArray());
            foreach ($users as $user) {
                $extension = [
                    'option' => [
                        'badge' => false
                    ]
                ];
                $data      = [
                    'type' => NeteaseCustomCode::STRONG_REMINDER,
                    'data' => [
                        'title'   => '“不支持的URL”解决方法',
                        'content' => '删除手机上所有小圈App后重新下载即可'
                    ]
                ];
                pocket()->common->sendNimMsgQueueMoreByPocketJob(
                    pocket()->netease,
                    'msgSendCustomMsg',
                    [config('custom.little_helper_uuid'), $user->uuid, $data, $extension]
                );
            }
        }
    }
}
