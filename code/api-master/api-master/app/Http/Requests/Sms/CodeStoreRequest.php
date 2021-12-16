<?php


namespace App\Http\Requests\Sms;


use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class CodeStoreRequest extends BaseRequest
{
    public function rules()
    {
        $rules = [
            'type' => ['required', Rule::in(['login', 'invite_bind', 'password'])],
            'area' => ['sometimes', 'numeric'],
        ];

        $certificateRules = $this->getRouteName() == 'api.v1.emails.store'
            ? ['email' => ['required', 'email']]
            : ['mobile' => ['required', 'numeric',]];

        return array_merge($certificateRules, $rules);
    }

    public function messages()
    {
        return [
            'mobile.required' => trans('messages.need_mobile'),
            'mobile.numeric'  => trans('messages.mobile_error'),
            'type.required'   => trans('messages.need_short_msg_type'),
            'type.in'         => trans('messages.short_msg_type_error'),
            'ares.numeric'    => trans('messages.mobile_area_type_error'),
            'email.required'  => '请输入邮箱',
            'email.email'     => '请输入正确的邮箱',
        ];
    }
}
