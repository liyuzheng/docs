<?php


namespace App\Pockets;

use App\Foundation\Handlers\Tools;
use App\Models\Discount;
use App\Models\Task;
use App\Models\TradePay;
use App\Models\User;
use App\Models\UserAb;
use App\Models\UserAuth;
use App\Models\UserDetail;
use App\Models\Wechat;
use App\Models\Resource;
use App\Models\Blacklist;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\DB;
use \Illuminate\Support\Collection;
use \Illuminate\Database\Eloquent\Model;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use Illuminate\Support\Str;
use App\Foundation\Services\Guzzle\GuzzleHandle;
use App\Jobs\StatRemainLoginLogJob;
use App\Models\Role;
use App\Models\SfaceRecord;
use App\Models\FacePic;
use App\Models\UserReview;
use App\Models\Tag;
use App\Models\UserVisit;
use Carbon\Carbon;
use App\Constant\NeteaseCustomCode;
use App\Models\UserSwitch;
use Illuminate\Cache\RedisLock;
use Illuminate\Support\Facades\Redis;
use App\Jobs\UpdateUserInfoToMongoJob;
use PHPUnit\Exception;
use GuzzleHttp\Exception\RequestException;

/**
 * Class UserPocket
 * @package App\Pockets
 */
class UserPocket extends BasePocket
{
    /**
     * 往User模型中添加数据
     *
     * @param  User   $user
     * @param  array  $property
     *
     * @return User
     */
    public function appendToUser(User $user, $property = [])
    {
        $filterProperty = pocket()->util->conversionAppendToUserArgs($property);
        /** append微信 */
        if (array_key_exists('wechat', $filterProperty)) {
            pocket()->user->appendWeChatToUser($user, $filterProperty['wechat']);
        }
        /** append头像 */
        if (array_key_exists('avatar', $filterProperty)) {
            pocket()->user->appendAvatarToUser($user);
        }
        /** append相册 */
        if (array_key_exists('photo', $filterProperty)) {
            pocket()->resource->appendPhotoToUser($user, $filterProperty['photo']);
        }
        /** append相册和视频的合集 */
        if (array_key_exists('album', $filterProperty)) {
            pocket()->resource->appendAlbumToUser($user, $filterProperty['album']);
        }
        /** append Job相关 */
        if (array_key_exists('job', $filterProperty)) {
            pocket()->account->appendJobToUser($user);
        }
        /** append网易 */
        if (array_key_exists('netease', $filterProperty)) {
            pocket()->account->appendNeteaseInfoToUser($user, $filterProperty['netease']);
        }
        /** append基础会员相关 */
        if (array_key_exists('member', $filterProperty)) {
            pocket()->user->appendMemberToUser($user);
        }
        /** append认证用户相关 */
        if (array_key_exists('auth_user', $filterProperty)) {
            pocket()->user->appendAuthUserToUser($user);
        }
        /** append魅力女生相关 */
        if (array_key_exists('charm_girl', $filterProperty)) {
            pocket()->user->appendCharmGirlToUser($user);
        }

        /** append用户详情相关 */
        if (array_key_exists('user_detail', $filterProperty)) {
            pocket()->user->appendUserDetailToUser($user);
        }
        /** append用户和某个坐标的距离 */
        if (array_key_exists('distance', $filterProperty)) {
            pocket()->user->appendDistanceToUser($user, $filterProperty['distance']);
        }
        /** append用户的活跃状态 */
        if (array_key_exists('active', $filterProperty)) {
            pocket()->user->appendActiveToUser($user);
        }
        /** append用户的number*/
        if (array_key_exists('number', $filterProperty)) {
            pocket()->user->appendNumberToUser($user);
        }
        /** append用户的爱好*/
        if (array_key_exists('hobby', $filterProperty)) {
            pocket()->user->appendHobbyToUser($user);
        }

        return $user;
    }

    /**
     * 往User collection中添加数据
     *
     * @param  Collection  $users
     * @param  array       $property
     *
     * @return Collection
     */
    public function appendToUsers(Collection $users, $property = [])
    {
        $filterProperty = pocket()->util->conversionAppendToUserArgs($property);
        /** append微信 */
        if (array_key_exists('wechat', $filterProperty)) {
            pocket()->user->appendWeChatToUsers($users, $filterProperty['wechat']);
        }
        /** append头像 */
        if (array_key_exists('avatar', $filterProperty)) {
            pocket()->user->appendAvatarToUsers($users);
        }
        /** append相册 */
        if (array_key_exists('photo', $filterProperty)) {
            pocket()->resource->appendPhotoToUsers($users);
        }
        /** append相册集 */
        if (array_key_exists('album', $filterProperty)) {
            pocket()->resource->appendAlbumToUsers($users, $filterProperty['album']);
        }
        /** append Job相关 */
        if (array_key_exists('job', $filterProperty)) {
            pocket()->account->appendJobToUsers($users);
        }
        /** append网易 */
        if (array_key_exists('netease', $filterProperty)) {
            pocket()->account->appendNeteaseInfoToUsers($users, $filterProperty['netease']);
        }
        /** append基础会员相关 */
        if (array_key_exists('member', $filterProperty)) {
            pocket()->user->appendMemberToUsers($users);
        }
        /** append认证用户相关 */
        if (array_key_exists('auth_user', $filterProperty)) {
            pocket()->user->appendAuthUserToUsers($users);
        }
        /** append魅力女生相关 */
        if (array_key_exists('charm_girl', $filterProperty)) {
            pocket()->user->appendCharmGirlToUsers($users);
        }
        /** append用户详情相关 */
        if (array_key_exists('user_detail', $filterProperty)) {
            pocket()->user->appendUserDetailToUsers($users);
        }

        /** append用户和某个坐标的距离 */
        if (array_key_exists('distance', $filterProperty)) {
            pocket()->user->appendDistanceToUsers($users, $filterProperty['distance']);
        }
        /** append用户的活跃状态 */
        if (array_key_exists('active', $filterProperty)) {
            pocket()->user->appendActiveToUsers($users);
        }
        /** append用户的昵称打* */
        if (array_key_exists('blur_nickname', $filterProperty)) {
            pocket()->user->appendBlurNickNameToUsers($users);
        }
        /** append用户的昵称打* */
        if (array_key_exists('blur_avatar', $filterProperty)) {
            pocket()->user->appendBlurAvatarToUsers($users, $filterProperty['blur_avatar']);
        }
        /** append用户的昵称打* */
        if (array_key_exists('detail_info', $filterProperty)) {
            pocket()->user->appendDetailInfoToUsers($users, $filterProperty['detail_info']);
        }

        return $users;
    }

    /**
     * 判断某个用户是否拥有某个角色
     *
     * @param  Model|User  $user 某个用户
     * @param  string      $role 对应user模型的role
     *
     * @return bool
     */
    public function hasRole(User $user, string $role): bool
    {
        if (!$user) {
            return false;
        }

        return in_array($role, explode(',', $user->role));
    }

    /**
     * 给user模型增加基本会员信息
     *
     * @param  User  $user
     *
     * @return User
     */
    public function appendMemberToUser(User $user)
    {
        $user->setAttribute('member', [
            'status' => $user->isMember()
        ]);

        return $user;
    }

    /**
     * 给user模型增加认证信息
     *
     * @param  User  $user
     *
     * @return User
     */
    public function appendAuthUserToUser(User $user)
    {
        $user->setAttribute('auth_user', [
            'status' => $this->hasRole($user, User::ROLE_AUTH_USER)
        ]);

        return $user;
    }

    /**
     * 给user模型增加魅力女生信息
     *
     * @param  User  $user
     *
     * @return User
     */
    public function appendCharmGirlToUser(User $user)
    {
        $user->setAttribute('charm_girl', [
            'status' => $this->hasRole($user, User::ROLE_CHARM_GIRL)
        ]);

        return $user;
    }


    /**
     * 给多个user模型增加基本会员信息
     *
     * @param  Collection  $users
     *
     * @return Collection
     */
    public function appendMemberToUsers(Collection $users)
    {
        $members = rep()->member->getQuery()
            ->select('user_id', 'start_at', 'duration')
            ->whereIn('user_id', $users->pluck('id')->toArray())
            ->get();

        $userMembers = [];
        foreach ($members as $member) {
            $userMembers[$member->user_id] = $member->start_at + $member->duration > time();
        }

        foreach ($users as $user) {
            $user->setAttribute('member', [
                'status' => isset($userMembers[$user->id]) && $userMembers[$user->id]
            ]);
        }

        return $users;
    }

    /**
     * 给多个user模型增加用户是否成为过会员
     *
     * @param  Collection  $users
     *
     * @return Collection
     */
    public function appendHasPayMemberToUsers(Collection $users)
    {
        $payIds      = rep()->tradePay->getQuery()->select(DB::raw('min(id) as id'))
            ->whereIn('user_id', $users->pluck('id')->toArray())
            ->where('related_type', TradePay::RELATED_TYPE_RECHARGE_VIP)
            ->where('done_at', '>', 0)->groupBy('user_id')->get()
            ->pluck('id')->toArray();
        $userMembers = [];
        if (!empty($payIds)) {
            $members = rep()->tradePay->getQuery()->select('trade_pay.user_id', 'card.level', 'card.name',
                'trade_pay.done_at')->join('card', 'card.id', 'trade_pay.related_id')
                ->whereIn('trade_pay.id', $payIds)->get();

            foreach ($members as $member) {
                $userMembers[$member->user_id] = $member;
            }
        }

        foreach ($users as $user) {
            $userMemberData = ['status' => isset($userMembers[$user->id])];
            if ($userMemberData['status']) {
                $userMemberData['type_str'] = $userMembers[$user->id]->name . '会员';
                $userMemberData['pay_at']   = date('Y/m/d', $userMembers[$user->id]->done_at);
            }

            $user->setAttribute('member', $userMemberData);
        }

        return $users;
    }

    /**
     * 给多个user模型增加认证信息
     *
     * @param  Collection  $users
     *
     * @return Collection
     */
    public function appendAuthUserToUsers(Collection $users)
    {
        foreach ($users as $user) {
            $user->setAttribute('auth_user', [
                'status' => $this->hasRole($user, User::ROLE_AUTH_USER)
            ]);
        }

        return $users;
    }

    /**
     * 给user模型增加魅力女生信息
     *
     * @param  Collection  $users
     *
     * @return Collection
     */
    public function appendCharmGirlToUsers(Collection $users)
    {
        foreach ($users as $user) {
            $user->setAttribute('charm_girl', [
                'status' => $this->hasRole($user, User::ROLE_CHARM_GIRL)
            ]);
        }

        return $users;
    }

