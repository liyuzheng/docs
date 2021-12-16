<?php


namespace App\Http\Requests\Trades;


use App\Http\Requests\BaseRequest;

class GooglePayRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'product_id'   => 'required',
            'package_name' => 'required',
            'token'        => 'required',
        ];
    }

    public function messages()
    {
        return [
            'product_id.required'   => trans('messages.need_transaction_good'),
            'package_name.required' => '缺少包名',
            'token.required'        => trans('messages.need_transaction_voucher'),
        ];
    }
}
