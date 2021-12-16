<?php


namespace App\Http\Requests\User;


use App\Http\Requests\BaseRequest;

class InviteBuildRequest extends BaseRequest
{
    private const TYPE_MOBILE = 'mobile';
    private const TYPE_EMAIL  = 'email';

    public function rules()
    {
        $rules = [
            'type'        => ['in:mobile,email'],
            'code'        => 'required',
            'invite_code' => 'required'
        ];

        $type             = request()->get('type', self::TYPE_MOBILE);
        $certificateRules = $type == self::TYPE_MOBILE ? ['mobile' => ['required', 'numeric',]]
            : ['email' => ['required', 'email']];

        return array_merge($rules, $certificateRules);
    }

    public function messages()
    {
        return [
            'mobile.required'      => trans('messages.need_mobile'),
            'mobile.numeric'       => '请输入正确的手机号',
            'email.required'       => '请输入邮箱号',
            'email.email'          => '请输入正确的邮箱',
            'type.in'              => '不支持的绑定方式',
            'mobile.regex'         => '请输入正确的手机号',
            'code.required'        => '请输入验证码',
            'invite_code.required' => '请上传邀请码',
        ];
    }
}
