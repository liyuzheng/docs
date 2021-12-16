<?php


namespace App\Jobs;


use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\Tag;
use App\Models\Resource;
use App\Models\UserPhoto;
use App\Models\UserLookOver;
use App\Models\ResourceCheck;
use Illuminate\Support\Facades\DB;

class UpdateUserInfoToMongoJob extends Job
{
    private $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function handle()
    {
        $user = rep()->user->m()
            ->select(['id', 'uuid', 'number', 'nickname', 'gender', 'birthday', 'role', 'hide'])
            ->with([
                'userDetail' => function ($query) {
                    $query->select(['user_id', 'intro', 'region', 'height', 'weight', 'reg_schedule']);
                }
            ])
            ->where('id', $this->userId)
            ->first();
        if (!$user) {
            return ResultReturn::failed('用户不存在');
        }

        $appendData = ['member', 'auth_user', 'charm_girl', 'job', 'hobby', 'netease' => ['accid']];
        pocket()->account->appendTagToUserByType($user, Tag::TYPE_TAG_MAN);
        pocket()->user->appendToUser($user, $appendData);
        $userPhoto   = rep()->userPhoto->m()
            ->where('status', UserPhoto::STATUS_OPEN)
            ->where('user_id', $user->id)
            ->get();
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
            ->pluck('resource_id')->toArray();
        $resourceRes = [];
        $status      = false;
        foreach ($resources as $k => $resource) {
            $resourceRes[$k]['uuid']     = $resource->uuid;
            $resourceRes[$k]['type']     = $resource->type;
            $resourceRes[$k]['width']    = $resource->width;
            $resourceRes[$k]['height']   = $resource->height;
            $resourceRes[$k]['resource'] = $resource->resource;
            $resourceRes[$k]['pay_type'] = UserPhoto::RELATED_TYPE_FREE_STR;
            $resourceRes[$k]['status']   = UserLookOver::STATUS_NOT_READ;
            $resourceRes[$k]['sort']     = $resource->sort;
            if (in_array($resource->id, $userPhoto, true)) {
                if ($resource->type === Resource::TYPE_LIST[Resource::TYPE_VIDEO]) {
                    $resourceRes[$k]['pay_type'] = UserPhoto::RELATED_TYPE_RED_PACKET_STR;
                } elseif ($resource->type === Resource::TYPE_LIST[Resource::TYPE_IMAGE]) {
                    $resourceRes[$k]['pay_type'] = UserPhoto::RELATED_TYPE_FIRE_STR;
                } else {
                    $resourceRes[$k]['pay_type'] = UserPhoto::RELATED_TYPE_FREE_STR;
                }
            }
            if ($resource->related_type == Resource::RELATED_MOMENT) {
                $resourceRes[$k]['small_cover'] = $resource->type == Resource::TYPE_LIST[Resource::TYPE_VIDEO] ? $resource->resource . '?vframe/png/offset/0/h/200' : $resource->resource . '?imageMogr2/thumbnail/300/auto-orient';
            } else {
                ///thumbnail/200
                $resourceRes[$k]['small_cover'] = $resource->type == Resource::TYPE_LIST[Resource::TYPE_VIDEO] ? $resource->resource . '?vframe/png/offset/0/h/200' : $resource->resource . '?imageMogr2';
            }

            $resourceRes[$k]['preview']    = $resource->resource;
            $resourceRes[$k]['cover']      = $resource->resource;
            $resourceRes[$k]['fake_cover'] = $resource->type == Resource::TYPE_LIST[Resource::TYPE_VIDEO] ? $resource->resource . '?vframe/png/offset/0' : $resource->resource;

            if ($resource->type == 'video') {
                $checkStauts = rep()->resourceCheck->m()->where('resource_id', $resource->id)->first();
                if ($checkStauts && $checkStauts->status == ResourceCheck::STATUS_PASS) {
                    $status = true;
                }
            }
        }
        $user->setAttribute('has_video', $status);
        $user->setAttribute('photo', collect($resourceRes)->sortByDesc('sort')->toArray());
        DB::transaction(function () use ($user) {
            $resource = rep()->resource->m()
                ->select('resource')
                ->where('related_type', Resource::RELATED_TYPE_USER_AVATAR)
                ->where('related_id', $this->userId)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();
            $user->setAttribute('avatar', $resource ? $resource->resource : '');
            mongodb('user_info')->where('_id', $this->userId)->updateOrInsert(['_id' => $this->userId], ['user_info' => $user->toArray()]);
            // mongodb('user_info')->update(['_id'=>$this->userId,'user_info'=>$user->toArray()],['upsert'=>true]);
        });
    }
}
