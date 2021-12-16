<?php

return [
    // HTTP 请求的超时时间（秒）
    'timeout'     => 5.0,

    // 默认发送配置
    'default'     => [
        // 网关调用策略，默认：顺序调用
        'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,

        // 默认可用的发送网关
        'gateways' => [
            'aliyun',
        ],
    ],
    // 可用的网关配置
    'gateways'    => [
        'errorlog' => [
            'file' => '/tmp/easy-sms.log',
        ],
        'yunpian'  => [
            'api_key' => '824f0ff2f71cab52936axxxxxxxxxx',
        ],
        'aliyun'   => [
            'access_key_id'     => env('SMS_ALIYUN_ACCESS_KEY_ID', ''),
            'access_key_secret' => env('SMS_ALIYUN_ACCESS_KEY_SECRET', ''),
            'sign_name'         => '小圈',
        ]
        //...
    ],
    //腾域短信
    'tengYu'      => [
        'otherUserName'    => env('TENGYU_OTHER_USER_NAME', ''),
        'otherPassword'    => env('TENGYU_OTHER_PASSWORD', ''),
        'bjUserName'       => env('TENGYU_BEIJING_USER_NAME', ''),
        'bjPassword'       => env('TENGYU_BEIJING_PASSWORD', ''),
        'batchSendMessage' => [
            'api'    => 'http://send.it1688.com.cn:8001/sms/api/batchSendMessage',
            'method' => 'POST'
        ],
        'sendMessage'      => [
            'api'    => 'http://send.it1688.com.cn:8001/sms/api/sendMessage',
            'method' => 'POST'
        ]
    ],
    'attribution' => [
        'api'        => 'https://service-av27cw4h-1257598706.ap-shanghai.apigateway.myqcloud.com/release/mobile',
        'secret_id'  => env('ATTRIBUTION_SECRET_ID'),
        'secret_key' => env('ATTRIBUTION_SECRET_KEY')
    ],
    'yuanZhi'     => [
        'method'    => 'POST',
        'api'       => 'http://101.132.177.59:9090/sms/batch/v1',
        'appKey'    => env('YUANZHI_APP_KEY', ''),
        'appCode'   => env('YUANZHI_APP_CODE', ''),
        'appSecret' => env('YUANZHI_APP_SECRET', '')
    ]
];
