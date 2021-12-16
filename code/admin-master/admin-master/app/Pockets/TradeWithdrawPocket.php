<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\TradeModel;
use App\Models\TradeWithdraw;
use App\Models\User;
use App\Models\UserContact;
use Illuminate\Support\Facades\DB;

class TradeWithdrawPocket extends BasePocket
{
    /**
     * 创建用户提现记录
     *
     * @param  User         $user
     * @param  UserContact  $userContact
     * @param  int          $amount
     * @param  int          $type
     *
     * @return ResultReturn
     */
    public function createWithdrawRecordByUser(User $user, UserContact $userContact, int $amount, int $type)
    {
        [$amountField, $totalField] = TradeWithdraw::getAmountFieldsByType($type);
        /** @var \App\Models\Wallet $userWallet */
        $userWallet         = rep()->wallet->getQuery()->lockForUpdate()->find($user->id);
        $todayStartAt       = strtotime(date('Y-m-d'));
        $toadyWithdrawCount = rep()->tradeWithdraw->getQuery()->where('user_id', $user->id)
            ->where('created_at', '>=', $todayStartAt)->where('type', $type)->count();
        if ($toadyWithdrawCount) {
            return ResultReturn::failed('一天只能提现一次, 请明天再尝试.');
        }

        if ($userWallet->getRawOriginal($amountField) < $amount) {
            return ResultReturn::failed("提现失败，余额不足");
        }

        $userWithdrawRate   = TradeWithdraw::WITHDRAW_RATE;
        $userWithdrawAmount = $amount - $amount / (1 + $userWithdrawRate) * $userWithdrawRate;
        // 创建支付宝
        $withdrawData = [
            'user_id'       => $user->id,
            'contact_id'    => $userContact->id,
            'income_rate'   => $userWithdrawRate,
            'ori_amount'    => $amount,
            'amount'        => $userWithdrawAmount,
            'type'          => $type,
            'before_income' => $userWallet->getRawOriginal($amountField),
            'after_income'  => $userWallet->getRawOriginal($amountField) - $amount,
        ];

        /** @var \App\Models\TradeWithdraw $tradeWithdraw */
        $tradeWithdraw = rep()->tradeWithdraw->getQuery()->create($withdrawData);
        rep()->wallet->getQuery()->where('user_id', $user->id)->update([
            $amountField => DB::raw($amountField . ' - ' . $amount)
        ]);
        $userWallet->setRawOriginal($amountField, $userWallet->$amountField - $amount);
        $userWallet->setRawOriginal($totalField, $userWallet->$totalField + $amount);

        return ResultReturn::success($tradeWithdraw);
    }

    /**
     * 完成提现订单
     *
     * @param  User           $user
     * @param  TradeWithdraw  $withdraw
     * @param  int            $operatorId
     */
    public function completeRecordByWithdraw(User $user, TradeWithdraw $withdraw, $operatorId)
    {
        DB::transaction(function () use ($withdraw, $user, $operatorId) {
            $withdraw->update(['done_at' => time(), 'operator_id' => $operatorId]);
            pocket()->trade->createRecord($user, $withdraw);

            $withdraw->setRecordType(TradeModel::CONSUME);
            pocket()->tradeIncome->createRecord($user, $withdraw);
        });
    }
}
