<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CityCommand extends Command
{
    protected $signature   = 'xiaoquan:city';
    protected $description = '计算城市';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $users = rep()->user->m()->where('gender',User::GENDER_WOMEN)->get();
        foreach ($users as $user) {
            $isCharm = pocket()->user->hasRole($user, User::ROLE_CHARM_GIRL);
            if ($isCharm) {
                $region = rep()->userDetail->m()->where('user_id', $user->id)->value('region');
                $this->line($region);
                $statics = DB::table('statics')->where('city',$region)->first();
                if ($statics) {
                    DB::table('statics')->where('city', $region)->increment('count');
                } else {
                    DB::table('statics')->where('city', $region)->insert([
                        'city'       => $region,
                        'count'      => 1,
                        'created_at' => time(),
                    ]);
                }
                $this->line($user->id . '已经查询');
            }
        }
        $this->line('处理成功！');
    }
}
