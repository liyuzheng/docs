<?php
/**
 * Created by PhpStorm.
 * User: good
 * Date: 2019/3/5
 * Time: 下午5:02
 */

namespace App\Http\Requests\User;


use App\Http\Requests\BaseRequest;

class UserChannelRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'channel' => 'sometimes|string',
            'traffic' => 'sometimes|string'
        ];
    }

    public function messages()
    {
        return [
            'channel.required' => trans('messages.need_channel'),
            'channel.string'   => trans('messages.channel_type_error'),
            'traffic.string'   => trans('messages.traffic_type_error'),
        ];
    }
}
