<?php


namespace App\Http\Requests\Banner;

use App\Http\Requests\BaseRequest;

class BannerIndexRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'limit' => [
                'sometimes',
                'numeric',
                'max:20'
            ],
            'page'  => [
                'sometimes',
                'numeric',
            ]
        ];
    }

    public function messages()
    {
        return [
            'limit.numeric' => 'limit.numeric',
            'limit.max'     => 'limit.max',
            'page.numeric'  => 'page.numeric'
        ];
    }
}
