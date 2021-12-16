<?php


namespace App\Console\Commands;


use App\Models\User;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;

class SendIosAllManUsersNewTopUpCommand extends Command
{
    protected $signature = 'xiaoquan:send_ios_all_man_users_new_top_up';
    protected $description = '给历史所有女生的头像和相册的水印';

    public function handle()
    {
        $query = rep()->user->getQuery()->join('user_detail', 'user.id', 'user_detail.user_id')
            ->where('user.gender', User::GENDER_MAN)->where('user_detail.os', 'ios')
            ->where('user.created_at', '<', 1607155200)->orderBy('id');

        $limit   = 500;
        $offset  = 0;
        $sendUid = config('custom.recharge_helper_uuid');
        $message = '公众号搜索【小圈App】购买会员享受9折优惠！！！由于苹果充值近期不稳定，抱歉给您带来不便~';

        do {
            $tmpQuery = clone $query;
            $uids     = $tmpQuery->offset($offset)->limit($limit)->pluck('uuid')->toArray();
            try {
                pocket()->netease->msgSendBatchMsg($sendUid, $uids, $message);
            } catch (GuzzleException $e) {
                d($e->getMessage());
            }

            $count  = count($uids);
            $offset += $count;

            unset($tmpQuery);
            unset($uids);
        } while ($count >= $limit);
    }
}
