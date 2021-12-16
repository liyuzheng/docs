<?php


namespace App\Http\Requests\User;


use App\Http\Requests\BaseRequest;

class UserFollowRequest extends BaseRequest
{
    public function rules()
    {
        return ['uuids' => 'required'];
    }

    public function messages()
    {
        return [
            'uuids.required' => trans('messages.need_follow')
        ];
    }
}
