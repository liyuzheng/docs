<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * 修复用户会员
 * Class FillCoordinateCommand
 * @package App\Console\Commands
 */
class FixEsMemberCommand extends Command
{
    protected $signature   = 'xiaoquan:fix_es_member';
    protected $description = '修复用户会员';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $startUserId = $this->ask('请输入起始用户ID');
        $endUserId   = $this->ask('请输入结束用户ID');
        if (!$startUserId || !$endUserId) {
            $this->error('id不能为空');

            return false;
        }
        $users = rep()->user->m()
            ->where('id', '>=', $startUserId)
            ->where('id', '<=', $endUserId)
            ->get();
        foreach ($users as $user) {
            $update = ['is_member' => 0];
            if ($user->isMember()) {
                $update = ['is_member' => 1];
            }
            pocket()->esUser->updateUserFieldToEs($user->id, $update);
            $this->line('用户id：' . $user->id . '-修复会员状态成功！');
        }
    }
}