    /**
     * 根据用户uuid或者用户id获取基本信息
     *
     * @param  int     $id
     *
     * @param  string  $field
     *
     * @return ResultReturn
     */
    public function getUserInfo(int $id, $field = 'id')
    {
        $tags = rep()->tag->m()->where('type', '>=', Tag::TYPE_EMOTION)->get();
        $user = rep()->user->m()
            ->select(['id', 'uuid', 'number', 'nickname', 'gender', 'birthday', 'role', 'active_at', 'hide'])
            ->with([
                'userDetail'      => function ($query) {
                    $query->select(['user_id', 'intro', 'region', 'height', 'weight', 'region', 'intro']);
                },
                'userDetailExtra' => function ($query) {
                    $query->select(['user_id', 'emotion', 'child', 'education', 'income', 'figure', 'smoke', 'drink']);
                }
            ])
            ->where($field, $id)
            ->first();
        if (!$user) {
            return ResultReturn::failed(trans('messages.user_not_exist'));
        }

        $userDetailExtra = $user->userDetailExtra;
        if (!$userDetailExtra) {
            return ResultReturn::failed('更多资料获取失败，请重试');
        }
        $userDetailExtraData = $userDetailExtra->toArray();
        foreach ($userDetailExtraData as $tagKey => $tagId) {
            if (!$tagId) {
                $user->userDetailExtra->$tagKey = null;
            }
            $user->userDetailExtra->$tagKey = $tags->where('id', $tagId)->first();
        }
        /*** @var $user User */
        pocket()->user->appendToUser($user, ['avatar', 'member', 'auth_user', 'charm_girl', 'hobby']);

        return ResultReturn::success($user);
    }

    /**
     * 根据用户uuid获取多个用户的基本信息
     *
     * @param  array   $ids
     *
     * @param  string  $field
     *
     * @return ResultReturn
     */
    public function getUsersInfo(array $ids, $field = 'id')
    {
        $orderBy = implode(',', $ids);
        $users   = rep()->user->m()
            ->select(['id', 'uuid', 'number', 'nickname', 'gender', 'role', 'birthday', 'hide', 'active_at'])
            ->with([
                'userDetail' => function ($query) {
                    $query->select(['user_id', 'intro', 'region', 'height', 'weight', 'region', 'intro']);
                }
            ])
            ->whereIn($field, $ids)->when(!empty($orderBy), function ($query) use ($field, $orderBy) {
                $query->orderByRaw(DB::raw("FIELD($field, $orderBy)"));
            })->get();
        pocket()->user->appendToUsers($users, ['avatar', 'netease' => ['accid']]);

        return ResultReturn::success($users);
    }

    /**
     * 根据用户id获取一个用户基本信息
     *
     * @param  int  $userId
     *
     * @return ResultReturn
     */
    public function getUserInfoById(int $userId)
    {
        return $this->getUserInfo($userId, 'id');
    }

    /**
     * 根据UUID获取一个用户基本信息
     *
     * @param  int  $userId
     *
     * @return ResultReturn
     */
    public function getUserInfoByUUID(int $userId)
    {
        return $this->getUserInfo($userId, 'uuid');
    }

    /**
     * 根据用户ids获取多个基本信息
     *
     * @param  array  $userIds
     *
     * @return ResultReturn
     */
    public function getUsersInfoByUserIds(array $userIds)
    {
        return $this->getUsersInfo($userIds, 'id');
    }

    /**
     * 根据用户UUIDs获取多个基本信息
     *
     * @param  array  $uuids
     *
     * @return ResultReturn
     */
    public function getUsersInfoByUUIDs(array $uuids)
    {
        return $this->getUsersInfo($uuids, 'uuid');
    }

    /**
     * 根据用户手机号获取一个用户基本信息
     *
     * @param  int  $mobile
     *
     * @return ResultReturn
     */
    public function getUserInfoByMobile(int $mobile)
    {
        return $this->getUserInfo($mobile, 'mobile');
    }

    /**
     * 给用户添加评论
     *
     * @param  int    $sUserId
     * @param  int    $rUserId
     * @param  array  $evaluates
     *
     * @return ResultReturn
     */
    public function setEvaluateToUser(int $sUserId, int $rUserId, array $evaluates)
    {
        $data     = [];
        $tagUUIds = [];
        foreach ($evaluates as $evaluate) {
            $tagUUIds[] = $evaluate['uuid'];
        }
        $tags   = rep()->tag->m()->whereIn('uuid', $tagUUIds)->get();
        $tagIds = [];
        foreach ($tags as $tag) {
            $tagIds[$tag->uuid] = $tag->id;
        }
        foreach ($evaluates as $evaluate) {
            $data[] = [
                'uuid'           => pocket()->util->getSnowflakeId(),
                'user_id'        => $sUserId,
                'target_user_id' => $rUserId,
                'tag_id'         => $tagIds[$evaluate['uuid']],
                'star'           => $evaluate['star'],
                'created_at'     => time(),
                'updated_at'     => time()
            ];
            pocket()->common->commonQueueMoreByPocketJob(
                pocket()->userTag,
                'addFakeEvaluate',
                [$rUserId, $tagIds[$evaluate['uuid']], $evaluate['star']]
            );
        }
        rep()->userEvaluate->m()->insert($data);

        return ResultReturn::success([$data]);
    }

    /**
     * 根据用户手机号获取多个用户基本信息
     *
     * @param  array  $mobiles
     *
     * @return ResultReturn
     */
    public function getUsersInfoByMobiles(array $mobiles)
    {
        return $this->getUsersInfo($mobiles, 'mobile');
    }

    /**
     * 根据用户number获取一个用户基本信息
     *
     * @param  int  $number
     *
     * @return ResultReturn
     */
    public function getUserInfoByNumber(int $number)
    {
        return $this->getUserInfo($number, 'number');
    }

    /**
     * 根据用户numbers获取多个用户基本信息
     *
     * @param  array  $numbers
     *
     * @return ResultReturn
     */
    public function getUsersInfoByNumbers(array $numbers)
    {
        return $this->getUsersInfo($numbers, 'number');
    }

    /**
     * 更新user表的role字段
     *
     * @param $userId
     *
     * @return ResultReturn
     */
    public function updateUserTableRoleField($userId)
    {
        $rolesId = rep()->userRole->m()
            ->where('user_id', $userId)
            ->pluck('role_id')
            ->toArray();
        $keysArr = rep()->role->m()
            ->whereIn('id', $rolesId)
            ->pluck('key')
            ->toArray();
        $keys    = implode(',', $keysArr);
        if (rep()->user->m()->where('id', $userId)->update(['role' => $keys])) {
            return ResultReturn::success($keys);
        }

        return ResultReturn::failed($keys);
    }

    /**
     * 获取用户token
     *
     * @param  \App\Models\User|int
     *
     * @return string
     */
    public function getUserToken($user)
    {
        if (!($user instanceof User)) {
            $user = rep()->user->getById($user, ['uuid']);
        }

        $now         = time();
        $newTokenArr = [
            'id'     => $user->id,
            'update' => $now + 86400 * 7 - 3600,
            'delete' => $now + 86400 * 7
        ];

        return $user->uuid . '.' . aes_encrypt()->encrypt($newTokenArr);
    }

