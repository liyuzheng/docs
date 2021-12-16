<?php


namespace App\Pockets;

use Carbon\Carbon;
use App\Models\Moment;
use App\Models\UserLike;
use App\Models\Resource;
use App\Models\BaseModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use App\Constant\NeteaseCustomCode;
use Illuminate\Support\Facades\Log;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\User;

class MomentPocket extends BasePocket
{
    /**
     * 往Moments collection中添加数据
     *
     * @param  Collection  $moments
     * @param  array       $property
     *
     * @return Collection
     */
    public function appendToMoments(Collection $moments, $property = []) : Collection
    {
        $filterProperty = pocket()->util->conversionAppendToUserArgs($property);
        /** append时间 */
        if (array_key_exists('human', $filterProperty)) {
            pocket()->moment->appendCreatedHumansToUsers($moments);
        }
        /** append图片 */
        if (array_key_exists('image', $filterProperty)) {
            pocket()->moment->appendImagesToMoments($moments);
        }
        /** append用户信息 */
        if (array_key_exists('user', $filterProperty)) {
            pocket()->moment->appendUserToMoments($moments);
        }
        /** append当前是否点赞 */
        if (array_key_exists('like', $filterProperty)) {
            pocket()->moment->appendLikeToMoments($moments, $filterProperty['like']);
        }
        /** append当前距离 */
        if (array_key_exists('distance', $filterProperty)) {
            pocket()->moment->appendDistanceToUsers($moments, $filterProperty['distance']);
        }
        /** append当前动态的话题 */
        if (array_key_exists('topic', $filterProperty)) {
            pocket()->moment->appendTopicToUsers($moments);
        }

        return $moments;
    }

    /**
     * 往Moment 中添加数据
     *
     * @param  Moment|BaseModel  $moment
     * @param  array             $property
     *
     * @return Moment
     */
    public function appendToMoment(Moment $moment, array $property = []) : Moment
    {
        $filterProperty = pocket()->util->conversionAppendToUserArgs($property);
        /** append时间 */
        if (array_key_exists('human', $filterProperty)) {
            pocket()->moment->appendCreatedHumansToUser($moment);
        }
        /** append图片 */
        if (array_key_exists('image', $filterProperty)) {
            pocket()->moment->appendImagesToMoment($moment);
        }
        /** append用户信息 */
        if (array_key_exists('user', $filterProperty)) {
            pocket()->moment->appendUserToMoment($moment);
        }
        /** append头像信息 */
        if (array_key_exists('avatar', $filterProperty)) {
            pocket()->moment->appendAvatarsToMoment($moment);
        }
        /** append当前是否点赞 */
        if (array_key_exists('like', $filterProperty)) {
            pocket()->moment->appendLikeToMoment($moment, $filterProperty['like']);
        }
        /** append当前动态的距离 */
        if (array_key_exists('distance', $filterProperty)) {
            pocket()->moment->appendDistanceToUser($moment, $filterProperty['distance']);
        }
        /** append当前动态的话题 */
        if (array_key_exists('topic', $filterProperty)) {
            pocket()->moment->appendTopicToUser($moment);
        }

        return $moment;
    }

    /**
     * 某个用户是否已经给某个动态点赞过
     *
     * @param $momentId
     * @param $userId
     *
     * @return bool
     */
    public function hasLike($momentId, $userId)
    {
        return rep()->userLike->m()
            ->where('related_id', $momentId)
            ->where('related_type', UserLike::RELATED_TYPE_MOMENT)
            ->where('user_id', $userId)
            ->where('deleted_at', 0)
            ->exists();
    }

    /**
     * 根据发布时间获得发布状态的信息
     *
     * @param  mixed  $createdAt  发布的时间戳
     *
     * @return array
     */
    public function getCreatedAtHumans($createdAt) : array
    {
        $data = [
            'created_format' => '',
        ];
        if (!$createdAt) {
            return $data;
        }
        if ($createdAt instanceof Carbon && (time() - $createdAt->timestamp <= 1 * 60)) {
            $data['created_format'] = trans('messages.just_now');

            return $data;
        }
        if ($createdAt instanceof Carbon) {
            $data['created_format'] = $createdAt->diffForHumans();
        } elseif (is_int($createdAt)) {
            $data['created_format'] = Carbon::createFromTime($createdAt)->diffForHumans();
        }


        return $data;
    }

