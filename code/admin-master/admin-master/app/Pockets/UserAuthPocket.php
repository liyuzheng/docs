<?php


namespace App\Pockets;


use App\Models\UserAuth;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use Illuminate\Support\Facades\Hash;

class UserAuthPocket extends BasePocket
{
    /**
     * 获得云信注册token
     *
     * @param  int  $userId
     *
     * @return \Illuminate\Database\Eloquent\HigherOrderBuilderProxy|mixed|string
     */
    public function getNeteaseTokenByUserId(int $userId)
    {
        $secret = rep()->userAuth->getByUserIdType($userId, UserAuth::TYPE_NETEASE_TOKEN);

        return $secret ? $secret->secret : '';
    }

    /**
     * 批量获得云信注册token
     *
     * @param  array  $userIds
     *
     * @return \Illuminate\Database\Eloquent\HigherOrderBuilderProxy|mixed|string
     */
    public function getNeteaseTokenByUserIds(array $userIds)
    {
        $secrets = rep()->userAuth->getByUserIdsType($userIds, UserAuth::TYPE_NETEASE_TOKEN);
        $data    = [];
        foreach ($userIds as $userId) {
            $val           = $secrets->where('user_id', $userId)->first();
            $data[$userId] = $val ? $val->secret : "";
        }

        return $data;
    }


    /**
     * 重设密码
     *
     * @param $userId
     * @param $password
     *
     * @return ResultReturn
     */
    public function resetPassword($userId, $password) : ResultReturn
    {
        $time = time();
        rep()->userAuth->m()
            ->updateOrInsert([
                'user_id' => $userId,
                'type'    => UserAuth::TYPE_PASSWORD,
            ], [
                'user_id'    => $userId,
                'type'       => UserAuth::TYPE_PASSWORD,
                'secret'     => Hash::make($password),
                'created_at' => $time,
                'updated_at' => $time,
            ]);

        return ResultReturn::success([]);
    }

    /**
     * 验证密码是否正确
     *
     * @param $userId
     * @param $password
     *
     * @return ResultReturn
     */
    public function checkPassword($userId, $password) : ResultReturn
    {
        $pas = rep()->userAuth->m()->where('user_id', $userId)->where('type', UserAuth::TYPE_PASSWORD)->first();
        if ($pas && Hash::check($password, $pas->secret)) {
            return ResultReturn::success([]);
        }

        return ResultReturn::failed("密码不匹配");
    }


    /**
     * 某个用户是否已经设置过密码
     *
     * @param $userId
     *
     * @return mixed
     */
    public function hasPassword($userId)
    {
        return (int)rep()->userAuth->m()
            ->where('type', UserAuth::TYPE_PASSWORD)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * 用户扫描专属二维码时候绑定用户id和openid
     *
     * @param  int     $userId
     * @param  string  $openId
     *
     * @return ResultReturn
     */
    public function createOrUpdateUserWeChatOfficeOpenId(int $userId, string $openId)
    {
        $userAuth = rep()->userAuth->getQuery()
            ->where('user_id', $userId)
            ->where('type', UserAuth::TYPE_OFFICE_OPENID)
            ->first();
        if ($userAuth) {
            $userAuth->update(['secret' => $openId]);

            return ResultReturn::success([
                'user_id' => $userId,
                'open_id' => $openId
            ]);
        }

        $openIdArr = [
            'user_id' => $userId,
            'type'    => UserAuth::TYPE_OFFICE_OPENID,
            'secret'  => $openId
        ];
        $userAuth  = rep()->userAuth->getQuery()->create($openIdArr);

        return ResultReturn::success([
            'user_id' => $userId,
            'open_id' => $openId
        ]);
    }
}