    /**
     * 设置通讯录黑名单
     *
     * @param  array  $mobiles
     * @param  int    $userId
     *
     * @return ResultReturn
     */
    public function setMobileBlacklist(array $mobiles, int $userId)
    {
        $now          = time();
        $mobileNumber = [];
        foreach ($mobiles as $mobile) {
            if (key_exists('mobile', $mobile)) {
                !isset($mobileNumber[$mobile['mobile']]) && $mobileNumber[$mobile['mobile']] = $mobile;
            }
        }

        $phoneNumbers = rep()->mobileBook->m()->where('user_id', $userId)
            ->whereIn('mobile', array_keys($mobileNumber))->get()
            ->pluck('mobile')->toArray();

        if (count($mobiles) > 0) {
            $phoneNumber = $createData = [];
            foreach ($mobileNumber as $mobile) {
                if (!isset($mobile['mobile']) || !isset($mobile['name'])
                    || strlen($mobile['mobile']) > 20 || strlen($mobile['name']) > 20
                    || in_array($mobile['mobile'], $phoneNumbers)) {
                    continue;
                }

                $phoneNumber[] = $mobile['mobile'];
                $createData[]  = [
                    'user_id'    => $userId,
                    'name'       => $mobile['name'],
                    'mobile'     => $mobile['mobile'],
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }
            rep()->mobileBook->m()->insert($createData);
            $existUsers = rep()->user->m()->whereIn('mobile', $phoneNumber)->get();
            if (count($existUsers) > 0) {
                foreach ($existUsers as $existUser) {
                    $blacklistData[] = [
                        'related_type' => Blacklist::RELATED_TYPE_MOBILE,
                        'related_id'   => $existUser->id,
                        'user_id'      => $userId,
                        'reason'       => 'mobile',
                        'created_at'   => $now,
                        'updated_at'   => $now
                    ];
                }
                rep()->blacklist->m()->insert($blacklistData);
            }
        }

        return ResultReturn::success([]);
    }

    /**
     * 给用户模型增加头像
     *
     * @param  User  $user
     *
     * @return User
     */
    public function appendAvatarToUser(User $user)
    {
        $resource = rep()->resource->m()
            ->select('resource')
            ->where('related_type', Resource::RELATED_TYPE_USER_AVATAR)
            ->where('related_id', $user->id)
            ->orderByDesc('id')
            ->first();
        $user->setAttribute('avatar', $resource ? cdn_url($resource->resource) : '');

        return $user;
    }

    /**
     * 给一组用户模型增加头像
     *
     * @param  Collection  $users
     *
     * @return Collection
     */
    public function appendAvatarToUsers(Collection $users)
    {
        $userIds   = $users->pluck('id')->toArray();
        $resources = rep()->resource->m()
            ->select('id', 'related_id', 'resource')
            ->where('related_type', Resource::RELATED_TYPE_USER_AVATAR)
            ->whereIn('related_id', $userIds)
            ->get();
        foreach ($users as $user) {
            $resource = $resources->where('related_id', $user->id)->sortByDesc('id')->first();
            $user->setAttribute(
                'avatar',
                $resource ? cdn_url($resource->resource) : ''
            );
        }

        return $users;
    }


    /**
     * 追加wechat模块数据
     *
     * @param  User   $user
     * @param  array  $fields 查询字段
     *
     * @return User
     */
    public function appendWeChatToUser(User $user, array $fields = [])
    {
        $weChats        = rep()->wechat->m()
            ->select('wechat', 'check_status')
            ->where('user_id', $user->id)
            ->whereIn('check_status', [Wechat::STATUS_PASS, Wechat::STATUS_DELAY])
            ->get();
        $weChat         = $weChats->where('check_status', Wechat::STATUS_PASS)->sortByDesc('id')->first();
        $delayWeChat    = $weChats->where('check_status', Wechat::STATUS_DELAY)->sortByDesc('id')->first();
        $userLock       = rep()->switchModel->m()->where('key', 'lock_wechat')->first();
        $userLockSwitch = rep()->userSwitch->m()
            ->where('user_id', $user->id)
            ->where('switch_id', $userLock->id)
            ->first();
        $fields         = $fields ? $fields : ['number', 'status', 'lock'];
        $fillData       = [];
        foreach ($fields as $field) {
            switch ($field) {
                case 'number':
                    $value = $weChat ? pocket()->common->substrCut($weChat->wechat) : '';
                    break;
                case 'status':
                    $value = $weChat ? Wechat::STATUS[$weChat->check_status] : '';
                    $value = $delayWeChat ? Wechat::STATUS[$delayWeChat->check_status] : $value;
                    break;
                case 'lock':
                    $value = ($userLockSwitch && in_array($userLockSwitch->status,
                            [UserSwitch::STATUS_OPEN, UserSwitch::STATUS_ADMIN_LOCK]));
                    break;
                default:
                    $value = '';
                    break;
            }
            in_array($field, $fields) && $fillData[$field] = $value;
        }
        $user->setAttribute('wechat', $fillData);

        return $user;
    }

    /**
     * 给一组用户模型增加模糊微信
     *
     * @param  Collection      $users
     * @param  array|string[]  $fields
     *
     * @return Collection
     */
    public function appendWeChatToUsers(Collection $users, array $fields = [])
    {
        $userIds = $users->pluck('id')->toArray();
        $weChats = rep()->wechat->m()
            ->select('id', 'user_id', 'wechat', 'status')
            ->whereIn('user_id', $userIds)
            ->get();

        $fields   = $fields ? $fields : ['number', 'status'];
        $fillData = [];
        /** @var User $user */
        foreach ($users as $user) {
            $weChat = $weChats->where('user_id', $user->id)->sortByDesc('id')->first();
            foreach ($fields as $field) {
                switch ($field) {
                    case 'number':
                        $value = $weChat ? pocket()->common->substr_cut($weChat->wechat) : '';
                        break;
                    case 'status':
                        $value = $weChat ? Wechat::STATUS[$weChat->check_status] : '';
                        break;
                    default:
                        $value = '';
                        break;
                }
                in_array($field, $fields) && $fillData[$field] = $value;
            }
            $user->setAttribute('wechat', $fillData);
        }

        return $users;
    }

    /**
     * 给用户模型增加明文微信
     *
     * @param  User   $user
     * @param  array  $fields
     *
     * @return User
     */
    public function appendRealWeChatToUser(User $user, array $fields = [])
    {
        $weChat   = rep()->wechat->m()
            ->select('wechat', 'qr_code', 'check_status')
            ->where('user_id', $user->id)
            ->whereIn('check_status', [Wechat::STATUS_PASS])
            ->orderByDesc('id')
            ->first();
        $fields   = $fields ? $fields : ['number', 'qr_code'];
        $fillData = [];
        foreach ($fields as $field) {
            switch ($field) {
                case 'number':
                    $value = $weChat ? $weChat->wechat : '';
                    break;
                case 'qr_code':
                    $value = $weChat ? $weChat->qr_code : '';
                    break;
                default:
                    $value = '';
                    break;
            }
            in_array($field, $fields) && $fillData[$field] = $value;
        }
        $user->setAttribute('wechat', $fillData);

        return $user;
    }

    /**
     * append某个用户的活跃状态
     *
     * @param  User  $user
     *
     * @return User
     */
    public function appendActiveToUser(User $user): User
    {
        $active = $this->getUserActiveInfoByActiveAt($user->active_at);
        $user->setAttribute('active_state', $active['active_state']);
        $user->setAttribute('active_format', $active['active_format']);

        return $user;
    }

    /**
     * append某个用户的活跃状态
     *
     * @param  Collection  $users
     *
     * @return Collection
     */
    public function appendActiveToUsers(Collection $users): Collection
    {
        foreach ($users as $user) {
            $active = $this->getUserActiveInfoByActiveAt($user->active_at);
            $user->setAttribute('active_state', $active['active_state']);
            $user->setAttribute('active_format', $active['active_format']);
        }

        return $users;
    }

    /**
     * append某个用户的活跃状态
     *
     * @param  Collection  $users
     *
     * @return Collection
     */
    public function appendBlurNickNameToUsers(Collection $users): Collection
    {
        foreach ($users as $user) {
            $user->setAttribute('nickname', str_repeat('*', mb_strlen($user->nickname, "utf8")));
        }

        return $users;
    }


    /**
     * append某个用户的活跃状态
     *
     * @param  Collection  $users
     * @param  User        $authUser
     *
     * @return Collection
     */
    public function appendBlurAvatarToUsers(Collection $users, $authUser): Collection
    {
        foreach ($users as $user) {
            if ($authUser->id != $user->id) {
                $user->setAttribute('avatar', $user->avatar . "?imageMogr2/blur/100x100");
            }
        }

        return $users;
    }

    /**
     * append某个用户的活跃状态
     *
     * @param  Collection  $users
     * @param  User        $authUser
     *
     * @return Collection
     */
    public function appendDetailInfoToUsers(Collection $users, $authUser): Collection
    {
        foreach ($users as $user) {
            $detailInfo = true;
            if ($user->gender == User::GENDER_WOMEN &&
                $user->gender == $authUser->gender &&
                $authUser->id != $user->id) {
                $detailInfo = false;
            }
            $user->setAttribute('detail_info', $detailInfo);
        }

        return $users;
    }

    /**
     * 给某个用户返回number
     *
     * @param  User  $user
     */
    public function appendNumberToUser(User $user)
    {
        //      $user->setAttribute('number', (string)$user['number']);
        $user->setAttribute('number', '');
    }

    /**
     * 给某个用户添加爱好
     *
     * @param  User  $user
     */
    public function appendHobbyToUser(User $user)
    {
        $userTags = rep()->userTag->m()->where('user_id', $user->id)->get();
        $tagIds   = $userTags->pluck('tag_id')->toArray();
        $hobbys   = rep()->tag->m()
            ->select(['uuid', 'name'])
            ->whereIn('id', $tagIds)
            ->where('type', Tag::TYPE_HOBBY)
            ->get();
        $user->setAttribute('hobbys', $hobbys);
    }

    /**
     * 给一组用户模型增加用户详情
     *
     * @param  Collection  $users
     *
     * @return Collection
     */
    public function appendUserDetailToUsers(Collection $users)
    {
        $userIds     = $users->pluck('id')->toArray();
        $userDetails = rep()->userDetail->m()
            ->select(['user_id', 'intro', 'height', 'weight', 'region'])
            ->whereIn('user_id', $userIds)
            ->get();
        foreach ($users as $user) {
            $detail = $userDetails->where('user_id', $user->id)->first();
            $user->setAttribute(
                'user_detail', $detail
            );
        }

        return $users;
    }

    /**
     * 给一个用户模型增加用户详情
     *
     * @param  User  $user
     *
     * @return User
     */
    public function appendUserDetailToUser(User $user)
    {
        $detail = rep()->userDetail->m()
            ->select(['user_id', 'intro', 'height', 'weight', 'region'])
            ->where('user_id', $user->id)
            ->first();
        $user->setAttribute('user_detail', $detail);

        return $user;
    }

    /**
     * 给一个用户模型增加到另外一个用户距离
     *
     * @param  User  $user
     *
     * @return User $targetUser
     */
    public function appendDistanceToUser(User $user, User $targetUser)
    {
        $lng1         = $lat1 = $lng2 = $lat2 = 0;
        $usersId      = [$user->id, $targetUser->id];
        $details      = rep()->userDetail->m()
            ->select('user_id', 'lat', 'lng')
            ->whereIn('user_id', $usersId)
            ->get();
        $targetDetail = $details->where('user_id', $targetUser->id)->first();
        $userDetail   = $details->where('user_id', $user->id)->first();
        if ($targetDetail && $userDetail) {
            $lng1 = $targetDetail->lng;
            $lat1 = $targetDetail->lat;
            $lng2 = $userDetail->lng;
            $lat2 = $userDetail->lat;
        }
        $distance = get_distance_str($lng1, $lat1, $lng2, $lat2);
        $user->setAttribute('distance', $distance);

        return $user;
    }

    /**
     * 给一组用户增加到某个坐标的距离
     *
     * @param  Collection  $users
     * @param  User        $targetUser
     *
     * @return Collection
     */
    public function appendDistanceToUsers(Collection $users, User $targetUser)
    {
        $lng1           = $lat1 = 0;
        $usersId        = array_merge($users->pluck('id')->toArray(), [$targetUser->id]);
        $usersDetail    = rep()->userDetail->m()
            ->select('user_id', 'lat', 'lng')
            ->whereIn('user_id', $usersId)
            ->get();
        $selfUserDetail = $usersDetail->where('user_id', $targetUser->id)->first();
        if ($selfUserDetail) {
            $lng1 = $selfUserDetail->lng;
            $lat1 = $selfUserDetail->lat;
        }
        foreach ($users as $user) {
            $distance = '未知';
            if ($lng1 != 0 && $lat1 != 0) {
                $userDetail = $usersDetail->where('user_id', $user->id)->first();
                if ($userDetail) {
                    $lng2 = $userDetail->lng;
                    $lat2 = $userDetail->lat;
                    if ($lng2 != 0 && $lat2 != 0) {
                        $distance = get_distance_str($lng1, $lat1, $lng2, $lat2);
                    }
                }
            }
            $user->setAttribute(
                'distance', $distance
            );
        }

        return $users;
    }


    /**
     * 获取两个用户之间的坐标的距离
     *
     * @param         $userId
     * @param         $targetUserId
     * @param  false  $unit 是否携带单位 公里和米
     *
     * @return float|int
     */
    public function getDistanceUsers($userId, $targetUserId, $unit = false)
    {
        if ($userId == $targetUserId) {
            return $unit ? '0m' : 0;
        }
        $distance   = $unit ? '未知' : -1;
        $mongoUsers = mongodb('user')->whereIn('_id', [$userId, $targetUserId])->get();
        foreach ($mongoUsers as $loc) {
            $location = $loc['location'] ?? [];
            if (isset($location[0], $location[1]) && $location[0] == 0 && $location[1] == 0) {
                return $distance;
            }
        }
        if ($mongoUsers->count() !== count([$userId, $targetUserId])) {
            return $distance;
        }
        if ($userId === $targetUserId) {
            return 0;
        }

        [$userLng, $userLat] = $mongoUsers[0]['location'];
        [$targetUserLng, $targetUserLat] = $mongoUsers[1]['location'];
        if ($targetUserLng == 0 && $targetUserLat == 0) {
            return $distance;
        }
        $position = [$userLng, $userLat, $targetUserLng, $targetUserLat];
        $distance = $unit ? get_distance_str(...$position) : get_distance(...$position);

        return $distance;
    }

    /**
     * 获取手机号归属地
     *
     * @param $mobile
     *
     * @return ResultReturn
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getUserMobileAttribution($mobile)
    {
        $gzHandle  = new GuzzleHandle();
        $client    = $gzHandle->getClient();
        $dateTime  = gmdate("D, d M Y H:i:s T");
        $SecretId  = config('sms.attribution.secret_id');
        $SecretKey = config('sms.attribution.secret_key');
        $source    = Str::random(16);
        $srcStr    = "date: " . $dateTime . "\n" . "source: " . $source;
        $headers   = [
            'Date'          => $dateTime,
            'Source'        => $source,
            'Authorization' => 'hmac id="' . $SecretId . '", algorithm="hmac-sha1", headers="date source", signature="'
                . base64_encode(hash_hmac('sha1', $srcStr, $SecretKey, true)) . '"',
        ];

        $requestUrl = config('sms.attribution.api') . '?mobile=' . $mobile;

        try {
            $response = $client->get($requestUrl, ['headers' => $headers]);
        } catch (\Exception $e) {
            return ResultReturn::failed(trans('messages.get_failed_error'));
        }

        return ResultReturn::success(json_decode($response->getBody()->getContents(), true));
    }

    /**
     * 用户是否更新redis
     *
     * @param  int  $userId
     * @param  int  $timestamp
     *
     * @return bool
     */
    public function whetherUpdateUserActiveAt(int $userId, int $timestamp)
    {
        $redisKey   = sprintf(config('redis_keys.cache.active_at'), $userId);
        $isUpdate   = true;
        $isHasRedis = redis()->exists($redisKey);
        if ($isHasRedis) {
            $oldActiveAt = redis()->get($redisKey);
            $isUpdate    = ($timestamp - $oldActiveAt) >= User::UPDATE_ACTIVE_SECONDS ? true : false;
        }

        return (bool)$isUpdate;
    }

    /**
     * 用户是否更新登录记录
     *
     * @param  int  $userId
     *
     * @return bool
     */
    public function whetherUpdateUserRemainLog(int $userId)
    {
        $redisKey   = sprintf(config('redis_keys.cache.has_remained'), $userId);
        $needUpdate = true;
        $isHasRedis = redis()->exists($redisKey);
        if ($isHasRedis) {
            $needUpdate = false;
        }

        return (bool)$needUpdate;
    }

    /**
     * 更新用户活跃时间
     *
     * @param          $userId
     * @param  int     $timestamp
     * @param  string  $os
     * @param  string  $runVersion
     * @param  string  $language
     *
     * @return ResultReturn
     */
    public function updateUserActiveAt($userId, int $timestamp, $os, $runVersion, $language)
    {
        $redisKey   = sprintf(config('redis_keys.cache.active_at'), $userId);
        $isUpdate   = true;
        $isHasRedis = redis()->exists($redisKey);
        $ttl        = $isHasRedis ? redis()->client()->ttl($redisKey) : 86400;
        if ($isHasRedis) {
            $oldActiveAt = redis()->get($redisKey);
            $isUpdate    = ($timestamp - $oldActiveAt) >= User::UPDATE_ACTIVE_SECONDS;
        }

        if (!$isUpdate) {
            return ResultReturn::failed('不需要更新');
        }
        rep()->user->getQuery()->where('id', $userId)->update(
            ['active_at' => $timestamp, 'language' => $language]);
        if (in_array($os, ['ios', 'android'])) {
            rep()->userDetail->getQuery()->where('user_id', $userId)->update(
                ['os' => $os, 'run_version' => $runVersion]);
        }

        redis()->set($redisKey, $timestamp);
        redis()->client()->expire($redisKey, $ttl);

        pocket()->mongodb->updateMongoActiveAt($userId);

        return ResultReturn::success($userId);
    }

    /**
     * 根据当前时间获得活跃状态的信息
     *
     * @param  int  $activeAt 活跃的时间戳
     *
     * @return array
     */
    public function getUserActiveInfoByActiveAt($activeAt): array
    {
        $data = [
            'active_state'  => 0,
            'active_format' => '',
        ];
        if (!$activeAt) {
            return $data;
        }
        $activeState = 0;
        $diffMinutes = time() - $activeAt;
        $diffHours   = round(($diffMinutes) / (60 * 60), 2);
        if ($diffMinutes <= 10 * 60) {
            $activeAtFormat = trans('messages.online_status');
            $activeState    = 1;
        } else {
            if ($diffHours <= 7 * 24) { //7天前
                $activeAtFormat = Carbon::createFromTimestamp($activeAt)->diffForHumans();
            } else {
                $activeAtFormat = trans('messages.max_offline_status');
            }
        }
        $data['active_state']  = $activeState;
        $data['active_format'] = $activeAtFormat;

        return $data;
    }

    /**
     * 记录用户登录日志
     *
     * @param  int  $userId
     *
     * @return ResultReturn
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function postStatRemainLoginLog(int $userId)
    {
        $now      = time();
        $startAt  = strtotime(date('Y-m-d', $now));
        $redisKey = sprintf(config('redis_keys.cache.has_remained'), $userId);
        if (!redis()->client()->get($redisKey)) {
            $cacheKey = sprintf(config('redis_keys.has_remained_block'), $userId);
            $lock     = new RedisLock(Redis::connection(), 'lock:' . $cacheKey, 3);
            $lock->block(3, function () use ($cacheKey, $redisKey, $now) {
                $endTime = strtotime(date('Y-m-d')) + 86400;
                redis()->client()->set($redisKey, $now);
                redis()->client()->expire($redisKey, $endTime - $now);
            });
        }
        $job = (new StatRemainLoginLogJob($userId, $startAt, $now))->onQueue('common_queue_more_by_pocket');
        dispatch($job);

        return ResultReturn::success([
            'start_at' => $startAt,
            'now'      => $now,
            'user_id'  => $userId
        ]);
    }

    /**
     * 判断用户当前登录是否需要更新Redis中的登录时间,如果要更新,那么初步判断,要更新日活表
     *
     * @param  int  $userId 用户ID
     * @param  int  $toDayTime 今天开始时间
     *
     * @return bool
     */
    public function whetherUpdateOrPostUserLoginAtToRedis(int $userId, int $toDayTime)
    {
        $redisKey = config('redis_keys.auth.user_login_at.key');
        if ($historyLoginAt = redis()->zscore($redisKey, $userId)) {
            if ($historyLoginAt < $toDayTime) {
                return true;
            }

            return false;
        } else {
            return true;
        }
    }

    /**
     * 更新认证Redis中的用户登录时间Zset
     *
     * @param  int  $userId
     * @param  int  $loginTime
     *
     * @return ResultReturn
     */
    public function updateOrPostUserLoginAtToRedis(int $userId, int $loginTime)
    {

        $redisKey = config('redis_keys.auth.user_login_at.key');
        redis()->zadd($redisKey, [$userId => $loginTime]);

        return ResultReturn::success([
            'user_id'  => $userId,
            'not_time' => $loginTime,
        ]);
    }

    /**
     * 个人信息携带用户头像
     *
     * @param  array  $userIds
     *
     * @return \App\Models\BaseModel[]|\App\Models\Model[]|array|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getUserInfoWithAvatar(array $userIds)
    {
        $users = rep()->user->m()
            ->select(['user.id', 'user.uuid', 'number', 'nickname', 'role', 'gender', 'resource.resource'])
            ->leftJoin('resource', 'resource.related_id', 'user.id')
            ->where('resource.related_type', Resource::RELATED_TYPE_USER_AVATAR)
            ->where('resource.deleted_at', 0)
            ->whereIn('user.id', $userIds)
            ->get();
        foreach ($users as $user) {
            $user->setAttribute('avatar', cdn_url($user->resource));
        }

        return $users;
    }

    /**
     * 验证用户提交的 Auth-Token 是否正确
     *
     * @param  string  $authToken
     *
     * @return ResultReturn
     */
    public function verifyAuthTokenPermission($authToken)
    {
        $tokenArr = explode('.', $authToken);
        if (count($tokenArr) != 2) {
            return ResultReturn::failed(trans('messages.need_correct_token'));
        }
        [$uuid, $token] = $tokenArr;
        $authArr = json_decode(aes_encrypt()->decrypt($token), true);
        if (!$authArr) {
            return ResultReturn::failed(trans('messages.token_error'));
        }
        if (!key_exists('id', $authArr) && !key_exists('update', $authArr)
            && !key_exists('delete', $authArr)) {
            return ResultReturn::failed(trans('messages.token_error'));
        }

        if (time() > $authArr['delete']) {
            return ResultReturn::failed(
                trans('messages.need_correct_token'));
        }

        return ResultReturn::success([$uuid, $authArr]);
    }

    /**
     * 附近的人在线优先
     *
     * @param          $userId
     * @param          $gender
     * @param          $page
     * @param          $limit
     * @param          $sort
     * @param          $field
     * @param  string  $cityName
     * @param  int     $isMember
     * @param  string  $version
     * @param  bool    $isTop //是否置顶该用户自己
     *
     * @return mixed
     */
    public function getLbsOnlineUsers(
        $userId,
        $gender,
        $page,
        $limit,
        $sort,
        $field,
        $cityName = "",
        $isMember = 0,
        $version = '1.0.0',
        $isTop = false,
        array $excludeUsersId = []
    ) {
        $cityId = $lng = $lat = 0;
        $area   = pocket()->userDetail->getAreaIdByName($cityName);
        if ($area) {
            $cityId = $area->id;
            $lng    = $area->lng;
            $lat    = $area->lat;
        } else {
            if ($cityName) {
                $cityId = -1;
            }
        }
        if (!$area) {
            $esUser = pocket()->esUser->getUserByUserId($userId);
            if ($esUser && isset($esUser['location']['lat']) && $esUser['location']['lat'] != 0) {
                $lng = $esUser['location']['lon'];
                $lat = $esUser['location']['lat'];
            }
        }
        [$userIds, $lastPage] = pocket()->esUser->getLbsOnlineUsers(
            $page,
            $limit,
            $lng,
            $lat,
            (int)($gender === User::GENDER_WOMEN),
            $gender,
            User::MONGO_LOC_IS_UPLOAD,
            $sort,
            $cityId,
            User::SHOW,
            $isMember,
            $version,
            $excludeUsersId
        );
        if ($isTop && $page == 0) {
            array_unshift($userIds, $userId);
        }
        $orderBy = implode(',', $userIds);
        $users   = rep()->user->m()
            ->select($field)
            ->whereIn('id', $userIds);
        if (!empty($orderBy)) {
            $users->orderBy(DB::raw('FIND_IN_SET(id, "' . $orderBy . '"' . ")"));
        }

        return [$users, $lastPage, $userIds];
    }

    /**
     * 获取某个用户附近的人
     *
     * @param          $userId
     * @param          $gender
     * @param          $page
     * @param          $limit
     * @param          $sort
     * @param          $field
     * @param  string  $cityName
     * @param  int     $isMember
     * @param  string  $version
     *
     * @return mixed
     */
    public function getLbsUsers(
        $userId,
        $gender,
        $page,
        $limit,
        $sort,
        $field,
        $cityName = "",
        $isMember = 0,
        $version = '1.0.0'
    ) {
        $cityId = $lng = $lat = 0;
        $area   = pocket()->userDetail->getAreaIdByName($cityName);
        if ($area) {
            $cityId = $area->id;
            $lng    = $area->lng;
            $lat    = $area->lat;
        } else {
            if ($cityName) {
                $cityId = -1;
            }
        }
        if (!$area) {
            $esUser = pocket()->esUser->getUserByUserId($userId);
            if ($esUser && isset($esUser['location']['lat']) && $esUser['location']['lat'] != 0) {
                $lng = $esUser['location']['lon'];
                $lat = $esUser['location']['lat'];
            }
        }
        $userIds = pocket()->esUser->getSearchLocationUsersIdByUserId($page * $limit,
            $limit,
            $lng,
            $lat,
            (int)($gender === User::GENDER_WOMEN),
            $gender,
            User::MONGO_LOC_IS_UPLOAD,
            $sort,
            $cityId,
            User::SHOW,
            $isMember,
            $version
        );
        $orderBy = implode(',', $userIds);
        $users   = rep()->user->m()
            ->select($field)
            ->whereIn('id', $userIds);
        if (!empty($orderBy)) {
            $users->orderBy(DB::raw('FIND_IN_SET(id, "' . $orderBy . '"' . ")"));
        }

        return $users;
    }

    /**
     * 生成唯一8位的number
     */
    public function genNumberByRedis()
    {
        $number = 0;
        for ($i = 0; $i <= 100; $i++) {
            try {
                $number = random_int(1000000, 99999999);
            } catch (\Exception $e) {
                continue;
            }
            $result = redis()->client()->sAdd(config('redis_keys.number.cache_number.key'), $number);
            if (!$result) {
                continue;
            }
            $exists = rep()->user->m()->where('number', $number)->exists();
            if ($exists) {
                continue;
            }

            return $number;
        }

        return $number;
    }

    /**
     * 获得不出现在附近的人的用户id
     *
     * @return array
     */
    public function getLBSNotUserId(): array
    {
        $iosAuditUUIDS = pocket()->util->getIosAuditUserListUUIds();

        return rep()->user->getByUUids($iosAuditUUIDS, 'id')->pluck('id')->toArray();
    }

    /**
     *
     * 后台获取用户
     *
     * @param $uuid
     *
     * @return \App\Models\BaseModel|\App\Models\Model|\Illuminate\Database\Eloquent\Builder|Model|\Illuminate\Http\JsonResponse|object
     */
    public function adminGetUser($uuid)
    {
        $user = rep()->user->m()
            ->select(['id', 'uuid', 'nickname', 'mobile', 'gender', 'birthday', 'role', 'created_at'])
            ->with([
                'userDetail' => function ($query) {
                    $query->select(['user_id', 'channel', 'client_id', 'os', 'intro', 'region', 'height', 'weight']);
                }
            ])
            ->where('uuid', $uuid)
            ->first();
        if (!$user) {
            return api_rr()->notFoundUser();
        }
        $reported = rep()->report->m()
            ->where('related_id', $user->id)
            ->count();
        $member   = rep()->member->m()
            ->where('user_id', $user->id)
            ->where(DB::raw('start_at + duration'), '>', time())
            ->first();
        $userRole = $member ? 'VIP，' . implode(',', array_map(function ($key) {
                return User::ROLE_ARR[$key];
            }, explode(',', $user->role))) : implode(',', array_map(function ($key) {
            return User::ROLE_ARR[$key];
        }, explode(',', $user->role)));
        $videos   = rep()->resource->m()
            ->where('related_id', $user->id)
            ->where('related_type', Resource::RELATED_TYPE_USER_PHOTO)
            ->where('type', Resource::TYPE_VIDEO)
            ->get();
        foreach ($videos as $video) {
            $video->setAttribute('cover', $video->fake_cover . '|imageMogr2/blur/200x50');
        }

        pocket()->user->appendToUser($user,
            ['job', 'avatar', 'photo' => $user]);
        pocket()->user->appendRealWeChatToUser($user);
        $user->setAttribute('report_count', $reported);
        $user->setAttribute('user_role', $userRole);
        $user->setAttribute('video', $videos);
        $user->setAttribute('is_charm_girl', (boolean)in_array(Role::KEY_CHARM_GIRL, explode(',', $user->role)));
        $user->setAttribute('create_time', (string)$user->created_at);

        return $user;
    }

    /**
     * 拉黑人脸
     *
     * @param $user
     *
     * @return ResultReturn
     */
    public function faceBlack($user)
    {
        $now         = time();
        $uuid        = $user->uuid;
        $personExist = rep()->sfaceRecord->m()->where('person_id', $uuid)->first();
        if (!$personExist) {
            pocket()->aliGreen->sfaceAddPerson($uuid, SfaceRecord::GROUP_FACE_BLACK);
        }
        $face = rep()->facePic->m()->where('user_id', $user->id)->get();
        if (count($face) == 0) {
            return ResultReturn::failed('当前用户没有底图');
        }
        $urls   = $face->pluck('base_map')->toArray();
        $result = pocket()->aliGreen->sfaceAddFace($uuid, $urls);
        if ($result->getStatus() == false) {
            return ResultReturn::failed('添加人脸失败');
        }
        $data       = $result->getData();
        $items      = $data['data']['faceImageItems'];
        $createData = [];
        foreach ($items as $key => $value) {
            if ($value['success'] == true) {
                $createData[] = [
                    'person_id'  => $uuid,
                    'group_id'   => SfaceRecord::GROUP_FACE_BLACK,
                    'face_id'    => $value['faceId'],
                    'url'        => $urls[$key],
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }
        }
        $blackData = [];
        foreach ($face as $item) {
            $blackData[] = [
                'related_type' => Blacklist::RELATED_TYPE_FACE,
                'related_id'   => $item->id,
                'user_id'      => $user->id,
                'reason'       => '',
                'remark'       => '',
                'expired_at'   => 0,
                'created_at'   => $now,
                'updated_at'   => $now
            ];
        }
        DB::transaction(function () use ($createData, $blackData, $user) {
            rep()->sfaceRecord->m()->insert($createData);
            rep()->blacklist->m()->insert($blackData);
            rep()->facePic->m()->where('user_id', $user->id)->update(['status' => FacePic::STATUS_BLACK]);
        });

        return ResultReturn::success([]);
    }

    /**
     * 更新用户昵称
     *
     * @param  int     $userId 用户id
     * @param  string  $nickname 用户昵称
     *
     * @return ResultReturn
     * @throws GuzzleException
     */
    public function updateUserNickname(int $userId, string $nickname)
    {
        $user = rep()->user->getById($userId);
        if (!$user) {
            return ResultReturn::failed(trans('messages.not_found_user'));
        }
        rep()->user->m()->where('id', $userId)->update(['nickname' => $nickname]);
        pocket()->netease->userUpdateUinfo($user->uuid, $nickname);

        return ResultReturn::success([
            'nickname' => $nickname,
            'user_id'  => $userId
        ]);
    }

    /**
     * 更新用户头像
     *
     * @param  int  $userId 用户id
     *
     * @return ResultReturn
     * @throws GuzzleException
     */
    public function updateUserDestroyAvatar(int $userId)
    {
        $user = rep()->user->getById($userId);
        if (!$user) {
            return ResultReturn::failed(trans('messages.not_found_user'));
        }
        $avatarArr  = config('custom.destroy_user_avatar');
        $avatarPath = $avatarArr['path'];
        rep()->resource->m()
            ->where('related_id', $userId)
            ->where('related_type', Resource::RELATED_TYPE_USER_AVATAR)
            ->delete();
        rep()->resource->m()->create([
            'uuid'         => pocket()->util->getSnowflakeId(),
            'type'         => Resource::TYPE_IMAGE,
            'related_id'   => $userId,
            'related_type' => Resource::RELATED_TYPE_USER_AVATAR,
            'resource'     => $avatarPath,
            'height'       => $avatarArr['height'],
            'width'        => $avatarArr['width']
        ]);
        pocket()->netease->userUpdateUinfo($user->uuid, '', cdn_url($avatarPath));

        return ResultReturn::success([
            'user_id' => $userId,
            'avatar'  => cdn_url($avatarPath)
        ]);
    }

    /**
     * 关注公众号后更新用户审核状态
     *
     * @param $userId
     *
     * @return ResultReturn
     */
    public function changeReviewStatus($userId)
    {
        $userReview = rep()->userReview->m()
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->first();
        if ($userReview) {
            if ($userReview->check_status == UserReview::CHECK_STATUS_FOLLOW_WECHAT) {
                $userReview->update(['check_status' => UserReview::CHECK_STATUS_DELAY]);
            }
            if ($userReview->check_status == UserReview::CHECK_STATUS_FOLLOW_WECHAT_FACE) {
                $userReview->update(['check_status' => UserReview::CHECK_STATUS_BLACK_DELAY]);
            }
        }

        return ResultReturn::success([]);
    }

    /**
     * 给男生用户自动添加谁看过我
     *
     * @param $userId
     *
     * @return ResultReturn
     * @throws \Exception
     */
    public function addVisitedToUser($userId)
    {
        $now      = time();
        $visited  = rep()->userVisit->m()->where('related_id', $userId)
            ->where('related_type', UserVisit::RELATED_TYPE_INTRODUCTION)
            ->get()->pluck('user_id')->toArray();
        $isMember = rep()->member->getUserValidMember($userId);
        if (!$isMember && count($visited) < 20) {
            if (count($visited) == 0) {
                $count = rand(3, 5);
            } else {
                $count = rand(2, 5);
            }
            $user         = rep()->user->getById($userId);
            $visitIds     = pocket()->esUser->searchVisited($userId, $count, $visited);
            $visitUsers   = rep()->user->m()->whereIn('id', $visitIds)->get();
            $visitAvatars = rep()->resource->m()
                ->select(['related_id', 'resource'])
                ->whereIn('related_id', $visitIds)
                ->where('related_type', Resource::RELATED_TYPE_USER_AVATAR)
                ->get();
            $visits       = [];
            $visitAvatar  = [];
            foreach ($visitUsers as $visitUser) {
                $visits[$visitUser->id] = $visitUser;
            }
            foreach ($visitAvatars as $avatar) {
                $visitAvatar[$avatar->related_id] = cdn_url($avatar->resource) . '?vframe/png/offset/0/h/200|imageMogr2/blur/10x10';
            }
            $createData = [];

            $distanceTemplate = trans('messages.visit_my_homepage_tmpl', [], $user->language);
            foreach ($visitIds as $visitId) {
                $createData[] = [
                    'user_id'      => $visitId,
                    'related_type' => UserVisit::RELATED_TYPE_INTRODUCTION,
                    'related_id'   => $userId,
                    'visit_time'   => $now - rand(0, 100),
                    'created_at'   => $now,
                    'updated_at'   => $now
                ];
                $extension    = ['option' => ['badge' => false]];
                //                $extension    = ['option' => ['push' => false, 'badge' => false]];
                $distance   = pocket()->user->getDistanceUsers($visitId, $userId, true);
                $userDetail = rep()->userDetail->m()->where('user_id', $userId)->first();
                if (version_compare($userDetail->run_version, '2.1.0', '>=')) {
                    $data = [
                        'type' => NeteaseCustomCode::USER_VISITED,
                        'data' => [
                            'message' => sprintf($distanceTemplate, $distance),
                            'avatar'  => key_exists($visitId, $visitAvatar) ? $visitAvatar[$visitId] : ''
                        ]
                    ];
                    pocket()->common->commonQueueMoreByPocketJob(
                        pocket()->netease,
                        'msgSendCustomMsg',
                        [config('custom.little_helper_uuid'), $user->uuid, $data, $extension]
                    );
                }
            }
            rep()->userVisit->m()->insert($createData);
        }

        return ResultReturn::success([]);
    }

    /**
     * 获取某个用户的经纬度
     *
     * @param $userId
     *
     * @return int[]
     */
    public function getLocationByUserId($userId)
    {
        $lng    = $lat = 0;
        $esUser = pocket()->esUser->getUserByUserId($userId);
        if ($esUser && isset($esUser['location']['lat']) && $esUser['location']['lat'] != 0) {
            $lng = $esUser['location']['lon'];
            $lat = $esUser['location']['lat'];
        }

        return [$lng, $lat];
    }

    /**
     * 获取男生新版邀请弹框数据
     *
     * @param  User  $user
     *
     * @return ResultReturn
     */
    public function manInvitePopup(User $user)
    {
        $inviteTestRecord = rep()->userAb->getUserInviteTestRecord($user);
        $inviteTestIsB    = $inviteTestRecord && $inviteTestRecord->inviteTestIsB();
        if ($this->getManInvitePopupStatus($user, $inviteTestRecord)) {
            $response = [
                'status' => true,
                'type'   => $inviteTestIsB ? 'discount_invite' : 'member_invite'
            ];

            if ($inviteTestIsB) {
                $response['invite_count'] = rep()->task->getQuery()->where('user_id', $user->id)
                    ->where('related_type', Task::RELATED_TYPE_MEMBER_DISCOUNT)
                    ->where('status', Task::STATUS_DEFAULT)->count();
            }

            return ResultReturn::success($response);
        }

        return ResultReturn::failed('');
    }

    /**
     * 获取跳转的启动弹框
     *
     * @param  User  $user
     *
     * @return ResultReturn
     */
    public function getJumpUrlPopup(User $user)
    {
        if (app()->environment('production') && $user->role == 'auth_user,charm_girl,user') {
            $redisKey = sprintf(config('redis_keys.charm_popup'), $user->id);
            if (redis()->client()->get($redisKey)) {
                return ResultReturn::failed('');
            }
            $response = [
                [
                    'type'    => 100,
                    'value'   => 'https://web.wqdhz.com/banner?url=https://cdn-dev1.wqdhz.com/assets/images/purify-env.jpeg',
                    'picture' => 'http://file-dev.wqdhz.com/uploads/face_auth/d5cf1c706e7d36d12d4f355fa4d264c0.png',
                ]
            ];
            redis()->client()->set($redisKey, true);
            redis()->client()->expire($redisKey, 86400);

            return ResultReturn::success($response);
        }
        if (!app()->environment('production')) {
            $response = [
                [
                    'type'    => 100,
                    'value'   => 'https://www.baidu.com',
                    'picture' => cdn_url('uploads/common/jump_url_test.png')
                ],
                [
                    'type'    => 200,
                    'value'   => 'https://www.baidu.com',
                    'picture' => cdn_url('uploads/common/jump_url_test.png')
                ],
            ];

            $response = [
                [
                    'type'    => 100,
                    'value'   => 'https://web.wqdhz.com/banner?url=https://cdn-dev1.wqdhz.com/assets/images/purify-env.jpeg',
                    'picture' => 'http://file-dev.wqdhz.com/uploads/face_auth/d5cf1c706e7d36d12d4f355fa4d264c0.png',
                ]
            ];

            return ResultReturn::success($response);
        }

        return ResultReturn::failed('');
    }

    /**
     * 判断新版邀请弹框是否需要弹
     *
     * @param  User    $user
     * @param  UserAb  $inviteTestRecord
     *
     * @return bool
     */
    protected function getManInvitePopupStatus(User $user, $inviteTestRecord)
    {
        $popupStatus   = false;
        $loginLogs     = rep()->statRemainLoginLog->getQuery()->select('user_id',
            DB::raw('UNIX_TIMESTAMP(FROM_UNIXTIME(login_at,\'%Y-%m-%d\')) as date'))
            ->where('user_id', $user->id)->groupBy('date')->orderBy('date')->get();
        $inviteTestIsB = $inviteTestRecord && $inviteTestRecord->inviteTestIsB();
        $todayStartAt  = strtotime(date('Y-m-d'));


        if ($inviteTestIsB && (($loginLogs->count() == 1 && $loginLogs->last()->date < $todayStartAt)
                || ($loginLogs->count() == 2 && $loginLogs->last()->date == $todayStartAt))) {
            $popupStatus = $this->checkInvitePopupEnd($user);
        } elseif (!$inviteTestIsB && $loginLogs->count() >= 3) {
            $discount = rep()->discount->getQuery()->where('user_id', $user->id)
                ->where('related_type', Discount::RELATED_TYPE_GIVING)->first();

            if ($discount) {
                $discountFailureAt = $discount->done_at ? $discount->done_at : $discount->expired_at;
                $failureStartAt    = strtotime(date('Y-m-d', $discountFailureAt));
                $expiredNextDay    = $loginLogs->where('date', '>=', $failureStartAt)->first();
                if ($expiredNextDay && $expiredNextDay->date == $todayStartAt
                    && $discountFailureAt < time()) {
                    $popupStatus = $this->checkInvitePopupEnd($user);
                }
            } elseif ($loginLogs->count() == 3) {
                $tradePay       = rep()->tradePay->getQuery()->where('user_id', $user->id)
                    ->where('related_type', TradePay::RELATED_TYPE_RECHARGE_VIP)
                    ->where('done_at', '>', 0)->first();
                $expiredNextDay = $loginLogs->where('date', '>=', $todayStartAt)->first();
                if ($tradePay && $expiredNextDay && strtotime(date('Y-m-d', $tradePay->done_at))
                    <= $expiredNextDay->date) {
                    $popupStatus = $this->checkInvitePopupEnd($user);
                }
            }
        }

        return $popupStatus;
    }

    /**
     * 检查邀请弹框是否已经弹过
     *
     * @param  User  $user
     *
     * @return bool
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    protected function checkInvitePopupEnd(User $user)
    {
        $cacheKey = config('redis_keys.cache.invite_popup_cache');
        if (!Redis::exists($cacheKey)) {
            $lock = new RedisLock(Redis::connection(), 'lock:' . $cacheKey, 3);
            $lock->block(3, function () use ($cacheKey, $user) {
                if (!Redis::exists($cacheKey)) {
                    Redis::zadd($cacheKey, time(), $user->id);
                    Redis::expire($cacheKey, strtotime('+1 days',
                            strtotime(date('Y-m-d'))) - time() - 1);
                }
            });

            return true;
        } elseif (!($timestamp = Redis::zscore($cacheKey, $user->id))) {
            Redis::zadd($cacheKey, time(), $user->id);

            return true;
        }

        return false;
    }

    /**
     * 获得活跃优先排除的用户ia
     *
     * @param  int  $userId
     *
     * @return array
     */
    public function getExistsFeedLbsUsersId(int $userId): array
    {
        $redisKey = sprintf(config('redis_keys.cache.feed_lbs_exists_user.key'), $userId);
        if (!redis()->client()->exists($redisKey)) {
            return [];
        }
        $hasUsersId = redis()->client()->zRangeByScore($redisKey, 0, 99999999999, ['withscores' => true]);

        return array_keys($hasUsersId);
    }


    /**
     * 删除活跃优先排除的用户ia
     *
     * @param  int  $userId
     *
     * @return ResultReturn
     */
    public function delExistsFeedLbsUsersId(int $userId)
    {
        $redisKey = sprintf(config('redis_keys.cache.feed_lbs_exists_user.key'), $userId);
        redis()->client()->del($redisKey);

        return ResultReturn::success(true);
    }

    /**
     * 写入活跃优先排除的用户ia
     *
     * @param  int    $userId
     * @param         $timestamp
     * @param  array  $usersId
     *
     * @return ResultReturn
     */
    public function postExistsFeedLbsUsersId(int $userId, int $timestamp, array $usersId)
    {
        $redisKey = sprintf(config('redis_keys.cache.feed_lbs_exists_user.key'), $userId);
        $ttl      = redis()->client()->exists($redisKey) ?
            redis()->client()->ttl($redisKey) :
            config('redis_keys.cache.feed_lbs_exists_user.ttl');
        foreach ($usersId as $userId) {
            redis()->zadd($redisKey, [$userId => $timestamp]);
        }
        redis()->client()->expire($redisKey, $ttl);

        return ResultReturn::success([
            'user_id'   => $userId,
            'timestamp' => $timestamp,
            'usersId'   => $userId
        ]);
    }

    /**
     * 通过authToken获得用户
     *
     * @param  string  $authToken
     *
     * @return \App\Models\BaseModel|\App\Models\Model|\Illuminate\Database\Eloquent\Builder|Model|object|null
     */
    public function getUserByAuthToken(string $authToken, array $fields = ['*'])
    {
        $uuidArr = explode('.', $authToken);
        $uuid    = $uuidArr[0];
        $user    = rep()->user->getByUUid($uuid, $fields);

        return $user;
    }


    /**
     * 从mongo中获取feed接口数据
     *
     * @param $users
     * @param $append
     * @param $version
     *
     * @return array
     */
    public function getFeedUsersByMongo($users, $append, $version)
    {
        $userIds         = $users->pluck('id')->toArray();
        $uuids           = $users->pluck('uuid')->toArray();
        $mongoUsers      = mongodb('user_info')->whereIn('_id', $userIds)->get();
        $mongoUserIds    = $mongoUsers->pluck('_id')->toArray();
        $notMongoUserIds = array_diff($userIds, $mongoUserIds);
        if (count($notMongoUserIds) > 0) {
            foreach ($notMongoUserIds as $notMongoUserId) {
                $job = (new UpdateUserInfoToMongoJob($notMongoUserId))
                    ->onQueue('update_user_info_to_mongo');
                dispatch($job);
            }
        }
        $users = $users->filter(function ($item) use ($notMongoUserIds) {
            if (in_array($item->id, $notMongoUserIds, true)) {
                return $item;
            }
        })->values();
        $this->appendToUsers($users, $append);
        $mongoUsers = $this->appendMongoToUsers($mongoUsers, $append, $version);
        $users      = collect(array_merge($users->toArray(), $mongoUsers->toArray()));
        $data       = [];
        foreach ($uuids as $uuid) {
            $userByUUID = $users->where('uuid', $uuid)->first();
            if (!$userByUUID) {
                continue;
            }
            $data[] = $userByUUID;
        }

        return $data;
    }

    /**
     * 往User Mongo中 collection中获取数据
     *
     * @param          $mongoUsers
     * @param  array   $property
     * @param  string  $version
     *
     * @return \App\Models\BaseModel[]|\App\Models\Model[]|array|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function appendMongoToUsers($mongoUsers, $property = [], $version = '1.0.0')
    {
        $mongoUserIds   = $mongoUsers->pluck('_id')->toArray();
        $users          = rep()->user->m()
            ->select(['user.id', 'uuid', 'number', 'nickname', 'gender', 'birthday', 'user.active_at', 'role'])
            ->whereIn('id', $mongoUserIds)
            ->get();
        $filterProperty = pocket()->util->conversionAppendToUserArgs($property);
        foreach ($users as $user) {
            $mongoUserInfoDetail = $mongoUsers->where('_id', $user->id)->first();
            if (!$mongoUserInfoDetail) {
                continue;
            }
            $mongoUserInfo = $mongoUserInfoDetail['user_info'] ?? [];
            /** append微信 */
            if (array_key_exists('wechat', $filterProperty)) {
                $user->setAttribute('wechat', $mongoUserInfo['wehcat']);
            }
            /** append头像 */
            if (array_key_exists('avatar', $filterProperty)) {
                $user->setAttribute('avatar',
                    key_exists('avatar', $mongoUserInfo) ? cdn_url($mongoUserInfo['avatar']) : '');
            }
            /** append相册 */
            if (array_key_exists('album', $filterProperty)) {
                $photos = $mongoUserInfo['photo'];
                if (count($photos) > 0) {
                    foreach ($photos as $key => $photo) {
                        $mongoUserInfo['photo'][$key]['preview']     =
                            isset($mongoUserInfo['photo'][$key]['preview']) ? cdn_url($mongoUserInfo['photo'][$key]['preview']) : "";
                        $mongoUserInfo['photo'][$key]['fake_cover']  =
                            isset($mongoUserInfo['photo'][$key]['fake_cover']) ?
                                cdn_url($mongoUserInfo['photo'][$key]['fake_cover']) : "";
                        $mongoUserInfo['photo'][$key]['small_cover'] = isset($mongoUserInfo['photo'][$key]['small_cover']) ?
                            cdn_url($mongoUserInfo['photo'][$key]['small_cover']) : "";
                        $mongoUserInfo['photo'][$key]['cover']       = isset($mongoUserInfo['photo'][$key]['cover']) ?
                            cdn_url($mongoUserInfo['photo'][$key]['cover']) : "";
                    }
                }
                if (version_compare($version, '2.1.0', '<')) {
                    $user->setAttribute('photo', $mongoUserInfo['photo']);
                } else {
                    $user->setAttribute('photo', []);
                }
                $user->setAttribute('has_video', $mongoUserInfo['has_video']);
                $user->setAttribute('photo_count', count($mongoUserInfo['photo']));
            }
            /** append Job相关 */
            if (array_key_exists('job', $filterProperty)) {
                $user->setAttribute('job', count($mongoUserInfo['job']) > 0 ? $mongoUserInfo['job'] : (object)[]);
            }
            /** append网易 */
            if (array_key_exists('netease', $filterProperty)) {
                $user->setAttribute('netease', $mongoUserInfo['netease']);
            }
            /** append基础会员相关 */
            if (array_key_exists('member', $filterProperty)) {
                $user->setAttribute('member', $mongoUserInfo['member']);
            }
            /** append认证用户相关 */
            if (array_key_exists('auth_user', $filterProperty)) {
                $user->setAttribute('auth_user', $mongoUserInfo['auth_user']);
            }
            /** append魅力女生相关 */
            if (array_key_exists('charm_girl', $filterProperty)) {
                $user->setAttribute('charm_girl', $mongoUserInfo['charm_girl']);
            }
            /** append用户详情相关 */
            if (array_key_exists('user_detail', $filterProperty)) {
                $user->setAttribute('user_detail', $mongoUserInfo['user_detail']);
            }

        }

        /** append用户和某个坐标的距离 */
        if (array_key_exists('distance', $filterProperty)) {
            pocket()->user->appendDistanceToUsers($users, $filterProperty['distance']);
        }
        /** append用户的活跃状态 */
        if (array_key_exists('active', $filterProperty)) {
            pocket()->user->appendActiveToUsers($users);
        }
        /** append用户的昵称打* */
        if (array_key_exists('blur_nickname', $filterProperty)) {
            pocket()->user->appendBlurNickNameToUsers($users);
        }
        /** append用户的昵称打* */
        if (array_key_exists('blur_avatar', $filterProperty)) {
            pocket()->user->appendBlurAvatarToUsers($users, $filterProperty['blur_avatar']);
        }

        return $users;
    }

