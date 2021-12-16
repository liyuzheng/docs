<?php


namespace App\Http\Requests\Moment;

use App\Http\Requests\BaseRequest;

class MomentReportRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'content' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'content.required' => trans('messages.need_insert_content'),
        ];
    }
}
