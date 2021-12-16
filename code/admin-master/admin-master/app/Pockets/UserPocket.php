<?php


namespace App\Pockets;

use App\Models\Discount;
use App\Models\SwitchModel;
use App\Models\Task;
use App\Models\TradePay;
use App\Models\User;
use App\Models\UserAb;
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
use App\Foundation\Handlers\Tools;

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
    public function appendToUser(User $user, $property = []): User
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
    public function appendToUsers(Collection $users, $property = []): Collection
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
    public function appendMemberToUser(User $user): User
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
    public function appendAuthUserToUser(User $user): User
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
    public function appendCharmGirlToUser(User $user): User
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
    public function appendMemberToUsers(Collection $users): Collection
    {
        $members = rep()->member->getQuery()->select('user_id', 'start_at', 'duration')
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
    public function appendHasPayMemberToUsers(Collection $users): Collection
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
    public function appendAuthUserToUsers(Collection $users): Collection
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
    public function appendCharmGirlToUsers(Collection $users): Collection
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
    public function getUserInfo(int $id, $field = 'id'): ResultReturn
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
            return ResultReturn::failed('用户不存在');
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
    public function getUsersInfo(array $ids, $field = 'id'): ResultReturn
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
    public function getUserInfoById(int $userId): ResultReturn
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
    public function getUserInfoByUUID(int $userId): ResultReturn
    {
        return $this->getUserInfo($userId, 'uuid');
    }

    /**
     * 更新user表的role字段
     *
     * @param $userId
     *
     * @return ResultReturn
     */
    public function updateUserTableRoleField($userId): ResultReturn
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
     * 给用户模型增加头像
     *
     * @param  User  $user
     *
     * @return User
     */
    public function appendAvatarToUser(User $user): User
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
    public function appendAvatarToUsers(Collection $users): Collection
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
    public function appendWeChatToUser(User $user, array $fields = []): User
    {
        $weChat         = rep()->wechat->m()
            ->select('wechat', 'check_status')
            ->where('user_id', $user->id)
            ->whereIn('check_status', [Wechat::STATUS_PASS])
            ->orderByDesc('id')
            ->first();
        $delayWeChat    = rep()->wechat->m()
            ->select('wechat', 'check_status')
            ->where('user_id', $user->id)
            ->whereIn('check_status', [Wechat::STATUS_DELAY])
            ->orderByDesc('id')
            ->first();
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
                    $value = $weChat ? pocket()->common->substr_cut($weChat->wechat) : '';
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
    public function appendWeChatToUsers(Collection $users, array $fields = []): Collection
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
    public function appendRealWeChatToUser(User $user, array $fields = []): User
    {
        $weChat   = DB::table('wechat')
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
     * 给某个用户返回number
     */
    public function appendNumberToUser(User $user)
    {
        $user->setAttribute('number', '');
    }

    /**
     * 给某个用户添加爱好
     */
    public function appendHobbyToUser(User $user)
    {
        $userTags = rep()->userTag->m()->where('user_id', $user->id)->get();
        $hobbys   = rep()->tag->m()
            ->select(['uuid', 'name'])
            ->where('type', Tag::TYPE_HOBBY)
            ->whereIn('id', $userTags->pluck('tag_id')->toArray())
            ->get()->toArray();
        $user->setAttribute('hobbys', $hobbys);
    }

    /**
     * 给一组用户模型增加用户详情
     *
     * @param  Collection  $users
     *
     * @return Collection
     */
    public function appendUserDetailToUsers(Collection $users): Collection
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
    public function appendUserDetailToUser(User $user): User
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
     * @param  User  $targetUser
     *
     * @return User
     */
    public function appendDistanceToUser(User $user, User $targetUser): User
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
    public function appendDistanceToUsers(Collection $users, User $targetUser): Collection
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
     * 根据当前时间获得活跃状态的信息
     *
     * @param  int  $activeAt 活跃的时间戳
     *
     * @return array
     */
    public function getUserActiveInfoByActiveAt(int $activeAt): array
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
            $activeAtFormat = '在线';
            $activeState    = 1;
        } else {
            if ($diffHours <= 7 * 24) { //7天前
                $activeAtFormat = Carbon::createFromTimestamp($activeAt)->diffForHumans();
            } else {
                $activeAtFormat = '一周前';
            }
        }
        $data['active_state']  = $activeState;
        $data['active_format'] = $activeAtFormat;

        return $data;
    }

    /**
     * 判断用户当前登录是否需要更新Redis中的登录时间,如果要更新,那么初步判断,要更新日活表
     *
     * @param  int  $userId 用户ID
     * @param  int  $toDayTime 今天开始时间
     *
     * @return bool
     */
    public function whetherUpdateOrPostUserLoginAtToRedis(int $userId, int $toDayTime): bool
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
    public function updateOrPostUserLoginAtToRedis(int $userId, int $loginTime): ResultReturn
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
     * @return Collection
     */
    public function getUserInfoWithAvatar(array $userIds): Collection
    {
        $orderStr = 'FIELD(user.id,' . implode(',', $userIds) . ')';
        $users    = rep()->user->m()
            ->select(['user.id', 'user.uuid', 'number', 'nickname', 'role', 'gender', 'resource.resource'])
            ->leftJoin('resource', 'resource.related_id', 'user.id')
            ->where('resource.related_type', Resource::RELATED_TYPE_USER_AVATAR)
            ->where('resource.deleted_at', 0)
            ->whereIn('user.id', $userIds)
            ->orderByRaw(DB::raw($orderStr))
            ->get();
        foreach ($users as $user) {
            $user->setAttribute('avatar', cdn_url($user->resource));
        }

        return $users;
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
            ->select(['id', 'uuid', 'nickname', 'gender', 'birthday', 'role', 'created_at'])
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
    public function faceBlack($user): ResultReturn
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
            return ResultReturn::failed($result->getMessage());
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
     * 关注公众号后更新用户审核状态
     *
     * @param $userId
     *
     * @return ResultReturn
     */
    public function changeReviewStatus($userId): ResultReturn
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
     * 撤回某个人的消息
     *
     * @param $userId
     *
     * @throws GuzzleException
     */
    public function recallUserMsg($userId)
    {
        $k = 1;
        while (true) {
            $userMsg = pocket()->esImChat->searchImChatSend($userId, 0, 0, 0, "", [], 3000, $k);
            $msgData = $userMsg->getData();
            if (!$msgData) {
                echo "处理完成" . PHP_EOL;
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
                    echo "消息格式有误" . PHP_EOL;
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
                echo $msgId . "撤回成功" . PHP_EOL;
            }
            $k++;
        }
    }

    /**
     * 同步冷起数据
     *
     * @param $userId
     *
     * @return ResultReturn
     * @throws GuzzleException
     */
    public function syncColdStartUser($userId): ResultReturn
    {
        $user = DB::table('user')->select('id', 'uuid', 'number', 'nickname', 'birthday', 'hide',
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
            foreach ($detailExtra as $index => $item) $detailExtraData[$index] = $tagsMapping[$item];
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
                break;
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

        $findSwitches = [SwitchModel::KEY_ADMIN_HIDE_USER, SwitchModel::KEY_LOCK_STEALTH, SwitchModel::KEY_LOCK_WECHAT];
        $userSwitches = rep()->userSwitch->getQuery()->select('switch.key', 'user_switch.status')
            ->join('switch', 'switch.id', 'user_switch.switch_id')->whereIn('switch.key', $findSwitches)
            ->where('user_id', $userId)->get();
        $switches     = [];
        foreach ($userSwitches as $userSwitch) {
            $switches[$userSwitch->key] = $userSwitch->status;
        }

        $switches[SwitchModel::KEY_LOCK_STEALTH] = $user->hide;

        $res      = ['resources' => $resources, 'photos' => $userPhotos];
        $auths    = ['review' => $userReview, 'wechat' => $wechat, 'face' => $facePic];
        $userInfo = [
            'user'         => $user,
            'detail'       => $userDetail,
            'detail_extra' => $detailExtraData,
            'hobbies'      => $hobbies,
            'job'          => $job,
            'resources'    => $res,
            'auths'        => $auths,
            'switches'     => $switches,
        ];

        $api      = sprintf(config('custom.internal.sync_users_active_url'), $user->uuid);
        $result   = Tools::getHttpRequestClient()->post($api,
            ['json' => $userInfo, 'headers' => ['Host' => 'api.okacea.com']]);
        $cacheKey = sprintf(config('redis_keys.cache.cold_start_user_cache'), $userId);
        if (redis()->client()->exists($cacheKey)) redis()->client()->set($cacheKey, 1);

        return ResultReturn::success($result);
    }
}
