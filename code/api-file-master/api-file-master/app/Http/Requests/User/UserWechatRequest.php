<?php


namespace App\Http\Requests\User;

use App\Http\Requests\BaseRequest;

class UserWechatRequest extends BaseRequest
{
    public function rules()
    {
        return ['type' => 'required'];
    }

    public function messages()
    {
        return [
            'type.required' => trans('messages.request_params_lack_error')
        ];
    }
}
