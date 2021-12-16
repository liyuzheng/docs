<?php


namespace App\Repositories;


use App\Foundation\Modules\Repository\BaseRepository;
use App\Models\User;
use App\Models\SwitchModel;

class UserRepository extends BaseRepository
{
    public function setModel()
    {
        return User::class;
    }

    /**
     * 根据UUID获取用户
     *
     * @param            $uuid
     * @param  string[]  $fields
     *
     * @return \App\Models\BaseModel|\App\Models\Model|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getByUUid($uuid, $fields = ['*'])
    {
        return rep()->user->m()->select($fields)->where('uuid', $uuid)->first();
    }

    /**
     * 根据uuid获取一批用户
     *
     * @param  array     $uuids
     * @param  string[]  $fields
     *
     * @return \App\Models\BaseModel[]|\App\Models\Model[]|array|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getByUUids(array $uuids, $fields = ['*'])
    {
        return rep()->user->m()->select($fields)->whereIn('uuid', $uuids)->get();
    }

    /**
     * 根据一组uuid获取用户id
     *
     * @param $uuids
     *
     * @return array
     */
    public function getIdsByUUids(array $uuids)
    {
        return rep()->user->m()->whereIn('uuid', $uuids)->get()->pluck('id')->toArray();
    }

    /**
     * 根据昵称获取用户
     *
     * @param  string    $nickname
     * @param  string[]  $fields
     *
     * @return array
     */
    public function getUserByNickName(string $nickname, $fields = ['*'])
    {
        return rep()->user->m()->select($fields)->where('nickname', $nickname)->get()->toArray();
    }


    /**
     * 根据手机号获取用户
     *
     * @param  int       $mobile
     * @param  string[]  $fields
     *
     * @return \App\Models\BaseModel|\App\Models\Model|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getLatestUserByMobile(int $mobile, $fields = ['*'])
    {
        return rep()->user->m()->select($fields)->where('mobile', $mobile)->orderBy('id', 'desc')->first();
    }

    /**
     * 根据某个字段获取用户
     *
     * @param  int|string  $certificate
     * @param  string      $field
     * @param  string[]    $fields
     *
     * @return \App\Models\User|null
     */
    public function getLatestUserByFiled($certificate, $field, $fields = ['*'])
    {
        return rep()->user->m()->select($fields)->where($field, $certificate)
            ->orderBy('id', 'desc')->first();
    }

    /**
     * 根据手机号获取用户[批量]
     *
     * @param  array     $mobiles
     * @param  string[]  $fields
     *
     * @return \App\Models\BaseModel|\App\Models\Model|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getUserByMobiles(array $mobiles, $fields = ['*'])
    {
        return rep()->user->m()->select($fields)->whereIn('mobile', $mobiles)->get();
    }


    /**
     * 根据ids获得uuids
     *
     * @param  array  $ids
     *
     * @return array
     */
    public function getUUidsByIds(array $ids): array
    {
        $users      = rep()->user->m()->select(['id', 'uuid'])->whereIn('id', $ids)->get();
        $returnData = [];
        foreach ($users as $user) {
            $returnData[$user->id] = $user->uuid;
        }

        return $returnData;
    }

    /**
     * 获得用户对象
     *
     * @param  array  $ids 用户ID
     * @param  array  $fields 查询字段
     *
     * @return \App\Models\Model[]|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getUsersById(array $ids, $fields = ['*'])
    {
        return rep()->user->m()->select($fields)->whereIn('id', $ids)->get();
    }

    /**
     * 检测用户是否主动隐身
     *
     * @param $id
     *
     * @return bool
     */
    public function isHideUser($id)
    {
        $userHide = rep()->user->m()->select(['id'])->where('id', $id)->where('hide', User::HIDE)->first();
        if ($userHide) {
            $switch     = rep()->switchModel->m()->where('key', SwitchModel::KEY_ADMIN_HIDE_USER)->first();
            $userSwitch = rep()->userSwitch->m()->where('switch_id', $switch->id)->where('user_id', $id)->first();
            if ($userSwitch && $userSwitch->status == 0) {
                return false;
            } else {
                return true;
            }
        }

        return false;
    }
}