    public function checkMongoKey($mongoUserInfo)
    {
        if (!isset($mongoUserInfo['wechat']) ||
            !isset($mongoUserInfo['avatar']) ||
            !isset($mongoUserInfo['album']) ||
            !isset($mongoUserInfo['job']) ||
            !isset($mongoUserInfo['netease']) ||
            !isset($mongoUserInfo['member']) ||
            !isset($mongoUserInfo['auth_user']) ||
            !isset($mongoUserInfo['charm_girl']) ||
            !isset($mongoUserInfo['user_detail'])
        ) {
            return false;
        }

        return true;
    }

    /**
     * 创建一个新用户
     *
     * @param  string|int  $certificate
     * @param  string      $authFiled
     * @param  string      $clientId
     * @param  string      $channel
     *
     * @return \App\Models\User
     * @throws GuzzleException
     */
    public function createNewUser($certificate, $authFiled, $clientId, $channel)
    {
        $userRole     = rep()->role->getUserRole();
        $neteaseToken = md5(Str::random(32));
        $clientName   = user_agent()->appName;
        $clientResult = separation_user_agent($clientName, '');
        if ($clientResult['client_name'] == '') {
            $clientName = 'xiaoquan_ghost';
        }

        [$user, $regSchedule] = DB::transaction(
            function () use ($authFiled, $certificate, $clientId, $channel, $userRole, $neteaseToken, $clientName) {
                $user = rep()->user->m()->create([
                    'uuid'     => pocket()->util->getSnowflakeId(),
                    'number'   => rand(1, 19999),
                    'nickname' => 'XiaoQuan_' . Str::random(6),
                    $authFiled => $certificate,
                    'language' => 'zh',
                ]);

                $regSchedule                   = version_compare(user_agent()->clientVersion, '2.0.0', ">=") ?
                    UserDetail::REG_SCHEDULE_PASSWORD : UserDetail::REG_SCHEDULE_GENDER;
                $userDetailData                = [
                    'user_id'      => $user->id,
                    'client_name'  => $clientName,
                    'client_id'    => $clientId,
                    'channel'      => $channel,
                    'reg_schedule' => $regSchedule,
                    'invite_code'  => pocket()->userDetail->getInviteCodeByUserId($user->id)
                ];
                $userDetailData['reg_os']      = $userDetailData['os'] = user_agent()->os ?: UserDetail::REG_OS_ANDROID;
                $userDetailData['reg_version'] = $userDetailData['run_version'] = user_agent()->clientVersion;
                rep()->userDetail->m()->create($userDetailData);

                rep()->userAb->createMemberPriceTest($user);
                rep()->wallet->m()->create(['user_id' => $user->id]);
                rep()->userDetailExtra->m()->create(['user_id' => $user->id]);
                $userAuthData = [
                    'user_id' => $user->id,
                    'type'    => UserAuth::TYPE_NETEASE_TOKEN,
                    'secret'  => $neteaseToken
                ];
                rep()->userAuth->m()->create($userAuthData);

                $userRole && rep()->userRole->m()->create(['user_id' => $user->id, 'role_id' => $userRole->id]);
                $user->update(['role' => Role::KEY_USER, 'birthday' => strtotime('2000-01-01 00:00:00')]);
                $inviteResp = pocket()->inviteRecord->checkAndBindUserInviteRecord($user, $authFiled);
                if ($inviteResp->getStatus()) {
                    $user->setAttribute('invite_channel', 1);
                }

                return [$user, $regSchedule];
            });

        $neteaseResp = pocket()->netease->userCreate($user->uuid, $user->nickname, $neteaseToken, '');
        if (!$neteaseResp->getStatus()) {
            pocket()->common->commonQueueMoreByPocketJob(pocket()->netease, 'userCreate',
                [$user->uuid, $user->nickname, $neteaseToken, '']);
        }

        return $this->buildNewUserInfo($user, $regSchedule, $neteaseToken, user_agent()->clientVersion);
    }