    /**
     * append某个动态的发布状态
     *
     * @param  Collection  $moments
     *
     * @return Collection
     */
    public function appendCreatedHumansToUsers(Collection $moments) : Collection
    {
        $suffix = trans('messages.publish');
        foreach ($moments as $moment) {
            $active = $this->getCreatedAtHumans($moment->created_at);
            $moment->setAttribute('created_format', $active['created_format'] . $suffix);
        }

        return $moments;
    }

    /**
     * append某个动态的发布状态
     *
     * @param  Moment  $moment
     *
     * @return Moment
     */
    public function appendCreatedHumansToUser(Moment $moment) : Moment
    {
        $suffix = trans('messages.publish');
        $active = $this->getCreatedAtHumans($moment->created_at);
        $moment->setAttribute('created_format', $active['created_format'] . $suffix);

        return $moment;
    }

    /**
     * append某个动态的图片资源
     *
     * @param  Moment  $moment
     *
     * @return Moment
     */
    public function appendImagesToMoment(Moment $moment) : Moment
    {
        $resources = rep()->resource->m()->where('related_id', $moment->id)
            ->select(['related_id', 'resource', 'type', 'width', 'height'])
            ->where('related_type', Resource::RELATED_MOMENT)
            ->where('type', Resource::TYPE_IMAGE)
            ->get();
        foreach ($resources as $resource) {
            $resource->setHidden(['fake_cover', 'related_id', 'resource']);
        }
        $moment->setAttribute('images', $resources);

        return $moment;
    }

    /**
     * append某个动态的图片资源
     *
     * @param  Collection  $moments
     *
     * @return Collection
     */
    public function appendImagesToMoments(Collection $moments) : Collection
    {
        $momentIds = $moments->pluck('id')->toArray();
        $resources = rep()->resource->m()->whereIn('related_id', $momentIds)
            ->select(['related_id', 'related_type', 'resource', 'type', 'width', 'height'])
            ->where('related_type', Resource::RELATED_MOMENT)
            ->where('type', Resource::TYPE_IMAGE)
            ->get();
        foreach ($resources as $resource) {
            $resource->setHidden(['fake_cover', 'related_id', 'resource']);
        }
        foreach ($moments as $moment) {
            $res = [];
            $ret = $resources->where('related_id', $moment->id);
            if ($ret) {
                $res = array_values($ret->toArray());
            }
            $moment->setAttribute('images', $res);
        }

        return $moments;
    }

    /**
     * append某些动态的用户信息
     *
     * @param  Collection  $moments
     *
     * @return Collection
     */
    public function appendUserToMoments(Collection $moments) : Collection
    {
        $users = rep()->user->m()
            ->select(['id', 'role', 'uuid', 'nickname', 'birthday', 'gender'])
            ->whereIn('id', $moments->pluck('user_id')->toArray())
            ->get();
        pocket()->user->appendToUsers($users, ['avatar', 'member', 'netease' => ['accid'], 'job', 'charm_girl']);
        foreach ($moments as $moment) {
            $moment->setAttribute('user', $users->where('id', $moment->user_id)->first());
        }

        return $moments;
    }

    /**
     * append增加某些动态某个用户是否点赞
     *
     * @param  Collection  $moments
     * @param              $userId
     *
     * @return Collection
     */
    public function appendLikeToMoments(Collection $moments, $userId) : Collection
    {
        $userLikes = rep()->userLike->m()
            ->where('related_type', UserLike::RELATED_TYPE_MOMENT)
            ->whereIn('related_id', $moments->pluck('id')->toArray())
            ->where('user_id', $userId)
            ->get();
        foreach ($moments as $moment) {
            $moment->setAttribute('like', $userLikes->where('related_id', $moment->id)->first() ? 1 : 0);
        }

        return $moments;
    }

