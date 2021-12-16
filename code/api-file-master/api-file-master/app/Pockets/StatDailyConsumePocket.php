<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Models\StatDailyConsume;
use App\Models\TradeBuy;
use App\Models\TradePay;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Cache\RedisLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class StatDailyConsumePocket extends BasePocket
{

    /**
     * 获取或创建当日的统计记录
     *
     * @param  string  $os
     * @param          $todayStartAt
     *
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    private function getOrCreateRecord($os, $todayStartAt)
    {
        $lock = new RedisLock(Redis::connection(), 'lock:create_stat_daily_consume_record', 3);
        $lock->block(3, function () use ($os, $todayStartAt) {
            $recordCount = rep()->statDailyConsume->getQuery()->where('os', $os)->where('date',
                $todayStartAt)->count();
            if (!$recordCount) {
                $iosRecord = $androidRecord = [
                    'os'         => StatDailyConsume::OS_IOS,
                    'date'       => $todayStartAt,
                    'created_at' => time(),
                    'updated_at' => time()
                ];

                $androidRecord['os'] = StatDailyConsume::OS_ANDROID;

                rep()->statDailyConsume->getQuery()->insert([$iosRecord, $androidRecord]);
            }
        });
    }

    /**
     * 用户交易统计
     *
     * @param  User                    $user
     * @param  UserDetail              $userDetail
     * @param  \App\Models\TradeModel  $model
     *
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     * TODO: 未统计代币购买的会员
     */
    public function consumeStat(User $user, UserDetail $userDetail, $model)
    {
        $todayStartAt = date('Y-m-d', $model->getRawOriginal('created_at'));
        $userIsNew    = $user->getRawOriginal('created_at') >= strtotime($todayStartAt);
        $recordOs     = StatDailyConsume::USER_DETAIL_OS_MAPPING[$userDetail->reg_os]
            ?? StatDailyConsume::OS_ANDROID;
        $amount       = abs($model instanceof TradePay ? $model->getAmount()
            : $model->getRawOriginal('ori_amount'));

        if ($amount) {
            $this->getOrCreateRecord($recordOs, $todayStartAt);
            if ($model instanceof TradePay) {
                $consumeField = $userIsNew ? 'new_member_total' : 'old_member_total';
            } else {
                switch ($model->getRawOriginal('related_type')) {
                    case TradeBuy::RELATED_TYPE_BUY_WECHAT:
                        $consumeField = $userIsNew ? 'new_unlock_wechat_total' : 'old_unlock_wechat_total';
                        break;
                    case TradeBuy::RELATED_TYPE_BUY_PHOTO:
                        $consumeField = $userIsNew ? 'new_unlock_video_total' : 'old_unlock_video_total';
                        break;
                    case TradeBuy::RELATED_TYPE_BUY_PRIVATE_CHAT:
                    default:
                        $consumeField = $userIsNew ? 'new_unlock_chat_total' : 'old_unlock_chat_total';
                }
            }

            $totalFiled   = $userIsNew ? 'new_user_total' : 'old_user_total';
            $updateFields = [
                $consumeField => DB::raw($consumeField . ' + ' . $amount),
                $totalFiled   => DB::raw($totalFiled . ' + ' . $amount)
            ];

            rep()->statDailyConsume->getQuery()->where('date', $todayStartAt)
                ->where('os', $recordOs)->update($updateFields);
        }
    }
}
