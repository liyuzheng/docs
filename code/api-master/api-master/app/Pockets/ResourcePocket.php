<?php


namespace App\Pockets;

use App\Models\User;
use App\Models\Resource;
use App\Foundation\Modules\Pocket\BasePocket;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\Collection;
use App\Models\UserResource;
use Illuminate\Support\Facades\DB;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\UserPhoto;
use App\Models\UserLookOver;
use App\Models\ResourceCheck;

class ResourcePocket extends BasePocket
{
    /**
     * 获取多个用户的相册集
     *
     * @param  Collection  $users
     * @param  User        $authUser
     *
     * @return Collection
     */
    public function appendAlbumToUsers(Collection $users, User $authUser)
    {
        $userPhotos = rep()->userPhoto->m()
            ->select(['resource_id'])
            ->whereIn('user_id', $users->pluck('id')->toArray())
            ->get();
        $resources  = rep()->resource->m()
            ->select(['type', 'related_id'])
            ->whereIn('id', $userPhotos->pluck('resource_id')->toArray())
            ->orderByDesc('sort')
            ->get();
        foreach ($users as $user) {
            $albums = $resources->where('related_id', $user->id);
            if (version_compare(user_agent()->clientVersion, '2.1.0', '<')) {
                $user->setAttribute('photo', array_values($albums->toArray()));
            } else {
                $user->setAttribute('photo', []);
            }
            $hasVideo = $resources->where('type', Resource::TYPE_LIST[Resource::TYPE_VIDEO])
                ->where('related_id', $user->id)
                ->count();
            $user->setAttribute('has_video', (bool)$hasVideo);
            $user->setAttribute('photo_count', $albums->count());
        }

        return $users;
    }

    /**
     * 获取多个用户的相册
     *
     * @param  Collection  $users
     *
     * @return mixed
     */
    public function appendPhotoToUsers(Collection $users)
    {
        $userPhotos = rep()->userPhoto->m()
            ->select(['user_id', 'resource_id'])
            ->whereIn('user_id', $users->pluck('id')->toArray())
            ->get();
        $resources  = rep()->resource->m()
            ->select(['type', 'related_id'])
            ->whereIn('id', $userPhotos->pluck('resource_id')->toArray())
            ->orderByDesc('sort')
            ->get();
        foreach ($users as $user) {
            $userPhoto = $userPhotos->where('user_id', $user->id);
            $avatar    = $resources->where('related_id', $user->id);
            foreach ($userPhoto as $item) {
                $resource = $avatar->where('id', $item->resource_id)->first();
                if (!$resource) {
                    continue;
                }
                if ($item->related_type == UserPhoto::RELATED_TYPE_FIRE) {
                    $resource->setAttribute('small_cover', $resource->small_cover . '/blur/200x50');
                } elseif ($item->related_type == UserPhoto::RELATED_TYPE_RED_PACKET) {
                    $resource->setAttribute('small_cover', $resource->small_cover . '|imageMogr2/blur/200x50');
                }
            }
            $user->setAttribute('photo', array_values($avatar->toArray()));
            $status = false;
            $video  = $resources->where('type', Resource::TYPE_LIST[Resource::TYPE_VIDEO])
                ->where('related_id', $user->id)
                ->first();
            if ($video) {
                $hasVideo = rep()->resourceCheck->m()->where('resource_id', $video->id)->first();
                if ($hasVideo && $hasVideo->status == ResourceCheck::STATUS_PASS) {
                    $status = true;
                }
            }
            $user->setAttribute(
                'has_video', $status
            );
        }

        return $users;
    }

    /**
     * 获取一个用户的相册
     *
     * @param  User  $user
     * @param  User  $targetUser
     *
     * @return mixed
     */
    public function appendPhotoToUser(User $user, User $targetUser)
    {
        $userPhoto = rep()->userPhoto->m()
            ->select(['resource_id'])
            ->where('user_id', $user->id)
            ->when($user !== $targetUser, function ($query) {
                $query->where('status', UserPhoto::STATUS_OPEN);
            })->get();
        if (count($userPhoto) == 0) {
            return $user;
        }
        $resources = rep()->resource->m()
            ->select(['id', 'related_id', 'related_type', 'type', 'resource', 'height', 'width', 'uuid'])
            ->where('related_type', Resource::RELATED_TYPE_USER_PHOTO)
            ->whereIn('id', $userPhoto->pluck('resource_id')->toArray())
            ->get();
        $status    = false;
        foreach ($resources as $resource) {
            if ($userPhoto->where('resource_id',
                    $resource->id)->first()->related_type == UserPhoto::RELATED_TYPE_FIRE) {
                $resource->setAttribute('pay_type', 'fire');
                $resource->setAttribute('cover', $resource->fake_cover . '?imageMogr2/blur/200x50');
            } elseif ($userPhoto->where('resource_id',
                    $resource->id)->first()->related_type == UserPhoto::RELATED_TYPE_RED_PACKET) {
                $resource->setAttribute('pay_type', 'red_packet');
                $resource->setAttribute('cover', $resource->fake_cover . '|imageMogr2/blur/10x10');
            } else {
                $resource->setAttribute('pay_type', 'free');
                $resource->setAttribute('cover', $resource->fake_cover);
            }
            if ($resource->type == 'video') {
                $checkStauts = rep()->resourceCheck->m()->where('resource_id', $resource->id)->first();
                if ($checkStauts && $checkStauts->status == ResourceCheck::STATUS_PASS) {
                    $status = true;
                }
            }
        }

        $user->setAttribute('has_video', $status);
        $user->setAttribute('photo', array_values(
                $resources->where('type', Resource::TYPE_LIST[Resource::TYPE_IMAGE])->toArray())
        );

        return $user;
    }

