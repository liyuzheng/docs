<?php


namespace App\Http\Controllers;


use App\Http\Requests\User\InviteBuildRequest;
use App\Models\Discount;
use App\Models\InviteBuildRecord;
use App\Models\InviteRecord;
use App\Models\Sms;
use App\Models\Task;
use Illuminate\Http\Request;

class InviteController extends BaseController
{
    /**
     * app中的邀请详情页
     *
     * @param  Request  $request
     * @param  int      $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function invite(Request $request, int $uuid)
    {
        $user       = rep()->user->getById($request->user()->id);
        $userDetail = rep()->userDetail->getByUserId($user->id);
        [$welfare, $receiveType, $rules] = pocket()->inviteRecord->getIncomeRecords($user,
            $userDetail, InviteRecord::CHANNEL_APP);

        return api_rr()->getOK([
            'welfare'      => $welfare,
            'receive_type' => $receiveType,
            'invite_code'  => $userDetail->invite_code,
            'rules'        => $rules,
            'qr_code'      => pocket()->resource->getUserInviteCodeQrCode($user->id)
        ]);
    }

    /**
     * 赠送折扣邀请
     *
     * @param  Request  $request
     * @param  int      $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function discountInvite(Request $request, int $uuid)
    {
        $user          = $request->user();
        $inviteCount   = rep()->task->getQuery()->where('user_id', $user->id)
            ->where('related_type', Task::RELATED_TYPE_MEMBER_DISCOUNT)
            ->where('status', Task::STATUS_DEFAULT)->count();
        $ownedDiscount = rep()->discount->getQuery()->where('user_id', $user->id)
            ->where('type', Discount::TYPE_CAN_OVERLAP)->where('done_at', 0)
            ->where('related_type', Discount::RELATED_TYPE_INVITE_PRIZE)
            ->sum('discount');
        $userDetail    = rep()->userDetail->getQuery()->select('user_id', 'invite_code')
            ->where('user_id', $user->id)->first();

        return api_rr()->getOK([
            'invite_code'       => $userDetail->getRawOriginal('invite_code'),
            'invite_count'      => $inviteCount,
            'owned_discount'    => (int)sprintf('%.0f', ($ownedDiscount > 0.5 ? 0.5 : $ownedDiscount) * 100),
            'discount_max'      => 50,
            'invite_qrcode_url' => 'http://i.xiaoquann.com/invite_slb?uuid='
                . $user->uuid
        ]);
    }

    /**
     * 小程序中的邀请收益
     *
     * @param  Request  $request
     * @param  int      $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function appletInvite(Request $request, int $uuid)
    {
        $user       = $request->user();
        $userDetail = rep()->userDetail->getByUserId($user->id);
        $welfare    = pocket()->inviteRecord->getIncomeRecords($user,
            $userDetail, InviteRecord::CHANNEL_APPLET);

        return api_rr()->getOK($welfare);
    }

    /**
     * 领取会员
     *
     * @param  Request  $request
     * @param  int      $uuid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function taskInviteMember(Request $request, int $uuid)
    {
        $user  = $request->user();
        $tasks = rep()->task->m()->where('user_id', $user->id)->whereIn(
            'related_type', [Task::RELATED_TYPE_MAN_INVITE_REG, Task::RELATED_TYPE_MAN_INVITE_MEMBER]
        )->where('status', Task::STATUS_DEFAULT)->where('done_at', 0)->get();

        if (!$tasks->count()) {
            return api_rr()->forbidCommon(trans('messages.dont_can_receive_member'));
        }

        $drawMemberResp = pocket()->inviteRecord->postTaskInviteMemberByUser($user, $tasks);
        if (!$drawMemberResp->getStatus()) {
            return api_rr()->customFailed($drawMemberResp->getMessage(), $drawMemberResp->getData());
        }

        return api_rr()->getOK([], trans('messages.receive_success'));
    }

    /**
     * 获取当前用户邀请的用户列表
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function users(Request $request)
    {
        $user = $request->user();
        //        $channel       = InviteRecord::CHANNEL_MAPPING[$request->query('channel', InviteRecord::CHANNEL_STR_APP)];
        $inviteRecords = rep()->inviteRecord->m()->select('id', 'target_user_id', 'created_at')
            ->where('user_id', $user->id)
            //            ->where('channel', $channel)
            ->where('type', InviteRecord::TYPE_USER_REG)
            ->orderBy('id', 'desc')
            ->paginate(20);

        $users    = pocket()->inviteRecord->getInviteUsersAndBuildReward($user, $inviteRecords);
        $nextPage = $inviteRecords->currentPage() + 1;

        return api_rr()->getOK(pocket()->util->getPaginateFinalData($users, $nextPage));
    }

    /**
     * 邀请绑定手机号
     *
     * @param  InviteBuildRequest  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function bind(InviteBuildRequest $request)
    {
        $authField = $request->get('type', 'mobile');
        if (pocket()->sms->whetherMobileBlock($request->{$authField})) {
            return api_rr()->forbidCommon(trans('messages.frequently_request_code_error'));
        }

        $smsRespData = rep()->sms->getSmsByTypeAndAuthFiled(Sms::TYPE_INVITE_BIND,
            $request->{$authField}, $authField, ['id', $authField, 'code']);

        if (!$smsRespData || ($request->code != $smsRespData->code)) {
            pocket()->sms->recordMobileErrorTimes($request->{$authField});

            return api_rr()->forbidCommon(trans('messages.not_have_code_error'));
        }

        $smsRespData->update(['used_at' => time()]);
        $buildResp = pocket()->inviteRecord->postInviteBuildRecord($request->{$authField},
            $authField, $request->invite_code);
        if (!$buildResp->getStatus()) {
            return api_rr()->forbidCommon($buildResp->getMessage());
        }

        return api_rr()->postOK((object)[]);
    }
}
