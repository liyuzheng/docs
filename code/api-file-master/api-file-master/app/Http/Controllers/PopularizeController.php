<?php


namespace App\Http\Controllers;


use Carbon\Carbon;

class PopularizeController extends BaseController
{
    /**
     * 客户端是否上报ocpc
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function isOcpcRecharge()
    {
        $userId         = $this->getAuthUserId();
        $authUser       = rep()->user->getById($userId, ['created_at']);
        $authTimestamp  = $authUser->created_at->timestamp;
        $todayTimestamp = Carbon::today()->timestamp;
        $isUpload       = (bool)($authTimestamp >= $todayTimestamp);

        return api_rr()->postOK(['is_upload' => $isUpload]);
    }
}
