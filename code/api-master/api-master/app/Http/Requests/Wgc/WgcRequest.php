<?php


namespace App\Http\Requests\Wgc;

use App\Http\Requests\BaseRequest;

class WgcRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'trade_withdraw_id' => ['required']
        ];
    }

    public function messages()
    {
        return [
            'trade_withdraw_id.required' => trans('messages.need_withdraw_id'),
        ];
    }
}
