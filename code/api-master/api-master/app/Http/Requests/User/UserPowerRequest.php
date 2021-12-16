<?php


namespace App\Http\Requests\User;

use App\Http\Requests\BaseRequest;

class UserPowerRequest extends BaseRequest
{
    public function rules()
    {
        return ['target_uuid' => 'required'];
    }

    public function messages()
    {
        return [
            'target_uuid.required' => trans('messages.need_user_uuid')
        ];
    }
}
