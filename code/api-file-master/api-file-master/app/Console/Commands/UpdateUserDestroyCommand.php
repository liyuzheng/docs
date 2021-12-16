<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateUserDestroyCommand extends Command
{
    protected $signature   = 'xiaoquan:update_user_destroy';
    protected $description = '命令集合';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $st          = Carbon::now()->subHours(1)->timestamp;
        $et          = Carbon::now()->addHours(1)->timestamp;
        $userDestroy = rep()->userDestroy->m()
            ->whereBetween('destroy_at', [$st, $et])
            ->where('cancel_at', 0)
            ->get();
        $now         = time();
        foreach ($userDestroy as $item) {
            $this->info('destroy_id: ' . $item->id . ' user_id: ' . $item->user_id);
            $delayTime = (($item->destroy_at - $now) <= 0) ? 0 : $item->destroy_at - $now;
            $resp      = pocket()->common->commonQueueMoreByPocketJob(
                pocket()->userDestroy,
                'postDestroyByUserDestroy',
                [$item],
                $delayTime
            );
        }
    }
}
