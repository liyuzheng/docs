<?php


namespace App\Pockets;


use App\Models\User;
use App\Models\UserFollowOffice;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;

class UserFollowOfficePocket extends BasePocket
{
    /**
     * @param  string  $ticket
     * @param          $status
     *
     * @return int
     */
    public function updateStatusByTicket(string $ticket, $status)
    {
        return rep()->userFollowOffice->getQuery()
            ->where('ticket', $ticket)
            ->update(['status' => $status]);
    }

    /**
     * 取消某个用户的微信公众号绑定状态
     *
     * @param  int  $userId
     *
     * @return int
     */
    public function cancelOfficeBind(int $userId)
    {
        return rep()->userFollowOffice->getQuery()
            ->where('user_id', $userId)
            ->update(['status' => UserFollowOffice::STATUS_CANCEL_FOLLOW]);
    }

    /**
     * 获得关注微信公众号的情况
     *
     * @param  int  $userId
     *
     * @return ResultReturn
     */
    public function getWeChatOfficeFollowArr(int $userId)
    {
        $latestFollowArr = rep()->userFollowOffice->getQuery()
            ->where('user_id', $userId)
            ->where('expired_at', '>=', time())
            ->orderBy('id', 'desc')
            ->first();
        if (!$latestFollowArr) {
            return ResultReturn::failed('没有数据', [
                'is_follow'       => false,
                'url'             => '',
                'push_msg_switch' => false,
            ]);
        }
        $isFollowCount = rep()->userFollowOffice->getQuery()
            ->where('user_id', $userId)
            ->where('status', UserFollowOffice::STATUS_FOLLOW)
            ->count();
        $switch        = rep()->switchModel->getPushTemMsg();
        if (!$switch || !$isFollowCount) {
            return ResultReturn::success([
                'is_follow'       => (bool)$isFollowCount,
                'url'             => $latestFollowArr->url,
                'push_msg_switch' => false,
            ]);
        }
        $userSwitch = rep()->userSwitch->getUserSwitch($userId, $switch->id);
        if (!$userSwitch) {
            pocket()->userSwitch->postSyncPushTemMsgStateByFollow($userId, true);
        }

        return ResultReturn::success([
            'is_follow'       => (bool)$isFollowCount,
            'url'             => $latestFollowArr->url,
            'push_msg_switch' => $userSwitch ? ((bool)$userSwitch->status) : false,
        ]);
    }

    /**
     * 给user追加 follow_of 数组
     *
     * @param  User  $user
     *
     * @return User
     */
    public function appendFollowOfArr(User $user)
    {
        $userId                 = $user->id;
        $weChatOfficeFollowResp = pocket()->userFollowOffice->getWeChatOfficeFollowArr($userId);
        $weChatOfficeFollowData = $weChatOfficeFollowResp->getData();
        if (!$weChatOfficeFollowResp->getStatus()) {
            pocket()->common->commonQueueMoreByPocketJob(
                pocket()->wechat,
                'getFollowOfficeQrCode',
                [$userId]
            );
        }
        $user->setAttribute('follow_of', $weChatOfficeFollowData);

        return $user;
    }
}
