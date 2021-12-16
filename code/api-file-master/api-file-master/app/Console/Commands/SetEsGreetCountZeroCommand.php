<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 重置es打招呼数量为0
 * Class FillCoordinateCommand
 * @package App\Console\Commands
 */
class SetEsGreetCountZeroCommand extends Command
{
    protected $signature   = 'xiaoquan:set_es_greet_count_zero';
    protected $description = '重置es打招呼数量为0';

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
        rep()->user->m()
            ->where('id', '>=', $startUserId)
            ->where('id', '<=', $endUserId)
            ->chunk(1000, function ($tmpUsers) {
                foreach ($tmpUsers as $user) {
                    pocket()->esUser->updateUserFieldToEs($user->id, [
                        'greet_count_two_days' => 0
                    ]);
                }
                $this->info('1000条更新成功！最新的id是：' . $tmpUsers->last()->id);
            });

    }
}
