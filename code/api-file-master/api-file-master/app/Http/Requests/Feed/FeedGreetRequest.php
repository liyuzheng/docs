<?php


namespace App\Http\Requests\Feed;

use App\Http\Requests\BaseRequest;

class FeedGreetRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'uuids' => [
                'sometimes',
                'array'
            ]
        ];

    }

    public function messages()
    {
        return [
            'uuids.array' => 'uuids.array'
        ];
    }
}
