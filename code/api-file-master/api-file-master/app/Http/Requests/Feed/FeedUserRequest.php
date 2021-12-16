<?php


namespace App\Http\Requests\Feed;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class FeedUserRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'type'      => [
                'sometimes',
                'string',
                Rule::in(['active_user', 'lbs_user', 'charm_girl', 'new_user'])
            ],
            'key'       => [
                'sometimes',
                'string',
                Rule::in(['lbs_online', 'lbs_new', 'lbs_charm_first', 'lbs_vip', 'lbs_girl', 'lbs_all'])
            ],
            'sort'      => [
                'sometimes',
                'string',
                Rule::in(['common', 'except_inactive', 'new_user', 'charm_first'])
            ],
            'is_member' => [
                'sometimes',
                'numeric',
                Rule::in([0, 1])
            ],
            'city_name' => [
                'sometimes',
                'string',
            ],
            'limit'     => [
                'sometimes',
                'numeric',
                'max:50'
            ]
        ];

    }

    public function messages()
    {
        return [
            'type.string'       => 'type.string',
            'type.in'           => 'type.in',
            'key.required'      => 'key.required',
            'key.string'        => 'key.string',
            'key.in'            => 'key.in',
            'sort.string'       => 'sort.string',
            'sort.in'           => 'sort.in',
            'is_member.numeric' => 'is_member.numeric',
            'is_member.in'      => 'is_member.in',
            'city_name.string'  => 'city_name.string',
            'limit.numeric'     => 'limit.numeric',
            'limit.max'         => 'limit.max',
        ];
    }
}