    /**
     * @param  User    $user
     * @param  int     $regSchedule
     * @param  string  $neteaseToken
     *
     * @return User
     */
    private function buildNewUserInfo(User $user, int $regSchedule, string $neteaseToken, string $clientVersion)
    {
        $user->setAttribute('gender', 0)->setAttribute('hide', 0)->setAttribute('job', (object)[])
            ->setAttribute('tags', [])->setAttribute('hobbys', [])
            ->setAttribute('follow_count', 0)->setAttribute('followed_count', 0)
            ->setAttribute('account_state', 'normal')->setAttribute('has_set_password', 0)
            ->setAttribute('avatar', '')->setAttribute('has_video', false)
            ->setAttribute('is_register', 1);

        $user->setAttribute('wechat', ['number' => '', 'status' => '', 'lock' => false]);
        $user->setAttribute('netease', ['accid' => $user->uuid, 'token' => $neteaseToken]);
        $user->setAttribute('follow_of', ['is_follow' => false, 'url' => '', 'push_msg_switch' => false]);
        $user->setAttribute('auth_user', ['status' => false])->setAttribute('member',
            ['status' => false])->setAttribute('charm_girl', ['status' => false]);
        $userDetail = new UserDetail([
            'intro'        => '',
            'region'       => '神秘星球',
            'height'       => 0,
            'weight'       => 0,
            "reg_schedule" => $regSchedule
        ]);

        $user->setRelation('userDetail', $userDetail);
        $user->setAttribute('user_detail_extra', [
            'emotion'   => null,
            'child'     => null,
            'education' => null,
            'income'    => null,
            'figure'    => null,
            'smoke'     => null,
            'drink'     => null,
        ]);

        pocket()->account->appendToQaToUser($user, $clientVersion);
        pocket()->account->appendUserWithdrawUrlToUser($user);
        pocket()->account->appendInviteInfoToUser($user);

        return $user;
    }

