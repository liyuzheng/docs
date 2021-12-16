<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\TradePay;

class StatUserPocket extends BasePocket
{
    /**
     * 是否可以更新用户第一次付费的时间秒数
     *
     * @param  int  $userId
     *
     * @return bool
     */
    public function whetherUpdateFirstTopUpSeconds(int $userId)
    {
        $statUser = rep()->statUser->getQuery()->where('user_id', $userId)->first();
        if (!$statUser || $statUser->first_top_up_seconds === 0) {
            return true;
        }

        return false;
    }

    /**
     * 更新用户第一次交易订单数据
     *
     * @param  int  $userId
     *
     * @return ResultReturn
     */
    public function createOrUpdateFirstTopUpSecondsByFirstTradePay(int $userId)
    {
        if (!pocket()->statUser->whetherUpdateFirstTopUpSeconds($userId)) {
            return ResultReturn::failed('无法更新');
        }
        $tradePay = rep()->tradePay->getQuery()->where('user_id', $userId)
            ->where('done_at', '>', 0)
            ->where('amount', '>', 0)
            ->orderBy('id', 'asc')
            ->first();
        if (!$tradePay) {
            return ResultReturn::failed('找不到交易订单');
        }

        return pocket()->statUser->createOrUpdateFirstTopUpSecondsByTradePay($userId, $tradePay);
    }

    /**
     * 更新用户第一次付费的时间秒数
     *
     * @param  int       $userId
     * @param  TradePay  $tradePay
     *
     * @return ResultReturn
     */
    public function createOrUpdateFirstTopUpSecondsByTradePay(int $userId, TradePay $tradePay)
    {

        $user      = rep()->user->getById($userId);
        $regTime   = $user->created_at->timestamp;
        $tradeTime = $tradePay->created_at->timestamp;
        $diffTime  = $tradeTime - $regTime;
        if ($diffTime <= 0) {
            return ResultReturn::failed('时间小于等于0');
        }
        $statUser = rep()->statUser->getQuery()->updateOrCreate([
            'user_id'              => $userId,
            'first_top_up_seconds' => $tradeTime - $regTime
        ]);

        return ResultReturn::success([
            'user_id'   => $userId,
            'stat_user' => $statUser
        ]);
    }
}
