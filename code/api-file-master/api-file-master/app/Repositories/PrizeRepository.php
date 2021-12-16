<?php


namespace App\Repositories;


use App\Models\Prize;
use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\PrizeGroup;

class PrizeRepository extends BaseRepository
{
    public function setModel()
    {
        return Prize::class;
    }

    /**
     * 获得女用户邀请用户注册成为会员
     *
     * @return \Illuminate\Database\Eloquent\HigherOrderBuilderProxy|int|mixed
     */
    public function getWoManInviteMemberAmount()
    {
        $prize = $this->m()->where('type', Prize::TYPE_WOMAN_INVITE_MEMBER)->first();

        return $prize ? $prize->value : 0;
    }

    /**
     * 获得男用户邀请注册
     *
     * @return \App\Models\BaseModel|\App\Models\Model|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getManInviteReg()
    {
        return $this->m()->where('type', Prize::TYPE_MAN_INVITE)->orderBy('id', 'desc')->first();
    }

    /**
     * 获得男用户邀请会员
     *
     * @return \App\Models\BaseModel|\App\Models\Model|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getManInviteMember()
    {
        return $this->m()->where('type', Prize::TYPE_MAN_INVITE_MEMBER)->orderBy('id', 'desc')->first();
    }

    /**
     * 获得女用户邀请会员
     *
     * @return \App\Models\BaseModel|\App\Models\Model|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getWoManInviteMember()
    {
        return $this->m()->where('type', Prize::TYPE_WOMAN_INVITE_MEMBER)->orderBy('id', 'desc')->first();
    }

    /**
     * 通过类型获取奖品
     *
     * @param  int  $type
     *
     * @return \App\Models\Prize
     */
    public function getPrizeByType(int $type)
    {
        return $this->getQuery()->where('type', $type)->first();
    }
}