    /**
     * 新增动态的距离
     *
     * @param  Collection  $moments
     * @param  User        $user
     *
     * @return Collection
     */
    public function appendDistanceToUsers(Collection $moments, User $user) : Collection
    {
        $lng1       = $lat1 = 0;
        $userDetail = rep()->userDetail->m()->where('user_id', $user->id)->first();
        if ($userDetail) {
            $lng1 = $userDetail->lng;
            $lat1 = $userDetail->lat;
        }

        $unKnowAddress = trans('messages.unknown_address');
        foreach ($moments as $moment) {
            $distance = $unKnowAddress;
            if ($lng1 != 0 && $lat1 != 0 && $moment->lng != 0 && $moment->lat != 0) {
                $distance = get_distance_str($lng1, $lat1, $moment->lng, $moment->lat);
            }
            $moment->setAttribute('distance', $distance);
        }

        return $moments;
    }

    /**
     * append某个动态的距离
     *
     * @param  Moment  $moment
     * @param  User    $user
     *
     * @return Moment
     */
    public function appendDistanceToUser(Moment $moment, User $user) : Moment
    {
        $lng1       = $lat1 = 0;
        $userDetail = rep()->userDetail->m()->where('user_id', $user->id)->first();
        if ($userDetail) {
            $lng1 = $userDetail->lng;
            $lat1 = $userDetail->lat;
        }
        $distance = '0m';
        if ($lng1 != 0 && $lat1 != 0 && $moment->lng != 0 && $moment->lat != 0) {
            $distance = get_distance_str($lng1, $lat1, $moment->lng, $moment->lat);
        }
        $moment->setAttribute(
            'distance', $distance
        );

        return $moment;
    }

    /**
     * 增加话题信息
     *
     * @param  Moment  $moment
     *
     * @return Moment
     */
    public function appendTopicToUser(Moment $moment) : Moment
    {
        $topic = rep()->topic->m()
            ->select(['uuid', 'name', 'desc'])
            ->where('id', $moment->topic_id)
            ->first();
        $moment->setAttribute('topic', !is_null($topic) ? $topic :
            ['uuid' => 0, 'name' => trans('messages.moment_title')]);

        return $moment;
    }

    /**
     * 增加话题信息
     *
     * @param  Collection  $moments
     *
     * @return Collection
     */
    public function appendTopicToUsers(Collection $moments) : Collection
    {
        $topics = rep()->topic->m()
            ->select(['id', 'uuid', 'name', 'desc'])
            ->whereIn('id', $moments->pluck('topic_id')->toArray())
            ->get();

        $momentTitle = trans('messages.moment_title');
        foreach ($moments as $moment) {
            $top = $topics->where('id', $moment->topic_id)->first();
            if (version_compare(user_agent()->clientVersion, '1.9.0', '<=')) {
                $moment->setAttribute('topic', !is_null($top) ? $top : ['uuid' => 0, 'name' => $momentTitle]);
            } else {
                $moment->setAttribute('topic', !is_null($top) ? $top : null);
            }
        }

        return $moments;
    }

    /**
     * append增加某个动态某个用户是否点赞
     *
     * @param  Moment  $moment
     * @param          $userId
     *
     * @return Moment|Collection
     */
    public function appendLikeToMoment(Moment $moment, $userId)
    {
        $userLikes = rep()->userLike->m()
            ->where('related_type', UserLike::RELATED_TYPE_MOMENT)
            ->where('related_id', $moment->id)
            ->where('user_id', $userId)
            ->first();
        $moment->setAttribute('like', $userLikes ? 1 : 0);

        return $moment;
    }

