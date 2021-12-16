<?php

namespace App\Console\Commands;

use App\Models\UserReview;
use Illuminate\Console\Command;

class UpdateCharmGirlDoneAtCommand extends Command
{
    protected $signature   = 'xiaoquan:update_charm_girl_done_at';
    protected $description = '更新魅力女生完成时间';

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
        $this->line('开始处理！');
        $roles = pocket()->role->getUserRoleArr(['charm_girl']);
        rep()->user->m()
            ->whereIn('role', $roles)
            ->chunk(1000, function ($users) {
                foreach ($users as $user) {
                    $userReview = rep()->userReview->m()
                        ->where('check_status', UserReview::CHECK_STATUS_PASS)
                        ->where('user_id', $user->id)
                        ->where('deleted_at', 0)
                        ->first();
                    if ($userReview) {
                        pocket()->esUser->updateUserFieldToEs($user->id, [
                            'charm_girl'         => 1,
                            'charm_girl_done_at' => $userReview->done_at,
                        ]);
                    } else {
                        $this->error('user数据：', $user->id);
                    }
                }
                $this->line('1000条处理完成');
            });
        $this->line('处理完毕！');
    }
}