    /**
     * 获取用户当前审核状态
     *
     * @param  int  $userId
     *
     * @return array
     */
    public function getUserCheckStatus($userId)
    {
        $wechatCheck      = rep()->wechat->m()->where('user_id', $userId)->orderByDesc('id')->first();
        $reviewCheck      = rep()->userReview->m()->where('user_id', $userId)->orderByDesc('id')->first();
        $wechatCheckCount = rep()->wechat->m()->where('user_id', $userId)
            ->where('check_status', Wechat::STATUS_PASS)->count();
        $reviewCheckCount = rep()->userReview->m()->where('user_id', $userId)
            ->where('check_status', UserReview::CHECK_STATUS_PASS)->count();

        $statusFailReason = '';
        if (!$wechatCheck || !$reviewCheck) {
            $status = 3;
        } elseif (($wechatCheck->check_status == Wechat::STATUS_PASS || $wechatCheckCount > 0)
            && ($reviewCheck->check_status == UserReview::CHECK_STATUS_PASS || $reviewCheckCount > 0)) {
            $status = 1;
        } elseif ($wechatCheck->check_status == Wechat::STATUS_FAIL
            || $reviewCheck->check_status == UserReview::CHECK_STATUS_FAIL) {
            $status           = 2;
            $statusFailReason = $reviewCheck->reason;
        }

        return [$status ?? 0, $statusFailReason];
    }

