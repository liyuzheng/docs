<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\UserSwitch;
use App\Models\SwitchModel;

class UserSwitchRepository extends BaseRepository
{
    public function setModel()
    {
        return UserSwitch::class;
    }

    /**
     * 获得用户开关
     *
     * @param  int  $userId
     * @param  int  $switchId
     *
     * @return \App\Models\BaseModel|\App\Models\Model|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getUserSwitch(int $userId, int $switchId)
    {
        return $this->m()
            ->where('user_id', $userId)
            ->where('switch_id', $switchId)
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * 获得用户屏蔽联系人状态
     *
     * @param $userId
     *
     * @return bool
     */
    public function getPhoneShieldStatus($userId)
    {
        $userLock       = rep()->switchModel->m()->where('key', 'phone')->first();
        $userLockSwitch = rep()->userSwitch->m()
            ->where('user_id', $userId)
            ->where('switch_id', $userLock->id)
            ->first();
        if ($userLockSwitch && $userLockSwitch->status == UserSwitch::STATUS_CLOSE) {
            return false;
        } else {
            return true;
        }
    }
}
