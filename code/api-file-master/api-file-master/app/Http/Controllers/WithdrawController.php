<?php


namespace App\Http\Controllers;

use App\Constant\ApiBusinessCode;
use App\Http\Requests\Trades\WithdrawInviteRequest;
use App\Models\TradeWithdraw;
use App\Models\UserContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Trades\WithdrawRequest;

/**
 * Class WithdrawController
 * @package App\Http\Controllers
 */
class WithdrawController extends BaseController
{
    /**
     * 收入提现
     *
     * @param  WithdrawRequest  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function incomeWithdraw(WithdrawRequest $request)
    {
        $user     = $request->user();
        $platform = $request->query('type', UserContact::PLATFORM_STR_ALIPAY);

        $userContactResp = pocket()->userContact->getUserContactByAccountAndPlatform(
            $user, $platform, $request->account, $request->all());
        if (!$userContactResp->getStatus()) {
            return api_rr()->requestParameterError($userContactResp->getMessage());
        }

        $type         = TradeWithdraw::TYPE_INCOME;
        $userContact  = $userContactResp->getData();
        $amount       = $request->amount * 100;
        $withdrawResp = DB::transaction(function () use ($user, $amount, $userContact, $type) {
            return pocket()->tradeWithdraw->createWithdrawRecordByUser(
                $user, $userContact, $amount, $type);
        });

        if ($withdrawResp->getStatus()) {
            pocket()->common->commonQueueMoreByPocketJob(pocket()->stat, 'statUserWithdraw',
                [$withdrawResp->getData()->id], 10);
        } else {
            return api_rr()->forbidCommon($withdrawResp->getMessage());
        }

        return api_rr()->postOK((object)[]);
    }

    /**
     * 邀请提现
     *
     * @param  WithdrawRequest  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function inviteWithdraw(WithdrawRequest $request)
    {
        $user     = $request->user();
        $platform = $request->query('type', UserContact::PLATFORM_STR_ALIPAY);

        $userContactResp = pocket()->userContact->getUserContactByAccountAndPlatform(
            $user, $platform, $request->account, $request->all());
        if (!$userContactResp->getStatus()) {
            return api_rr()->requestParameterError($userContactResp->getMessage());
        }

        $type         = TradeWithdraw::TYPE_INVITE;
        $userContact  = $userContactResp->getData();
        $amount       = $request->amount * 100;
        $withdrawResp = DB::transaction(function () use ($user, $amount, $userContact, $type) {
            return pocket()->tradeWithdraw->createWithdrawRecordByUser(
                $user, $userContact, $amount, $type);
        });

        if ($withdrawResp->getStatus()) {
            pocket()->common->commonQueueMoreByPocketJob(pocket()->stat, 'statUserWithdraw',
                [$withdrawResp->getData()->id], 10);
        } else {
            return api_rr()->forbidCommon($withdrawResp->getMessage());
        }

        return api_rr()->postOK((object)[]);
    }

    /**
     * 提现记录
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function records(Request $request)
    {
        $user  = $request->user();
        $query = rep()->tradeWithdraw->getQuery()->select('ori_amount', 'type', 'created_at')
            ->where('user_id', $user->id)->orderBy('created_at', 'desc');
        if ($request->has('type')) {
            $type  = TradeWithdraw::TYPE_STR_MAPPING[$request->type];
            $query = $query->where('type', $type);
        }

        $records  = $query->paginate(20);
        $nextPage = $records->currentPage() + 1;
        foreach ($records as $record) {
            $record->setAttribute('related_type_tips',
                TradeWithdraw::RELATED_TYPE_TIPS_MAPPING[$record->getRawOriginal('type')]);
            $record->setAttribute('unit', trans('messages.currency_unit'));
        }

        return api_rr()->getOK(pocket()->util->getPaginateFinalData(
            $records->items(), $nextPage));
    }

    /**
     * 获取提现开户行区域
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function region(Request $request)
    {
        $parent  = $request->get('parent', 0);
        $regions = rep()->region->getQuery()->select('id', 'parent_id', 'name')
            ->where('parent_id', $parent)->get();

        return api_rr()->getOK($regions);
    }

    /**
     * 获取最后一次提现的账号
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function account(Request $request)
    {
        $user     = $request->user();
        $platform = $request->query('type', UserContact::PLATFORM_STR_ALIPAY);
        $contact  = rep()->userContact->getQuery()->where('user_id', $user->id)->where('platform',
            UserContact::PLATFORM_MAPPING[$platform])->orderBy('id', 'desc')
            ->first();

        $contactData['name'] = $contactData['account'] = $contactData['mobile'] =
        $contactData['bank'] = $contactData['branch_bank'] = '';

        $contactData['province_id'] = $contactData['city_id'] = 0;
        $contactData['province']    = '-选择省-';
        $contactData['city']        = '-选择市-';

        if ($contact) {
            if ($contact->region) {
                [$bank, $branch_bank] = explode(',', $contact->region);
                $contactData['bank']        = $bank;
                $contactData['branch_bank'] = $branch_bank;
            }

            if ($contact->region_path) {
                [$province, $city] = explode('_', $contact->region_path);
                $addresses                  = rep()->region->getQuery()->whereIn('id', [$province, $city])->get();
                $contactData['province']    = $addresses->find($province)->name;
                $contactData['province_id'] = $province;
                $contactData['city']        = $addresses->find($city)->name;
                $contactData['city_id']     = $city;
            }

            $contactData['name']    = $contact->name;
            $contactData['account'] = $contact->account;
            $contactData['mobile']  = $contact->mobile;
            $contactData['id_card'] = $contact->id_card;
        }

        return api_rr()->getOK($contactData);
    }
}
