<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateUserMapCommand extends Command
{
    protected $signature   = 'xiaoquan:update_user_map';
    protected $description = '命令集合';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $data  = [];
        $users = mongodb('user')->get();
        foreach ($users as $k => $user) {
            $data[] = [
                'lng'   => $user['location'][0] ?? 0,
                'lat'   => $user['location'][1] ?? 0,
                'count' => 1
            ];
        }
        file_put_contents(public_path("uploads/common/hot_map.json"), json_encode($data));
    }
}