    /**
     * append某个动态的用户信息
     *
     * @param  Moment  $moment
     *
     * @return Moment
     */
    public function appendUserToMoment(Moment $moment) : Moment
    {
        $user = rep()->user->m()
            ->select(['id', 'uuid', 'role', 'nickname', 'birthday', 'gender'])
            ->where('id', $moment->user_id)
            ->first();
        if (!$user) {
            return $moment;
        }
        pocket()->user->appendToUser($user,
            ['avatar', 'member', 'netease' => ['accid'], 'member', 'job', 'charm_girl']);
        $moment->setAttribute('user', $user);

        return $moment;
    }

    /**
     * append某些动态的字段
     *
     * @param  Collection  $moments
     *
     * @return Collection
     */
    public function appendHiddenFieldToMoments(Collection $moments) : Collection
    {
        foreach ($moments as $moment) {
            $moment->setHidden(['topic_id', 'user_id', 'check_status', 'created_at', 'updated_at', 'deleted_at']);
        }

        return $moments;
    }

    /**
     * append某个动态的字段
     *
     * @param  Moment  $moment
     *
     * @return Moment
     */
    public function appendHiddenFieldToMoment(Moment $moment) : Moment
    {
        $moment->setHidden(['topic_id', 'user_id', 'check_status', 'created_at', 'updated_at', 'deleted_at']);

        return $moment;
    }

    /**
     * 增加头像信息
     *
     * @param  Moment  $moment
     *
     * @return Moment
     */
    public function appendAvatarsToMoment(Moment $moment) : Moment
    {
        $userLikeUserIds = rep()->userLike->m()
            ->where('related_id', $moment->id)
            ->where('related_type', UserLike::RELATED_TYPE_MOMENT)
            ->pluck('user_id');
        $avatars         = rep()->resource->m()
            ->where('related_type', Resource::RELATED_TYPE_USER_AVATAR)
            ->whereIn('related_id', $userLikeUserIds)
            ->where('type', Resource::TYPE_IMAGE)
            ->where('deleted_at', 0)
            ->pluck('resource');
        $avatarArr       = [];
        foreach ($avatars as $avatar) {
            $avatarArr[] = cdn_url($avatar) . "?imageView2/1/w/200/h/200|roundPic/radius/300";
        }
        $moment->setAttribute('avatars', $avatarArr);

        return $moment;
    }

    /**
     * 点赞发送消息
     *
     * @param  int  $userId      动态所属人id
     * @param  int  $likeUserId  发布动态人id
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function likeNetease(int $userId, int $likeUserId)
    {
        $users    = rep()->user->m()->whereIn('id', [$userId, $likeUserId])->get();
        $user     = $users->where('id', $userId)->first();
        $likeUser = $users->where('id', $likeUserId)->first();
        if (!$user || !$likeUser) {
            return;
        }
        $msgCount = 0;
        $userMark = mongodb('user_mark')->where('_id', $userId)->first();
        if ($userMark && isset($userMark['moment_unread'])) {
            $msgCount = $userMark['moment_unread'] > 0 ? $userMark['moment_unread'] : 0;
        }
        ++$msgCount;
        $msgCount = $msgCount > 999 ? '999+' : $msgCount;
        $message  = $msgCount > 1 ? sprintf(trans('messages.others_like_moment_notice_tmpl'), $likeUser->nickname,
            $msgCount)
            : sprintf(trans('messages.other_like_moment_tmpl'), $likeUser->nickname);
        if (!$userMark) {
            mongodb('user_mark')->where('_id', $userId)->insert(['_id' => $userId, 'moment_unread' => 1]);
        } else {
            mongodb('user_mark')->where('_id', $userId)->increment('moment_unread');
        }
        $sender = config('custom.little_helper_uuid');
        $body   = [
            'type' => NeteaseCustomCode::MOMENT_LIKE,
            'data' => [
                'sender'   => ['message' => '', 'user' => ['uuid' => $sender, 'nickname' => '', 'avatar' => '']],
                'receiver' => $message
            ]
        ];
        $resp   = pocket()->netease->sendMomentLikeCountMsg($sender, $user->uuid, $body);
        Log::info(sprintf('%s like_message', $user->uuid),
            ['request' => $body, 'response' => $resp->getData()]);
    }

    /**
     * 删除用户的动态
     *
     * @param  int  $userId
     *
     * @return bool|mixed|null
     * @throws \Exception
     */
    public function deleteUserMoment(int $userId)
    {
        return rep()->moment->m()->where('user_id', $userId)->delete();
    }

