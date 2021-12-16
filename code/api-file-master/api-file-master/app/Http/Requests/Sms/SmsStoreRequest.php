<?php


namespace App\Http\Requests\Sms;


use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class SmsStoreRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'mobile' => [
                'required',
                'custom_mobile'
            ],
            'type'   => [
                'required',
                Rule::in(['login', 'invite_bind', 'password'])
            ],
            'area'   => [
                'sometimes',
                'numeric'
            ]
        ];
    }

    public function messages()
    {
        return [
            'mobile.required'      => 'mobile.required',
            'mobile.custom_mobile' => 'mobile.custom_mobile',
            'type.required'        => 'type.required',
            'type.in'              => 'type.in',
            'ares.numeric'         => 'ares.numeric'
        ];
    }
}
