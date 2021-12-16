<?php


namespace App\Http\Requests\Uploads;


use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UploadsSingleRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'file' => 'required|file',
            'type' => [
                'required',
                Rule::in(['user_avatar', 'user_photo', 'qrcode', 'watermark', 'report', 'feedback', 'error_report', 'user_video', 'moment', 'banner', 'face_auth'])
            ],
        ];
    }

    public function messages()
    {
        return [
            'file.required' => trans('messages.need_file'),
            'file.file'     => trans('messages.file_type_error'),
            'type.required' => trans('messages.need_picture_type'),
            'type.in'       => trans('messages.type_type_error')
        ];
    }
}
