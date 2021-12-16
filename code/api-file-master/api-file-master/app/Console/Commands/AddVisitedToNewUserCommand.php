<?php


namespace App\Console\Commands;


use Illuminate\Console\Command;
use App\Models\User;

class AddVisitedToNewUserCommand extends Command
{
    protected $signature   = 'xiaoquan:add_visited';
    protected $description = '给新版本注册的男用户添加看过';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $users     = rep()->user->m()->where('gender', User::GENDER_MAN)
            ->where('created_at', '>', 1614931200)
            ->get();
        $vipStatus = [];
        $isMembers = rep()->member->m()
            ->whereIn('user_id', $users->pluck('id')->toArray())->get();
        foreach ($isMembers as $isMember) {
            $vipStatus[] = $isMember->user_id;
        }
        foreach ($users as $user) {
            if (in_array($user->id, $vipStatus)) {
                continue;
            }
            pocket()->common->commonQueueMoreByPocketJob(
                pocket()->user,
                'addVisitedToUser',
                [$user->id],
                60 * rand(2, 10)
            );
        }
    }
}
