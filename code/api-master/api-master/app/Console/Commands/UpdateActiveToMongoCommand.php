<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateActiveToMongoCommand extends Command
{
    protected $signature   = 'xiaoquan:update_active_to_mongo';
    protected $description = '更新活跃时间到mongo';

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
        rep()->user->m()->chunk(1000, function ($users) {
            foreach ($users as $user) {
                mongodb('user')->where('_id', $user->id)->update([
                    'active_at' => $user->active_at
                ]);
            }
            $this->line('1000条处理完成');
        });
        $this->line('处理完毕！');
    }
}
