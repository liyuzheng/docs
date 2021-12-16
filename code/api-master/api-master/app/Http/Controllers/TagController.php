<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\User\UserTagRequest;

/**
 * Class TagController
 * @package App\Http\Controllers
 */
class TagController extends BaseController
{
    /**
     * 获取在一起的关系
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function inRelation(Request $request)
    {
        $tags = rep()->tag->m()
            ->select(['uuid', 'name', 'icon'])
            ->where('type', Tag::TYPE_RELATION)
            ->get();
        foreach ($tags as $tag) {
            $tag->setAttribute('icon', cdn_url($tag->icon));
        }

        return api_rr()->getOK($tags->toArray());
    }

    /**
     * 获取用户评价的标签
     *
     * @param  Request  $request
     * @param  int      $uuid
     *
     * @return JsonResponse
     */
    public function userEvaluate(Request $request, int $uuid)
    {
        $users = pocket()->user->getUserInfoByUUID($uuid);
        if (!$users->getStatus()) {
            return api_rr()->notFoundUser();
        }
        $targetUser = $users->getData();
        $type       = $targetUser->gender == User::GENDER_MAN ? Tag::TYPE_TAG_MAN : Tag::TYPE_TAG_WOMEN;
        $tags       = rep()->tag->m()
            ->select(['uuid', 'name', 'icon'])
            ->where('type', $type)
            ->get();

        return api_rr()->getOK($tags->toArray());
    }

    /**
     * 给用户添加标签
     *
     * @param  UserTagRequest  $request
     * @param  int             $uuid
     *
     * @return JsonResponse
     */
    public function tags(UserTagRequest $request, int $uuid)
    {
        $userId = $this->getAuthUserId();
        $rUser  = rep()->user->getByUUid($uuid);
        if (!$rUser) {
            return api_rr()->notFoundUser(trans('messages.target_user_not_exists'));
        }
        $exist = rep()->userEvaluate->m()
            ->where('user_id', $userId)
            ->where('target_user_id', $rUser->id)
            ->count();
        if ($exist) {
            return api_rr()->forbidCommon(trans('messages.have_evaluation_notice'));
        }
        $type = $request->get('type');
        $tags = $request->post();
        switch ($type) {
            case "evaluate":
                pocket()->user->setEvaluateToUser($userId, $rUser->id, $tags);
        }

        return api_rr()->postOK([]);
    }

}
