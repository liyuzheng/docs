<?php


namespace App\Http\Requests\Check;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class CheckStoreRequest extends BaseRequest
{
    public function rules()
    {
        $type = request()->get('type', 'all');

        $rules = [
            'type' => ['required', Rule::in(['all', 'wechat'])]
        ];

        switch ($type) {
            case 'all':
                $rules = array_merge($rules, [
                    'height'  => 'required',
                    'weight'  => 'required',
                    'job'     => 'required',
                    'intro'   => 'sometimes',
                    'wechat'  => 'required',
                    'qr_code' => 'required'
                ]);
                break;
            case 'wechat':
                $rules = array_merge($rules, [
                    'wechat'  => 'required',
                    'qr_code' => 'required'
                ]);
                break;
            default:
                break;
        }

        return $rules;
    }

    public function messages()
    {
        $type     = request()->get('type', 'all');
        $messages = [
            'type.required' => trans('messages.need_upload_type'),
            'type.in'       => trans('messages.type_type_error')
        ];

        switch ($type) {
            case 'all':
                $messages = array_merge($messages, [
                    'height.required'  => trans('messages.need_height'),
                    'weight.required'  => trans('messages.need_weight'),
                    'job.required'     => trans('messages.need_job'),
                    'intro.required'   => trans('messages.need_intro'),
                    'wechat.required'  => trans('messages.need_wechat'),
                    'qr_code.required' => trans('messages.need_upload_qrcode')
                ]);
                break;
            case 'wechat':
                $messages = array_merge($messages, [
                    'wechat.required'  => trans('messages.need_wechat'),
                    'qr_code.required' => trans('messages.need_upload_qrcode')
                ]);
            default:
        }

        return $messages;
    }
}
