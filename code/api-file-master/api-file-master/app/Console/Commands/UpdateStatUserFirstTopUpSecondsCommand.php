<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateStatUserFirstTopUpSecondsCommand extends Command
{
    protected $signature   = 'xiaoquan:update_stat_user_first_top_up_seconds_command {st} {et}';
    protected $description = '更新stat_user的first_top_up_seconds字段';

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
        $args = $this->arguments();
        $st   = $args['st'];
        $et   = $args['et'];
        for ($i = $st; $i <= $et; $i++) {
            $user = rep()->user->getById($i);
            if (!$user || !$user->created_at->timestamp) {
                continue;
            }
            $userId = $user->id;
            $this->info('user_id: ' . $userId . ' nickname: ' . $user->nickname);
            $tradePay = rep()->tradePay->getQuery()->where('user_id', $userId)
                ->where('done_at', '>', 0)
                ->where('amount', '>', 0)
                ->orderBy('id', 'asc')
                ->first();
            if (!$tradePay) {
                continue;
            }
            pocket()->common->commonQueueMoreByPocketJob(
                pocket()->statUser,
                'createOrUpdateFirstTopUpSecondsByFirstTradePay',
                [$userId]
            );
        }
    }
}
