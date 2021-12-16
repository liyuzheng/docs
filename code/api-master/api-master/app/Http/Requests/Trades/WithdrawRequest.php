<?php


namespace App\Http\Requests\Trades;


use App\Http\Requests\BaseRequest;
use App\Models\UserContact;

class WithdrawRequest extends BaseRequest
{

    public function rules()
    {
        $rules = [
            'amount'               => ['required', 'numeric', 'max:100001'],
            'name'                 => 'required',
            'account'              => 'required|confirmed',
            'account_confirmation' => 'required',
        ];

        if (request()->has('type')
            && request('type') == UserContact::PLATFORM_STR_ALIPAY) {
            $rules['id_card'] = [
                'required',
                'regex:/^[1-9]\d{5}\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}$|^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$/'
            ];
        }

        $rules['amount'][] = !app()->environment('production') ? 'min:2' : 'min:100';

        return $rules;
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
            'account_confirmation.required' => trans('messages.need_retype_withdraw'),
            'id_card.required'              => trans('messages.need_id_card'),
            'id_card.regex'                 => trans('messages.id_card_length_error'),
        ];
    }
}