    /**
     * 给动态喜欢记录绑定动态详情和记录
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $likes
     * @param  string                                    $msgTemplate
     *
     * @return  \Illuminate\Database\Eloquent\Collection
     */
    public function bindMomentFollowRecords($likes, $msgTemplate)
    {
        $users = rep()->user->m()->select('id', 'uuid', 'nickname', 'gender', 'nickname', 'birthday')
            ->whereIn('id', $likes->pluck('user_id')->toArray())->get();
        pocket()->user->appendToUsers($users, ['avatar', 'member', 'job']);

        $resources = rep()->resource->m()->whereIn('related_id', $likes->pluck('moment_id')->toArray())
            ->select(['related_id', 'resource', 'type', 'width', 'height'])
            ->where('related_type', Resource::RELATED_MOMENT)
            ->where('type', Resource::TYPE_IMAGE)
            ->get();

        foreach ($likes as $like) {
            /** @var \App\Models\Resource $resource */
            $resource = $resources->where('related_id', $like->moment_id)->first();
            $resource->setHidden(array_merge($resource->getHidden(), ['fake_cover', 'related_id', 'resource']));
            $content = $like->getRawOriginal('deleted_at') ? '此内容已被删除' : $like->content;
            /** @var \App\Models\User $user */
            $user = $users->where('id', $like->user_id)->first();
            $like->getRawOriginal('deleted_at') && $resource->setAttribute('resource',
                'uploads/common/moment_destroy.png');
            $like->setAttribute('created_format', $like->created_at->diffForHumans(Carbon::now()))
                ->setAttribute('moment', ['uuid' => $like->uuid, 'content' => $content])
                ->setAttribute('user', $user)->setAttribute('msg', sprintf($msgTemplate, 'Ta'))
                ->setAttribute('images', [$resource])->setHidden(array_merge($like->getHidden(),
                    ['created_at', 'moment_id', 'moment_id', 'uuid', 'content']));
        }

        return $likes;
    }

    /**
     * 更新动态的like_count
     *
     * @param  int  $momentId
     *
     * @return ResultReturn
     */
    public function updateLikeCountToEs(int $momentId)
    {
        $moment = rep()->moment->getById($momentId, ['like_count']);
        if (!$moment) {
            return ResultReturn::failed('not found moment');
        }
        pocket()->esMoment->updateMomentFieldToEs($momentId, ['like_count' => $moment->like_count]);

        return ResultReturn::success(['moment_id' => $momentId, 'like_count' => $moment->like_count]);
    }

    /**
     * 获得和一部分固定动态的举例
     *
     * @param  Collection  $moments
     * @param  float       $selfLat
     * @param  float       $selfLng
     *
     * @return array
     */
    public function getDistanceToMomentsByFixedLocationUUIDArr(
        Collection $moments,
        float $selfLat,
        float $selfLng
    ) : array {
        $momentsId     = $moments->pluck('id')->toArray();
        $momentsDetail = rep()->moment->m()
            ->select('id', 'lat', 'lng')
            ->whereIn('id', $momentsId)
            ->get();
        $returnData    = [];
        foreach ($moments as $moment) {
            $distance     = '未知';
            $momentDetail = $momentsDetail->where('id', $moment->id)->first();
            if ($momentDetail) {
                $targetLng = $momentDetail->lng;
                $targetLat = $momentDetail->lat;
                if ($targetLng != 0 && $targetLat != 0) {
                    $distance = get_distance_str($selfLng, $selfLat, $targetLng, $targetLat);
                }
            }
            $returnData[$moment->uuid] = $distance;
        }

        return $returnData;
    }
}
