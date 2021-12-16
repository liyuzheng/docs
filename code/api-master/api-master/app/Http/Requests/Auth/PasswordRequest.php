<?php


namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class PasswordRequest extends BaseRequest
{
    private const TYPE_MOBILE = 'mobile';
    private const TYPE_EMAIL  = 'email';

    public function rules()
    {
        $rules = [
            'password'              => 'required|confirmed|max:12|min:6',
            'password_confirmation' => 'required|same:password'
        ];

        if ($this->getRouteName() == 'api.v1.auth.reset_password') {
            $otherRules = ['type' => 'in:mobile,email', 'code' => 'required|numeric'];
            $type       = request()->get('type', self::TYPE_MOBILE);

            $otherRules[$type] = $type == self::TYPE_MOBILE ? 'required|numeric'
                : 'required|email';

            $rules = array_merge($rules, $otherRules);
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'password.required'          => trans('messages.need_repassword'),
            'password.confirmed'         => trans('messages.repassword_error'),
            'password_confirmation.same' => trans('messages.repassword_error'),
            'password.max'               => trans('messages.password_many_error'),
            'password.min'               => trans('messages.password_less_error'),
            'mobile.required'            => trans('messages.need_mobile'),
            'mobile.numeric'             => "请输入正确的手机号",
            'type.in'                    => "不支持的重置方式",
            'email.required'             => "请输入邮箱",
            'email.email'                => "请输入正确的邮箱",
            'code.required'              => "请输入验证码",
            'code.numeric'               => "请输入正确的验证码",
        ];
    }
}
