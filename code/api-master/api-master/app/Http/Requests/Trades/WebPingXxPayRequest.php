<?php


namespace App\Http\Requests\Trades;


use App\Http\Requests\BaseRequest;

class WebPingXxPayRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'good_id' => 'required|exists:goods,uuid',
            'user_id' => 'required|exists:user,uuid',
        ];
    }

    public function messages()
    {
        return [
            'good_id.required' => trans('messages.need_transaction_good'),
            'good_id.exists'   => trans('messages.need_transaction_good'),
            'user_id.required' => trans('messages.need_pay_user'),
            'user_id.exists'   => trans('messages.pay_user_disappear'),
        ];
    }
}
