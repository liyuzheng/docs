<?php


namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class AuthRequest extends BaseRequest
{
    private const TYPE_MOBILE_SMS   = 'mobile_sms';
    private const TYPE_MOBILE_QUICK = 'mobile_quick';
    private const TYPE_PASSWORD     = 'password';
    private const TYPE_EMAIL        = 'email';
    private const TYPE_GOOGLE       = 'google';

    public function rules()
    {
        $type  = request()->get('type', self::TYPE_MOBILE_SMS);
        $rules = ['type' => ['required', Rule::in(['mobile_quick', 'mobile_sms', 'password', 'email', 'google'])],];

        switch ($type) {
            case self::TYPE_GOOGLE:
            case self::TYPE_MOBILE_QUICK:
                $rules = array_merge($rules, ['token' => 'required']);
                break;
            case self::TYPE_MOBILE_SMS:
                $rules = array_merge($rules, ['mobile' => ['required', 'numeric']]);
                break;
            case self::TYPE_PASSWORD:
                $rules = array_merge($rules, [
                    'password' => ['required'],
                    'mobile'   => ['required_without:email', 'numeric'],
                    'email'    => ['required_without:mobile', 'email']
                ]);
                break;
            case self::TYPE_EMAIL:
                $rules = array_merge($rules, ['email' => ['required', 'email']]);
                break;
            default:
                throw new HttpResponseException(api_rr()->forbidCommon('不支持的登录方式'));
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'type.required'           => trans('messages.auth_disappear'),
            'type.in'                 => trans('messages.auth_error'),
            'mobile.required'         => trans('messages.need_mobile'),
            'mobile.required_without' => '请输入邮箱或手机号',
            'token.required'          => trans('messages.need_login_token'),
            'password.required'       => 'password为必填项',
            'email.required'          => '请输入邮箱',
            'email.required_without'  => '请输入邮箱或手机号',
            'email.email'             => '请输入一个正确的邮箱',
        ];
    }
}
