<?php


namespace App\Http\Requests\Report;

use App\Http\Requests\BaseRequest;

class ReportRequest extends BaseRequest
{
    public function rules()
    {
        $data = [
            'content' => 'required|max:128'
        ];
        if (version_compare(user_agent()->clientVersion, '2.0.0', '>=')) {
            $data = [
                'tags' => 'required'
            ];
        }

        return $data;
    }

    public function messages()
    {
        $messages = [
            'content.required' => trans('messages.need_report_msg'),
            'content.max'      => trans('messages.report_msg_many'),
        ];
        if (version_compare(user_agent()->clientVersion, '2.0.0', '>=')) {
            $messages = [
                'tags.required' => trans('messages.need_report_type')
            ];
        }

        return $messages;
    }
}
