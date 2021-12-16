<?php


namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearSmsCommand extends Command
{
    protected $signature   = 'xiaoquan:clear_sms';
    protected $description = '清理短信内容';

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
        $dayTime = time() - 2 * 24 * 60 * 60;
        $this->line('开始清理！' . date('Y-m-d H:i:s', $dayTime) . '天前的短信');
        rep()->sms->m()->where('created_at', '<=', $dayTime)
            ->chunk(5000, function ($sms) {
                rep()->sms->m()->whereIn('id', $sms->pluck('id')->toArray())->forceDelete();
                $this->line('5000条已经处理~');
            });
        $this->line('清理结束！');
    }
}
