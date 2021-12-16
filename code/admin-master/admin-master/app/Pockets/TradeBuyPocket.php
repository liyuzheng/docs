<?php


namespace App\Pockets;


use App\Constant\ApiBusinessCode;
use App\Constant\NeteaseCustomCode;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\Resource;
use App\Models\TradeBuy;
use App\Models\TradeModel;
use App\Models\UnlockPreOrder;
use App\Models\User;
use App\Models\UserPhoto;
use App\Models\UserRelation;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TradeBuyPocket extends BasePocket
{
    /**
     * 创建用户代币消费记录
     *
     * @param  Wallet  $consumer
     * @param  Wallet  $beneficiary
     * @param  int     $amount
     * @param  int     $type
     *
     * @return \App\Models\TradeBuy
     */
    public function createTradeBuyRecord(Wallet $consumer, Wallet $beneficiary, int $amount, int $type)
    {
        $dividedIntoAmount = $amount * TradeBuy::TRADE_BUY_SHARE_RATIO;

        $tradeBuyRecord = [
            'user_id'        => $consumer->user_id,
            'target_user_id' => $beneficiary->user_id,
            'related_type'   => $type,
            'income_rate'    => TradeBuy::TRADE_BUY_SHARE_RATIO,
            'ori_amount'     => $amount,
            'amount'         => $dividedIntoAmount,
            'before_balance' => $consumer->getRawOriginal('balance'),
            'after_balance'  => $consumer->getRawOriginal('balance') - $amount,
            'before_income'  => $beneficiary->getRawOriginal('income'),
            'after_income'   => $beneficiary->getRawOriginal('income') + $dividedIntoAmount,
        ];

        $consumer->decrement('balance', $amount);
        rep()->wallet->getQuery()->where('user_id', $beneficiary->user_id)->update([
            'income'       => DB::raw('income + ' . $dividedIntoAmount),
            'income_total' => DB::raw('income_total + ' . $dividedIntoAmount),
        ]);

        $beneficiary->setRawOriginal('income',
            $beneficiary->getRawOriginal('income') + $dividedIntoAmount);

        $beneficiary->setRawOriginal('income_total',
            $beneficiary->getRawOriginal('income_total') + $dividedIntoAmount);

        return rep()->tradeBuy->getQuery()->create($tradeBuyRecord);
    }

    /**
     * 发送解锁消息
     *
     * @param  User    $consumer
     * @param  User    $beneficiary
     * @param  array   $relations
     * @param  string  $appName
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendUnlockMessage(User $consumer, User $beneficiary, array $relations, string $appName)
    {
        $relationsStr = '';
        foreach ($relations as $relation => $price) {
            $relationsStr = ($relationsStr ? $relationsStr . '和' : '') .
                UserRelation::RELATIONS_MAPPING[$relation];
        }

        $messageTemplate = '的' . $relationsStr . '见面了解后请给%s真诚的评价～';
        pocket()->user->appendToUsers(collect([$consumer, $beneficiary]), ['avatar']);

        $messages = [
            $consumer->uuid    => [
                'message' => sprintf('您解锁了%s～', $beneficiary->nickname),
                'user'    => $beneficiary->get('uuid', 'nickname', 'avatar'),
            ],
            $beneficiary->uuid => [
                'message' => sprintf('%s解锁了您' . $messageTemplate, $consumer->nickname, '他'),
                'user'    => $consumer->get('uuid', 'nickname', 'avatar'),
            ]
        ];

        $sender = pocket()->common->getSystemHelperByAppName($appName);
        foreach ($messages as $receiver => $message) {
            $body = [
                'type' => NeteaseCustomCode::UNLOCK_USER_MESSAGE,
                'data' => [
                    'sender'   => ['message' => '', 'user' => ['uuid' => $sender, 'nickname' => '', 'avatar' => '']],
                    'receiver' => $message
                ]
            ];
            pocket()->netease->msgSendCustomMsg($sender, $receiver, $body);
        }
    }

    /**
     * 验证视频解锁权限
     *
     * @param  User      $consumer
     * @param  Resource  $resource
     *
     * @return ResultReturn
     */
    public function verifyUnlockVideoPermission(User $consumer, Resource $resource)
    {
        $isReal = rep()->userPhoto->m()->where('resource_id', $resource->id)
            ->where('related_type', UserPhoto::RELATED_TYPE_RED_PACKET)
            ->first();

        if (!$isReal || $isReal->status != UserPhoto::STATUS_OPEN) {
            return ResultReturn::failed('当前照片不是红包视频，请重试', ApiBusinessCode::FORBID_COMMON);
        }

        $isLooked = rep()->userLookOver->m()->where('user_id', $consumer->id)->where('resource_id',
            $resource->id)->orderByDesc('id')->first();
        if ($isLooked && $isLooked->expired_at > time()) {
            return ResultReturn::failed('已经解锁过啦可以直接观看', ApiBusinessCode::FORBID_COMMON);
        }

        return ResultReturn::success($isReal);
    }

    /**
     * 解锁红包视频
     *
     * @param  User      $consumer
     * @param  User      $beneficiary
     * @param  Resource  $resource
     *
     * @return ResultReturn
     */
    public function unlockVideo(User $consumer, User $beneficiary, Resource $resource)
    {
        return DB::transaction(function () use ($consumer, $beneficiary, $resource) {
            $userWallet           = rep()->wallet->m()->where('user_id', $consumer->id)->lockForUpdate()->first();
            $verifyPermissionResp = $this->verifyUnlockVideoPermission($consumer, $resource);
            if (!$verifyPermissionResp->getStatus()) {
                return $verifyPermissionResp;
            }

            $isReal = $verifyPermissionResp->getData();
            if ($userWallet->balance < $isReal->amount) {
                return ResultReturn::failed("余额不足", ApiBusinessCode::LACK_OF_BALANCE);
            }

            $needUnlockWallet = rep()->wallet->getQuery()->where('user_id', $isReal->user_id)->first();
            $tradeBuy         = $this->createTradeBuyRecord($userWallet, $needUnlockWallet,
                $isReal->amount, TradeBuy::RELATED_TYPE_BUY_PHOTO);
            $tradeIncome      = pocket()->tradeIncome->createRecord($beneficiary, $tradeBuy);
            $tradeBuy->setRecordType(TradeModel::CONSUME);
            $tradeBalance = pocket()->tradeBalance->createRecord($consumer, $tradeBuy);
            pocket()->trade->batchCreateTradeRecord($tradeIncome, $tradeBalance);
            rep()->userLookOver->createLookOver($consumer, $isReal, time() + 86400);

            return ResultReturn::success($tradeBuy);
        });
    }

    /**
     * 解锁用户
     *
     * @param  User   $consumer
     * @param  User   $beneficiary
     * @param  array  $relationPrices
     *
     * @return ResultReturn
     */
    public function unlockUser(User $consumer, User $beneficiary, array $relationPrices)
    {
        return DB::transaction(function () use ($consumer, $beneficiary, $relationPrices) {
            $consumerWallet = rep()->wallet->getQuery()->where('user_id', $consumer->id)
                ->lockForUpdate()->first();

            if (array_sum($relationPrices) > $consumerWallet->getRawOriginal('balance')) {
                return ResultReturn::failed('余额不足', ApiBusinessCode::LACK_OF_BALANCE);
            }

            $beneficiaryWallet = rep()->wallet->getQuery()->where('user_id', $beneficiary->id)->first();
            $trades            = [];

            foreach ($relationPrices as $type => $relationPrice) {
                $price    = UserRelation::TRADE_BUY_RELATED_TYPES[$type];
                $tradeBuy = $this->createTradeBuyRecord($consumerWallet, $beneficiaryWallet,
                    $relationPrice, $price);
                if ($type == UserRelation::TYPE_PRIVATE_CHAT && $relationPrice
                    && version_compare(user_agent()->clientVersion, '2.1.0', '>=')) {
                    $this->generatePreOrder($consumer, $beneficiary, $tradeBuy);
                }

                $trades[] = $tradeBuy;
                if ($relationPrice) {
                    //                    $tradeIncome = pocket()->tradeIncome->createRecord($needUnlockUser, $tradeBuy);
                    $tradeBuy->setRecordType(TradeModel::CONSUME);
                    $tradeBalance = pocket()->tradeBalance->createRecord($consumer, $tradeBuy);
                    //                    pocket()->trade->batchCreateTradeRecord($tradeIncome, $tradeBalance);
                    pocket()->trade->batchCreateTradeRecord($tradeBalance);
                }

                pocket()->userRelation->createUserRelation($tradeBuy, $consumerWallet,
                    $beneficiaryWallet, $type);
            }

            return ResultReturn::success([$relationPrices, $consumerWallet, $trades]);
        });
    }

    /**
     * 生成解锁预订单
     *
     * @param  User      $consumer
     * @param  User      $beneficiary
     * @param  TradeBuy  $buy
     *
     * @return \App\Models\UnlockPreOrder
     */
    public function generatePreOrder(User $consumer, User $beneficiary, TradeBuy $buy)
    {
        $preOrderData = [
            'user_id'        => $consumer->id,
            'target_user_id' => $beneficiary->id,
            'buy_id'         => $buy->id,
            'expired_at'     => UnlockPreOrder::getExpiredAt(),
        ];

        return rep()->unlockPreOrder->getQuery()->create($preOrderData);
    }
}
