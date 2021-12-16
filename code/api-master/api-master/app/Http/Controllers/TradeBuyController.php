<?php


namespace App\Http\Controllers;


use App\Models\TradeBuy;
use App\Models\UnlockPreOrder;
use App\Models\UserRelation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TradeBuyController extends BaseController
{
    /**
     * 解锁用户服务 微信和私聊
     *
     * @param  Request  $request
     * @param  int      $uuid
     *
     * @return JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function unlockUser(Request $request, int $uuid)
    {
        $relationType = UserRelation::TYPE_MAPPING[$request->query('type', 'private_chat')];
        $consumer     = rep()->user->m()->find($request->user()->id);
        if ($consumer->uuid == $uuid) {
            return api_rr()->forbidCommon(trans('messages.self_not_unlock_self_notice'));
        }

        $beneficiary        = rep()->user->m()->where('uuid', $uuid)->first();
        $tranResp       = pocket()->tradeBuy->unlockUser($consumer, $beneficiary, $relationType);
        if (!$tranResp->getStatus()) {
            return api_rr()->customFailed($tranResp->getMessage(), $tranResp->getData());
        }

        [$relationPrices, $wallet, $trades] = $tranResp->getData();
        pocket()->common->commonQueueMoreByPocketJob(pocket()->tradeBuy, 'sendUnlockMessage',
            [$consumer, $beneficiary, $relationPrices, user_agent()->appName]);

        foreach ($trades as $tradeBuy) {
            pocket()->common->commonQueueMoreByPocketJob(pocket()->stat, 'statUserConsume',
                [$consumer->id, $tradeBuy->id], 10);
        }

        return api_rr()->postOK(['wallet' => ['balance' => (int)($wallet->balance / 10)]]);
    }

    /**
     * 解锁红包视频
     *
     * @param  Request  $request
     * @param  int      $uuid
     *
     * @return JsonResponse
     */
    public function unlockVideo(Request $request, int $uuid)
    {
        $consumer = $request->user();
        $resource = rep()->resource->m()->where('uuid', $uuid)->first();
        if (!$resource) {
            return api_rr()->notFoundResult(trans('messages.resource_not_exists'));
        }

        $beneficiary = rep()->user->getQuery()->find($resource->related_id);
        $unlockResp  = pocket()->tradeBuy->unlockVideo($consumer, $beneficiary, $resource);

        if ($unlockResp->getStatus()) {
            pocket()->common->commonQueueMoreByPocketJob(pocket()->stat, 'statUserConsume',
                [$consumer->id, $unlockResp->getData()->id], 10);
        } else {
            return api_rr()->customFailed($unlockResp->getMessage(), $unlockResp->getData());
        }

        return api_rr()->postOK([]);
    }

    /**
     * 代币消费记录
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function consumeRecords(Request $request)
    {
        $trades       = rep()->tradeBuy->getQuery()->select('id', 'target_user_id',
            'ori_amount', 'related_type', 'created_at')->with([
            'targetUser' => function ($query) {
                $query->select('id', 'user.nickname', 'user.uuid')->withTrashed();
            }
        ])->where('user_id', $request->user()->id)
            ->where('ori_amount', '>', 0)->orderBy('id', 'desc')
            ->paginate(20);
        $refundTrades = rep()->unlockPreOrder->getQuery()->whereIn('buy_id', $trades
            ->getCollection()->pluck('id')->toArray())->where('status', UnlockPreOrder::STATUS_REFUND)
            ->pluck('buy_id')->toArray();

        foreach ($trades as $trade) {
            /** @var \App\Models\TradeBuy $trade */
            $trade->setAttribute('related_type_tips', sprintf('解锁%s%s', $trade->targetUser->nickname,
                TradeBuy::RELATED_TYPE_TIPS_MAPPING[$trade->related_type]));
            $trade->setAttribute('unit', in_array($trade->id, $refundTrades) ? '钻石(已退款)' : '钻石');
            $trade->setHidden(array_merge($trade->getHidden(), ['targetUser']));
        }

        $nextPage = $trades->currentPage() + 1;

        return api_rr()->getOK(pocket()->util->getPaginateFinalData(
            $trades->getCollection(), $nextPage));
    }

    /**
     * 代币收益记录
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function incomeRecords(Request $request)
    {
        $trades = rep()->tradeBuy->getQuery()->select('user_id', 'amount',
            'related_type', 'created_at')->with([
            'user' => function ($query) {
                $query->select('id', 'user.nickname', 'user.uuid')->withTrashed();
            }
        ])->where('target_user_id', $request->user()->id)
            ->where('amount', '>', 0)->orderBy('id', 'desc')
            ->paginate(20);

        $tipsTemplate = trans('messages.unlocked_records_tmpl');
        foreach ($trades as $trade) {
            /** @var \App\Models\TradeBuy $trade */
            $trade->setAttribute('related_type_tips', sprintf($tipsTemplate, $trade->user->nickname,
                TradeBuy::RELATED_TYPE_TIPS_MAPPING[$trade->related_type]));
            $trade->setAttribute('unit', '钻石');
            $trade->setAttribute('ori_amount', $trade->getRawOriginal('amount'));
            $trade->setHidden(array_merge($trade->getHidden(), ['user']));
        }

        $nextPage = $trades->currentPage() + 1;

        return api_rr()->getOK(pocket()->util->getPaginateFinalData(
            $trades->getCollection(), $nextPage));
    }

    /**
     * 分页获取用户已解锁的魅力女生列表
     *
     * @param  Request  $request
     * @param  int      $uuid
     *
     * @return JsonResponse
     */
    public function unlockedUsers(Request $request, int $uuid = 0)
    {
        $user     = $request->user();
        $nextPage = $page = $request->get('page', 0);
        $users    = rep()->tradeBuy->getUnlockedUsers($user, $page);
        pocket()->user->appendToUsers($users, ['avatar']);

        $useMemberPrivilege  = trans('messages.use_member_tmpl');
        $consumeDiamondsTmpl = trans('messages.consume_diamonds_tmpl');
        $unlockRecordTmpl    = trans('messages.unlock_tmpl');

        foreach ($users as $user) {
            /** @var \App\Models\User $user */
            $user->setAttribute('event_at', date('m-d H:i', $user->getRawOriginal('created_at')));
            $type   = $user->related_type == TradeBuy::RELATED_TYPE_BUY_WECHAT ? '微信和私信' : '私信';
            $amount = $user->ori_amount ? sprintf($consumeDiamondsTmpl, $user->ori_amount / 10)
                : $useMemberPrivilege;
            $user->setAttribute('unlock_tips',
                sprintf($unlockRecordTmpl, $amount, $user->nickname, $type));
            $user->setHidden(array_merge($user->getHidden(),
                ['ori_amount', 'related_type', 'age', 'buy_id']));
        }

        if ($users->isNotEmpty()) {
            $lastUser = $users->last();
            $nextPage = sprintf('%d-%d', $lastUser->buy_id, $lastUser->id);
        }

        return api_rr()->getOK(pocket()->util->getPaginateFinalData($users, $nextPage));
    }

    /**
     * 分页获取解锁过某个用户的用户列表
     *
     * @param  Request  $request
     * @param  int      $uuid
     *
     * @return JsonResponse
     */
    public function beUnlocked(Request $request, int $uuid)
    {
        $user     = rep()->user->getQuery()->where('uuid', $uuid)->first();
        $nextPage = $page = $request->get('page', 0);
        $users    = rep()->tradeBuy->getUnlockedUserMans($user, $page);
        pocket()->user->appendToUsers($users, ['avatar', 'member', 'distance' => $user]);

        foreach ($users as $user) {
            $user->setAttribute('event_at', date('m-d H:i', $user->getRawOriginal('created_at')));
            $user->setHidden(array_merge($user->getHidden(), ['birthday', 'buy_id']));
        }

        if ($users->isNotEmpty()) {
            $lastUser = $users->last();
            $nextPage = sprintf('%d-%d', $lastUser->buy_id, $lastUser->id);
        }

        return api_rr()->getOK(pocket()->util->getPaginateFinalData($users, $nextPage));
    }
}
