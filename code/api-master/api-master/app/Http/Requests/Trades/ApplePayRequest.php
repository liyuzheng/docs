<?php


namespace App\Http\Requests\Trades;


use App\Http\Requests\BaseRequest;

class ApplePayRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'trade_no' => 'required',
            'evidence' => 'required',
            'good_id'  => 'required',
        ];
    }

    public function messages()
    {
        return [
            'trade_no.required' => trans('messages.need_transaction_flow'),
            'evidence.required' => trans('messages.need_transaction_voucher'),
            'good_id.required'  => trans('messages.need_transaction_good'),
        ];
    }
}
