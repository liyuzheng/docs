<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\User\UserBlacklistRequest;

/**
 * Class BlackController
 * @package App\Http\Controllers
 */
class BlackController extends BaseController
{

    /**
     * 批量拉黑
     *
     * @param  UserBlacklistRequest  $request
     *
     * @return JsonResponse
     */
    public function blacklistStore(UserBlacklistRequest $request)
    {
        $userId = $this->getAuthUserId();
        $tuuids = $request->post('uuids');
        if (in_array(config('custom.little_helper_uuid'), $tuuids)) {
            return api_rr()->forbidCommon(trans('messages.not_black_system_helper'));
        }

        $tUserIds = rep()->user->getIdsByUUids($tuuids);
        $result   = pocket()->blacklist->batchAddOrDelete('add', $userId, $tUserIds);

        if ($result->getStatus() == false) {
            return api_rr()->notFoundResult($result->getMessage());
        }

        return api_rr()->postOK([]);
    }

    /**
     * 批量取消拉黑
     *
     * @param  UserBlacklistRequest  $request
     *
     * @return JsonResponse
     */
    public function blacklistDel(UserBlacklistRequest $request)
    {
        $userId   = $this->getAuthUserId();
        $tuuids   = $request->post('uuids');
        $tUserIds = rep()->user->getIdsByUUids($tuuids);
        $result   = pocket()->blacklist->batchAddOrDelete('delete', $userId, $tUserIds);

        if ($result->getStatus() == false) {
            return api_rr()->notFoundResult($result->getMessage());
        }

        return api_rr()->deleteOK([]);
    }

    /**
     * 取消对某个用户的拉黑
     *
     * @param  Request  $request
     * @param  int      $uuid
     *
     * @return JsonResponse
     */
    public function destroyBlacklistsUsers(Request $request, int $uuid)
    {
        $userId   = $this->getAuthUserId();
        $tUserIds = rep()->user->getIdsByUUids([$uuid]);
        $result   = pocket()->blacklist->batchAddOrDelete('delete', $userId, $tUserIds);

        if ($result->getStatus() == false) {
            return api_rr()->notFoundResult($result->getMessage());
        }

        return api_rr()->deleteOK([]);
    }
}
