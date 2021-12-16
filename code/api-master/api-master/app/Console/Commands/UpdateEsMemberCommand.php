<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Jobs\UpdateMemberToEsJob;

/**
 * 更新用户会员[每10分钟跑一次]
 * Class FillCoordinateCommand
 * @package App\Console\Commands
 */
class UpdateEsMemberCommand extends Command
{
    protected $signature   = 'xiaoquan:update_es_member';
    protected $description = '更新用户会员';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $time    = time();
        $members = rep()->member->m()
            ->whereRaw('start_at + duration >= ' . $time)
            ->whereRaw('start_at + duration <= ' . ($time + 11 * 60))
            ->get();
        foreach ($members as $member) {
            $user = rep()->user->m()->where('id', $member->user_id)->first();
            if (!$user) {
                continue;
            }
            $update = ['is_member' => 0];
            if ($user->isMember()) {
                $update = ['is_member' => 1];
            }
            $delay = ($member->start_at + $member->duration) - $time;
            if ($delay <= 0) $delay = 0;
            $job = (new UpdateMemberToEsJob($user->id, $update))
                ->onQueue('update_member_to_es')
                ->delay(Carbon::now()->addSeconds($delay));
            dispatch($job);
            $this->line('用户id：' . $user->id . '-更新会员状态成功！' . $delay . 's后生效');
        }
    }
}