    /**
     * 撤回某个人的消息
     *
     * @param $userId
     *
     * @return ResultReturn
     * @throws GuzzleException
     */
    public function recallUserMsg($userId)
    {
        $k = 1;
        while (true) {
            $userMsg = pocket()->esImChat->searchImChatSend($userId, 0, 0, 0, "", [], 3000, $k);
            $msgData = $userMsg->getData();
            if (!$msgData) {
                break;
            }
            $data = $msgData['data'];
            $now  = time();
            foreach ($data as $item) {
                $sendId    = $item['send_id'];
                $receiveId = $item['receive_id'];
                $sendAt    = $item['send_at'];
                $bodyArr   = (array)json_decode($item['body']);
                if (!key_exists('msgidServer', $bodyArr) ||
                    !key_exists('msgTimestamp', $bodyArr) ||
                    !key_exists('fromAccount', $bodyArr) ||
                    !key_exists('to', $bodyArr)) {
                    continue;
                }
                $msgId     = $bodyArr['msgidServer'];
                $timeStamp = $bodyArr['msgTimestamp'];
                $from      = $bodyArr['fromAccount'];
                $to        = $bodyArr['to'];
                $result    = pocket()->netease->recall($msgId, $timeStamp, $from, $to);
                $mongoData = [
                    'send_id'    => $sendId,
                    'receive_id' => $receiveId,
                    'send_at'    => $sendAt,
                    'recall_at'  => $now,
                    'body'       => $bodyArr
                ];
                mongodb('message_recall')->insert($mongoData);
            }
            $k++;
        }

        return ResultReturn::success(['user_id' => $userId]);
    }