    /**
     * 增加相册合集 [包含视频和图片] 1.6.0以上走这里
     *
     * @param  User  $user
     * @param  User  $authUser
     *
     * @return User
     */
    public function appendAlbumToUser(User $user, User $authUser)
    {
        $userPhoto   = rep()->userPhoto->m()
            ->select(['resource_id'])
            ->when($user->id !== $authUser->id, function ($query) {
                $query->where('status', UserPhoto::STATUS_OPEN);
            })->where('user_id', $user->id)->get();
        $resources   = rep()->resource->m()
            ->select(['id', 'uuid', 'related_id', 'related_type', 'type', 'resource', 'height', 'width'])
            ->whereIn('id', $userPhoto->pluck('resource_id')->toArray())
            ->orderByDesc('sort')
            ->orderBy('id')
            ->get();
        $userPhoto   = rep()->userPhoto->m()
            ->where('user_id', $user->id)
            ->whereIn('resource_id', $resources->pluck('id')->toArray())
            ->whereIn('related_type', [UserPhoto::RELATED_TYPE_RED_PACKET, UserPhoto::RELATED_TYPE_FIRE])
            ->where('deleted_at', 0)
            ->pluck('resource_id')
            ->toArray();
        $overLook    = rep()->userLookOver->m()
            ->where('target_id', $user->id)
            ->where('user_id', $authUser->id)
            ->whereIn('resource_id', $resources->pluck('id')->toArray())
            ->where('deleted_at', 0)
            ->get();
        $resourceRes = [];
        $status      = false;
        foreach ($resources as $k => $resource) {
            $resourceRes[$k]['uuid']        = $resource->uuid;
            $resourceRes[$k]['type']        = $resource->type;
            $resourceRes[$k]['width']       = $resource->width;
            $resourceRes[$k]['height']      = $resource->height;
            $resourceRes[$k]['cover']       = $resource->fake_cover;
            $resourceRes[$k]['preview']     = $resource->preview;
            $resourceRes[$k]['resource']    = $resource->resource;
            $resourceRes[$k]['pay_type']    = UserPhoto::RELATED_TYPE_FREE_STR;
            $resourceRes[$k]['status']      = UserLookOver::STATUS_NOT_READ;
            $resourceRes[$k]['sort']        = $resource->sort;
            $resourceRes[$k]['small_cover'] = $resource->small_cover;
            $look                           = $overLook->where('resource_id', $resource->id)->sortByDesc('id')->first();
            if (in_array($resource->id, $userPhoto, true)) {
                if ($look) {
                    if ($look->expired_at >= time()) {
                        $resourceRes[$k]['status'] = UserLookOver::STATUS_READED;
                    } else {
                        $resourceRes[$k]['status'] = UserLookOver::STATUS_NOT_READ;
                    }
                }
                if ($resource->type === Resource::TYPE_LIST[Resource::TYPE_VIDEO]) {
                    $resourceRes[$k]['pay_type']    = UserPhoto::RELATED_TYPE_RED_PACKET_STR;
                    $resourceRes[$k]['cover']       = $resource->fake_cover . '|imageMogr2/blur/200x50';
                    $resourceRes[$k]['small_cover'] = $resource->small_cover . '|imageMogr2/blur/200x50';
                } elseif ($resource->type === Resource::TYPE_LIST[Resource::TYPE_IMAGE]) {
                    $resourceRes[$k]['pay_type']    = UserPhoto::RELATED_TYPE_FIRE_STR;
                    $resourceRes[$k]['cover']       = $resource->fake_cover . '?imageMogr2/blur/200x50';
                    $resourceRes[$k]['small_cover'] = $resource->small_cover . '/blur/200x50';
                } else {
                    $resourceRes[$k]['pay_type']    = UserPhoto::RELATED_TYPE_FREE_STR;
                    $resourceRes[$k]['cover']       = $resource->fake_cover;
                    $resourceRes[$k]['small_cover'] = $resource->small_cover;
                }
            }
            if ($resource->type == 'video') {
                $checkStauts = rep()->resourceCheck->m()->where('resource_id', $resource->id)->first();
                if ($checkStauts && $checkStauts->status == ResourceCheck::STATUS_PASS) {
                    $status = true;
                }
            }
        }
        $user->setAttribute('has_video', $status);
        $user->setAttribute('photo', collect($resourceRes)->sortByDesc('sort'));

        return $user;

    }

