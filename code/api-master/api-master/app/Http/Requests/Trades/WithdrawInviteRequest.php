<?php


namespace App\Http\Requests\Trades;


use App\Http\Requests\BaseRequest;

class WithdrawInviteRequest extends BaseRequest
{

    public function rules()
    {
        return [
            'amount'               => 'required|numeric|min:100|max:100001',
            'name'                 => 'required',
            'account'              => 'required|confirmed',
            'account_confirmation' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'amount.required'               => trans('messages.need_withdraw'),
            'amount.numeric'                => trans('messages.withdraw_type_error'),
            'amount.min'                    => trans('messages.withdraw_less_error'),
            'amount.max'                    => trans('messages.withdraw_many_error'),
            'name.required'                 => trans('messages.need_withdraw_person'),
            'account.required'              => trans('messages.need_withdraw_account'),
            'account.confirmed'             => trans('messages.retype_withdraw_error'),
            'account_confirmation.required' => trans('messages.need_retype_withdraw')
        ];
    }
}
