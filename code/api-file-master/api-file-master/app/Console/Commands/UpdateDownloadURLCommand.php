<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateDownloadURLCommand extends Command
{
    protected $signature   = 'xiaoquan:update_download_url {os} {url}';
    protected $description = '更新下载地址脚本,配合spug自动发布做';

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
        $os  = $this->argument('os');
        $url = $this->argument('url');
        switch ($os) {
            case 'apple':
                $key = 'apple_latest_url';
                break;
            case 'android':
                $key = 'android_latest_url';
                break;
            default:
                $this->error('系统错误');

                return;
                break;
        }
        rep()->config->m()->where('key', $key)->update(['value' => $url]);
        $this->info('succeed: ' . ' key: ' . $key . ' value: ' . $url);
    }
}