    /**
     * 更新一个用户的相册
     *
     * @param $uuid   int 用户uuid
     * @param $paths  ['upload1.png','upload2.png']
     *
     * @return mixed
     */
    public function updateUserPhotos(int $uuid, array $paths)
    {
        $now          = time();
        $user         = pocket()->user->getUserInfoByUUID($uuid)->getData();
        $oldResId     = rep()->userResource->m()
            ->where('user_id', $user->id)
            ->where('type', UserResource::TYPE_PHOTO)
            ->pluck('resource_id')->toArray();
        $res          = rep()->resource->m()
            ->whereIn('resource', $paths)
            ->pluck('resource')->toArray();
        $newResId     = rep()->resource->m()
            ->whereIn('resource', $paths)
            ->pluck('id')->toArray();
        $delResIds    = array_diff($oldResId, $newResId);
        $insRes       = array_diff($paths, $res);
        $avatarDetail = pocket()->account->getImagesDetail($insRes)->getData();
        try {
            DB::transaction(function () use ($avatarDetail, $user, $delResIds, $insRes, $now) {
                if (count($delResIds)) {
                    rep()->resource->m()->where('id', $delResIds)->delete();
                    rep()->userResource->m()->where('resource_id', $delResIds)->delete();
                }
                foreach ($insRes as $res) {
                    $data  = [
                        'uuid'         => pocket()->util->getSnowflakeId(),
                        'related_type' => Resource::RELATED_TYPE_USER_PHOTO,
                        'related_id'   => $user->id,
                        'type'         => Resource::TYPE_IMAGE,
                        'resource'     => $res,
                        'height'       => $avatarDetail[$res]['height'] ?? 0,
                        'width'        => $avatarDetail[$res]['width'] ?? 0,
                        'sort'         => 100,
                        'created_at'   => $now,
                        'updated_at'   => $now
                    ];
                    $resId = rep()->resource->m()->insertGetId($data);
                    rep()->userResource->m()->create([
                        'uuid'        => pocket()->util->getSnowflakeId(),
                        'user_id'     => $user->id,
                        'type'        => UserResource::TYPE_PHOTO,
                        'resource_id' => $resId,
                    ]);
                }
            });
        } catch (\Exception $exception) {
            return ResultReturn::failed($exception->getMessage());
        }

        return ResultReturn::success('更新成功！');
    }

    /**
     * 用户注册增加邀请码
     *
     * @param  int  $userId
     *
     * @return ResultReturn
     * @throws GuzzleException
     */
    public function postUserInviteCodeQrCode(int $userId)
    {
        $userDetail = rep()->userDetail->write()->where('user_id', $userId)->first();
        $user       = rep()->user->getById($userId, ['uuid']);
        $api        = config('custom.file_url') . '/file/poster?code=' . $userDetail->invite_code . '&link=http://i.xiaoquann.com/invite_slb?uuid=' . $user->uuid;
        try {
            $response = (new Client(['timeout' => 10]))->get($api);
        } catch (\Exception $e) {
            return ResultReturn::failed($e->getMessage());
        }
        if ($response->getStatusCode() !== 200) {
            return ResultReturn::failed(trans('messages.http_code_not_200_tmpl'));
        }
        $body = json_decode($response->getBody()->getContents(), true);
        if ($body['code'] !== 1007) {
            return ResultReturn::failed('业务code不是1007');
        }
        $inviteResource = rep()->resource->m()
            ->where('related_type', Resource::RELATED_INVITE_QR_CODE)
            ->where('related_id', $userId)
            ->orderBy('id', 'desc')
            ->first();
        if (!$inviteResource) {
            $resourceArr    = [
                'uuid'         => pocket()->util->getSnowflakeId(),
                'user_id'      => $userId,
                'related_type' => Resource::RELATED_INVITE_QR_CODE,
                'related_id'   => $userId,
                'type'         => Resource::TYPE_IMAGE,
                'resource'     => $body['data']['resource'],
                'width'        => $body['data']['width'],
                'height'       => $body['data']['height']
            ];
            $inviteResource = rep()->resource->m()->create($resourceArr);

            return ResultReturn::success($inviteResource);
        } else {
            $inviteResource->update([
                'resource' => $body['data']['resource'],
                'width'    => $body['data']['width'],
                'height'   => $body['data']['height']
            ]);
        }

        return ResultReturn::success($inviteResource);
    }

    /**
     * 获得邀请码二维码
     *
     * @param  int  $userId
     *
     * @return string
     */
    public function getUserInviteCodeQrCode(int $userId)
    {
        $inviteResource = rep()->resource->m()
            ->where('related_type', Resource::RELATED_INVITE_QR_CODE)
            ->where('related_id', $userId)
            ->orderBy('id', 'desc')
            ->first();
        if ($inviteResource) {
            return cdn_url($inviteResource->resource);
        }

        pocket()->common->commonQueueMoreByPocketJob(
            pocket()->resource,
            'postUserInviteCodeQrCode',
            [$userId]
        );

        return '';
    }
}
