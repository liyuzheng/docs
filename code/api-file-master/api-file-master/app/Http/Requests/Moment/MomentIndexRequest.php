<?php


namespace App\Http\Requests\Moment;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class MomentIndexRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'topic_uuid' => [
                'sometimes',
                'numeric'
            ],
            'type'       => [
                'sometimes',
                Rule::in(['hot', 'new', 'lbs']),
            ],
            'limit'      => [
                'sometimes',
                'numeric',
                'max:20'
            ],
            'gender'     => [
                'sometimes',
                'numeric',
                Rule::in([0, 1, 2])
            ]
        ];
    }

    public function messages()
    {
        return [
            'topic_uuid.numeric' => 'topic_uuid.numeric',
            'type.in'            => 'type.in',
            'limit.numeric'      => 'limit.numeric',
            'limit.max'          => 'limit.max',
            'gender.numeric'     => 'gender.numeric',
            'gender.in'          => 'gender.in'
        ];
    }
}