    /**
     * 重置用户惩罚
     *
     * @param $userId
     *
     * @return ResultReturn
     */
    public function resetUserPunishment($userId)
    {
        $userMongo = mongodb('user_info')->where('_id', $userId);
        $mongo     = $userMongo->first();
        if ($mongo) {
            $userMongo->update(['mark' => 0, 'refresh_mark' => 0]);
        } else {
            $userMongo->insert(['_id' => $userId, 'mark' => 0, 'refresh_mark' => 0]);
        }

        return ResultReturn::success([]);
    }

    /**
     * 获得隐藏的usersId
     *
     * @return array
     */
    public function getHideUserByRedis(): array
    {
        $redisKey = config('redis_keys.hide_users.key');

        return redis()->client()->smembers($redisKey);
    }

    /**
     * 同步冷起数据
     *
     * @param $userId
     *
     * @throws GuzzleException
     */
    public function syncColdStartUser($userId)
    {
        $user = DB::table('user')->select('id', 'uuid', 'number', 'nickname', 'birthday',
            'role', 'gender', 'language', 'active_at', 'mobile', 'email', 'area', 'charm_girl_at')
            ->where('id', $userId)->first();
        if (!in_array(User::ROLE_CHARM_GIRL, explode(',', $user->role))) {
            return ResultReturn::failed('只能魅力女生才能同步');
        }

        $userDetail  = DB::table('user_detail')->select('intro', 'height', 'weight',
            'region', 'reg_schedule', 'lat', 'lng', 'client_id')
            ->where('user_id', $user->id)->first();
        $detailExtra = DB::table('user_detail_extra')->where('user_id', $user->id)
            ->select('education', 'figure', 'smoke', 'drink')->first();
        $tagIds      = array_values((array)$detailExtra);
        $tags        = DB::table('tag')->select('id', 'uuid')->whereIn('id', $tagIds)->get();

        $detailExtraData = [];
        if ($tags->isNotEmpty()) {
            $tagsMapping = [];
            foreach ($tags as $tag) $tagsMapping[$tag->id] = $tag->uuid;

            foreach ($detailExtra as $index => $item) {
                if (key_exists($item, $tagsMapping)) {
                    $detailExtraData[$index] = $tagsMapping[$item];
                }
            }
        }

        $hobbies = rep()->userTag->getQuery()->join('tag', 'user_tag.tag_id', 'tag.id')
            ->where('user_tag.user_id', $user->id)->where('tag.type', Tag::TYPE_HOBBY)
            ->pluck('tag.uuid')->toArray();

        $userJob         = rep()->userJob->getQuery()->select('job.uuid')
            ->join('job', 'job.id', 'user_job.job_id')
            ->where('user_id', $user->id)->orderBy('user_job.id', 'desc')->first();
        $job             = optional($userJob)->uuid;
        $resources       = DB::table('resource')->select('id', 'uuid', 'related_type',
            'type', 'resource', 'height', 'width', 'sort')->where('related_id', $user->id)
            ->whereIn('related_type',
                [Resource::RELATED_TYPE_USER_PHOTO, Resource::RELATED_TYPE_USER_AVATAR])
            ->where('deleted_at', 0)->get();
        $resourceMapping = [];
        foreach ($resources as $resource) $resourceMapping[$resource->id] = $resource->uuid;
        $userPhotos = DB::table('user_photo')->select('resource_id', 'related_type', 'status')
            ->where('user_id', $user->id)->where('deleted_at', 0)->get();
        foreach ($userPhotos as $index => $userPhoto) {
            if (!isset($resourceMapping[$userPhoto->resource_id])) {
                unset($userPhotos[$index]);
                continue;
            }
            $userPhoto->resource_id = $resourceMapping[$userPhoto->resource_id];
        }

        $userReview = DB::table('user_review')->select('nickname', 'birthday', 'region', 'height',
            'weight', 'job', 'intro', 'check_status', 'reason', 'done_at', 'alert_status')
            ->where('user_id', $user->id)->where('check_status', UserReview::CHECK_STATUS_PASS)
            ->where('deleted_at', 0)->orderBy('id', 'desc')->first();
        $wechat     = DB::table('wechat')->select('wechat', 'qr_code', 'parse_content',
            'check_status', 'done_at')->where('user_id', $user->id)->where('check_status', 100)
            ->where('deleted_at', 0)->orderBy('id', 'desc')->first();
        $facePic    = DB::table('face_pic')->where('user_id', $user->id)->select('base_map', 'status')
            ->where('deleted_at', 0)->orderBy('id', 'desc')->first();
        $res        = ['resources' => $resources, 'photos' => $userPhotos];
        $auths      = ['review' => $userReview, 'wechat' => $wechat, 'face' => $facePic];
        $userInfo   = [
            'user'         => $user,
            'detail'       => $userDetail,
            'detail_extra' => $detailExtraData,
            'hobbies'      => $hobbies,
            'job'          => $job,
            'resources'    => $res,
            'auths'        => $auths,
        ];

        $api = sprintf(config('custom.internal.sync_users_active_url'), $user->uuid);
        try {
            Tools::getHttpRequestClient()->post($api, ['json' => $userInfo]);
        } catch (RequestException $e) {
            return ResultReturn::failed('', $userInfo);
        }

        return ResultReturn::success($userInfo);
    }

    /**
     * 更新冷起用户活跃时间
     *
     * @param $user
     * @param $os
     * @param $runVersion
     *
     * @throws GuzzleException
     */
    public function updateColdStartUserActiveAt($user, $os, $runVersion)
    {
        if (pocket()->account->isColdStartUser($user->id)) {
            $api  = sprintf(config('custom.internal.update_users_active_url'), $user->uuid);
            Tools::getHttpRequestClient()->post($api,
                ['json' => ['os' => $os, 'run_version' => $runVersion]]);
        }
    }

    /**
     * 更新冷起用户位置
     *
     * @param $user
     * @param $lng
     * @param $lat
     *
     * @throws GuzzleException
     */
    public function updateColdStartUserLocation($user, $lng, $lat)
    {
        if (pocket()->account->isColdStartUser($user->id)) {
            $api  = sprintf(config('custom.internal.update_users_location_url'), $user->uuid);
            Tools::getHttpRequestClient()->post($api,
                ['json' => ['lng' => $lng, 'lat' => $lat]]);
        }
    }
}
