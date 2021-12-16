<?php

return [
    'access_key' => env('FENGKONG_ACCESS_KEY'),
    'video'      => [
        'check'  => [
            'api'    => 'http://video-api.fengkongcloud.com/v2/saas/anti_fraud/video',
            'method' => 'POST'
        ],
        'result' => [
            'api'    => 'http://video-api.fengkongcloud.com/v2/saas/anti_fraud/query_video',
            'method' => 'POST'
        ],
    ]
];
