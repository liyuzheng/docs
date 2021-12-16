<?php


namespace App\Http\Requests\Uploads;


use App\Http\Requests\BaseRequest;

class QrcodePosterRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'code' => 'required|string',
            'link' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'code.required' => trans('messages.need_invite_code'),
            'code.string'   => trans('messages.invite_code_type_error'),
            'link.required' => trans('messages.need_picture_type'),
            'link.string'   => trans('messages.invite_code_type_error')
        ];
    }
}
