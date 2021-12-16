<?php


namespace App\Repositories;


use App\Models\UserAuth;
use App\Foundation\Modules\Repository\BaseRepository;

class UserAuthRepository extends BaseRepository
{
    public function setModel()
    {
        return UserAuth::class;
    }

    /**
     * 根据用户ID和type获得对象
     *
     * @param  int             $userId
     * @param  int             $type
     * @param  array|string[]  $fields
     *
     * @return \App\Models\BaseModel|\App\Models\Model|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getByUserIdType(int $userId, int $type, array $fields = ['*'])
    {
        return $this->m()->select($fields)->where('user_id', $userId)->where('type', $type)->first();
    }

    /**
     * 根据用户ID和type获得一批对象
     *
     * @param  array           $userIds
     * @param  int             $type
     * @param  array|string[]  $fields
     *
     * @return \App\Models\BaseModel|\App\Models\Model|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getByUserIdsType(array $userIds, int $type, array $fields = ['*'])
    {
        return $this->m()->select($fields)->whereIn('user_id', $userIds)->where('type', $type)->get();
    }

    /**
     * 根据微信公众号openId获得用户ID
     *
     * @param  string  $openId
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getByWeChatOfficeOpenId(string $openId)
    {
        return rep()->userAuth->getQuery()
            ->where('secret', $openId)
            ->where('type', UserAuth::TYPE_OFFICE_OPENID)
            ->first();
    }
}
