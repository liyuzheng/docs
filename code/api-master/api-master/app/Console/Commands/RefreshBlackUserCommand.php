<?php


namespace App\Console\Commands;


use Illuminate\Console\Command;
use App\Models\Blacklist;

class RefreshBlackUserCommand extends Command
{
    protected $signature   = 'xiaoquan:refresh_black_user';
    protected $description = '补充封禁用户';

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
        $blackUser = rep()->blacklist->m()
            ->where('related_type', Blacklist::RELATED_TYPE_OVERALL)
            ->where('expired_at', 0)
            ->get();
        $uuids     = rep()->user->m()
            ->whereIn('id', $blackUser->pluck('related_id')->toArray())
            ->get();
        foreach ($uuids as $item) {
            $redisUserKey = config('redis_keys.blacklist.user.key');
            redis()->client()->zAdd($redisUserKey, 0, $item->id);
            pocket()->netease->userBlock($item->uuid);
            echo $item->id . "用户补充封禁成功" . PHP_EOL;
        }
    }
}
