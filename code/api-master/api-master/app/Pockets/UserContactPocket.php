<?php


namespace App\Pockets;


use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use App\Models\User;
use App\Models\UserContact;
use Illuminate\Support\Facades\Validator;

class UserContactPocket extends BasePocket
{
    /**
     * 校验用户关系参数是否正确
     *
     * @param  array  $parameters
     *
     * @return ResultReturn
     */
    private function validatorUserContactByBranKCard(array $parameters)
    {
        $validatorRules = [
            'mobile'      => 'required|numeric',
            'bank'        => 'required',
            'branch_bank' => 'required',
            'province_id' => 'required',
            'city_id'     => 'required',
        ];

        $validatorMessages = [
            'mobile.required'      => trans('messages.missing_withdrawal_phone_number'),
            'mobile.numeric'       => trans('messages.incorrect_withdrawal_mobile_number'),
            'bank.required'        => trans('messages.not_choose_account_bank'),
            'branch_bank.required' => trans('messages.not_fill_in_account_bank'),
            'province_id.required' => trans('messages.not_select_city_of_account_bank'),
            'city_id.required'     => trans('messages.not_select_bank_area'),
        ];

        $validator = Validator::make($parameters, $validatorRules, $validatorMessages);
        if ($validator->fails()) {
            return ResultReturn::failed($validator->getMessageBag()->first());
        }

        return ResultReturn::success(null);
    }

    /**
     * 创建用户联系记录
     *
     * @param  User   $user
     * @param  array  $parameters
     * @param  int    $platform
     *
     * @return ResultReturn
     */
    public function createUserContactByPlatform(User $user, array $parameters, int $platform)
    {
        $userContactData = [
            'uuid'     => pocket()->util->getSnowflakeId(),
            'user_id'  => $user->id,
            'platform' => $platform,
            'account'  => $parameters['account'],
            'name'     => $parameters['name'],
            'id_card'  => $parameters['id_card'],
        ];

        if (isset($parameters['id_card'])) {
            $userContactData['id_card'] = $parameters['id_card'];
        }

        switch ($platform) {
            case UserContact::PLATFORM_BANK_CARD:
                $validatorResp = $this->validatorUserContactByBranKCard($parameters);
                if (!$validatorResp->getStatus()) {
                    return $validatorResp;
                }
                $userContactData['mobile']      = $parameters['mobile'];
                $userContactData['region']      = $parameters['bank'] . ',' . $parameters['branch_bank'];
                $userContactData['region_path'] = $regionPath = implode('_', [
                    $parameters['province_id'],
                    $parameters['city_id']
                ]);
        }

        $userContact = rep()->userContact->getQuery()
            ->create($userContactData);

        return ResultReturn::success($userContact);
    }


    /**
     * 获取用户提现账号没有则创建
     *
     * @param  User    $user
     * @param  string  $platform
     * @param  string  $account
     * @param  array   $parameters
     *
     * @return ResultReturn
     */
    public function getUserContactByAccountAndPlatform(User $user, string $platform, string $account, array $parameters)
    {
        $intPlatform = UserContact::PLATFORM_MAPPING[$platform];
        $userContact = rep()->userContact->getUserContactByAccountAndPlatform($user, $account, $platform);
        if (isset($parameters['id_card']) && app()->environment('production')) {
            $checkResp = pocket()->wgcYunPay->checkIdCardAndName($parameters['name'],
                $parameters['id_card']);
            if (!$checkResp->getStatus()) {
                return $checkResp->setMessage(trans('messages.name_and_id_not_match'));
            }
        }

        if (!$userContact) {
            $userContactResp = $this->createUserContactByPlatform($user,
                $parameters, $intPlatform);
            if (!$userContactResp->getStatus()) {
                return $userContactResp;
            }

            $userContact = $userContactResp->getData();
        }

        if ($intPlatform == UserContact::PLATFORM_ALIPAY && !$userContact->id_card) {
            $userContact->update(['id_card' => $parameters['id_card']]);
        }

        return ResultReturn::success($userContact);
    }
}
