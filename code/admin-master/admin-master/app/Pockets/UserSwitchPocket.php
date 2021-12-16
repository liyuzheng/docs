<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\SwitchModel;
use App\Models\UserSwitch;

class UserSwitchPocket extends BasePocket
{
    /**
     * 处理用户关注微信公众号处理开关状态
     *
     * @param  int   $userId    用户id
     * @param  bool  $isFollow  当前行为是否是在关注公众号
     *
     * @return ResultReturn
     */
    public function postSyncPushTemMsgStateByFollow(int $userId, bool $isFollow)
    {
        $switch = rep()->switchModel->getPushTemMsg();
        if (!$switch) {
            return ResultReturn::failed('开关不存在');
        }
        $state = $isFollow ? 1 : 0;

        $userSwitch = rep()->userSwitch->getUserSwitch($userId, $switch->id);
        if (!$userSwitch) {
            $attributes    = [
                'uuid'      => pocket()->util->getSnowflakeId(),
                'user_id'   => $userId,
                'switch_id' => $switch->id,
                'status'    => $state,
            ];
            $newUserSwitch = rep()->userSwitch->m()->create($attributes);

            return ResultReturn::success($newUserSwitch);
        }

        $updateUserSwitch = $userSwitch->update(['status' => $state]);

        return ResultReturn::success($updateUserSwitch);
    }

    /**
     * 修改用户用户是否接受微信模板消息
     *
     * @param  int   $userId
     * @param  bool  $status
     *
     * @return ResultReturn
     */
    public function postPushTemMsgState(int $userId, bool $status)
    {
        $switch = rep()->switchModel->getPushTemMsg();
        if (!$switch) {
            return ResultReturn::failed('开关不存在');
        }
        $state            = $status ? 1 : 0;
        $userSwitch       = rep()->userSwitch->getUserSwitch($userId, $switch->id);
        $updateUserSwitch = $userSwitch->update(['status' => $state]);

        return ResultReturn::success($updateUserSwitch);
    }

    /**
     * 从缓存中获取开关
     *
     * @param $userId
     * @param $key
     *
     * @return string
     */
    public function getUserSwitchCache($userId, $key = SwitchModel::KEY_LOCK_PHONE)
    {
        $redisKey        = config('redis_keys.user_switch_cache.key');
        $client          = redis()->client();
        $cacheUserSwitch = $client->hGet($redisKey, $userId);
        if ($cacheUserSwitch) {
            return $cacheUserSwitch;
        }

        $switch = rep()->userSwitch->m()
            ->join('switch', 'switch.id', 'user_switch.switch_id')
            ->where('user_id', $userId)
            ->where('switch.key', $key)
            ->where('status', UserSwitch::STATUS_OPEN)
            ->first();
        if ($switch) {
            $status = UserSwitch::STATUS_OPEN;
        } else {
            $status = UserSwitch::STATUS_CLOSE;
        }
        $client->hSet($redisKey, $userId, $status);

        return $status;
    }

    /**
     * 设置开关到缓存
     *
     * @param $userId
     * @param $key
     *
     * @return string
     */
    public function setUserSwitchCache($userId, $key = SwitchModel::KEY_LOCK_PHONE)
    {
        $redisKey = config('redis_keys.user_switch_cache.key');
        $client   = redis()->client();
        $switch   = rep()->userSwitch->m()
            ->join('switch', 'switch.id', 'user_switch.switch_id')
            ->where('user_id', $userId)
            ->where('switch.key', $key)
            ->where('status', UserSwitch::STATUS_OPEN)
            ->first();
        if ($switch) {
            $status = UserSwitch::STATUS_OPEN;
        } else {
            $status = UserSwitch::STATUS_CLOSE;
        }
        $client->hSet($redisKey, $userId, $status);

        return ResultReturn::success([]);
    }
}
