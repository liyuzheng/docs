<?php

return [
    'api'  => [
        'user'   => [
            'create'       => [
                'method' => 'POST',
                'api'    => 'https://api.netease.im/nimserver/user/create.action'
            ],
            'update'       => [
                'method' => 'POST',
                'api'    => 'https://api.netease.im/nimserver/user/update.action'
            ],
            'refreshToken' => [
                'method' => 'POST',
                'api'    => 'https://api.netease.im/nimserver/user/refreshToken.action'
            ],
            'block'        => [
                'method' => 'POST',
                'api'    => 'https://api.netease.im/nimserver/user/block.action'
            ],
            'unblock'      => [
                'method' => 'POST',
                'api'    => 'https://api.netease.im/nimserver/user/unblock.action'
            ],
            'getUinfos'    => [
                'method' => 'POST',
                'api'    => 'https://api.netease.im/nimserver/user/getUinfos.action'
            ],
            'updateUinfo'  => [
                'method' => 'POST',
                'api'    => 'https://api.netease.im/nimserver/user/updateUinfo.action'
            ]
        ],
        'msg'    => [
            'sendMsg'      => [
                'method' => 'POST',
                'api'    => 'https://api.netease.im/nimserver/msg/sendMsg.action'
            ],
            'sendBatchMsg' => [
                'method' => 'POST',
                'api'    => 'https://api.netease.im/nimserver/msg/sendBatchMsg.action'
            ],
            'recall'       => [
                'method' => 'POST',
                'api'    => 'https://api.netease.im/nimserver/msg/recall.action'
            ],
        ],
        'upload' => [
            'image' => [
                'method' => 'POST',
                'api'    => 'https://api.netease.im/nimserver/msg/upload.action'
            ]
        ]
    ],
    'keys' => [
        'dun' => [
            'secret_key'               => env('NETEASE_DUN_SECRET_KEY', ''),
            'secret_id'                => env('NETEASE_DUN_SECRET_ID', ''),
            'business_id'              => env('NETEASE_DUN_BUSINESS_ID', ''),
            'moment_image_business_id' => env('NETEASE_DUN_MOMENT_IMAGE_BUSINESS_ID', ''),
            'user_video'               => env('NETEASE_DUN_USER_VIDEO', ''),
            'moment_pic'               => env('NETEASE_DUN_MOMENT_PIC', ''),
            'user_photo'               => env('NETEASE_DUN_USER_PHOTO', ''),
            'user_avatar'              => env('NETEASE_DUN_USER_AVATAR', ''),
            'moment_text'              => env('NETEASE_DUN_MOMENT_TEXT', ''),
            'user_intro'               => env('NETEASE_DUN_USER_INTRO', ''),
            'user_nickname'            => env('NETEASE_DUN_USER_NICKNAME', ''),
            'chat_pic'                 => env('NETEASE_DUN_CHAT_PIC', ''),
            'chat_text'                => env('NETEASE_DUN_CHAT_TEXT', ''),
        ]
    ]
];
