<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * 修复用户创建时间
 * Class FillCoordinateCommand
 * @package App\Console\Commands
 */
class FixEsUserCreatedAtCommand extends Command
{
    protected $signature   = 'xiaoquan:fix_es_created_at';
    protected $description = '修复用户创建时间';

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
            $timestamp  = $user->created_at->timestamp;
            $userDetail = rep()->userDetail->m()->where('user_id', $user->id)->first();
            if ($timestamp && $userDetail) {
                $update = [
                    'created_at'     => $timestamp,
                    'followed_count' => $userDetail->followed_count,
                    'destroy_at'     => $user->destroy_at
                ];
                pocket()->esUser->updateUserFieldToEs($user->id, $update);
            } else {
                $this->line('用户id：' . $user->id . ':修复异常！');
            }
            $this->line('用户id：' . $user->id . ':修复用户时间成功！');
        }
        $this->line('success!');
    }
}
