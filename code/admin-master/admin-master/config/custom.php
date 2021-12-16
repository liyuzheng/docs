<?php

return [
    'internal'                 => [
        'cold_start_users_url'      => env('COLD_START_API_DOMAIN') . '/internal/users/%s',
        'cold_start_message_cc_url' => env('COLD_START_API_DOMAIN') . '/internal/messages/cc',
        'update_users_location_url' => env('COLD_START_API_DOMAIN') . '/internal/users/%s/location',
        'update_users_active_url'   => env('COLD_START_API_DOMAIN') . '/internal/users/%s/active-at',
        'sync_users_active_url'     => env('COLD_START_API_DOMAIN') . '/internal/users/%s/sync',
        'update_users_switches'     => env('COLD_START_API_DOMAIN') . '/internal/users/%s/switches',
        'update_users_wechat'       => env('COLD_START_API_DOMAIN') . '/internal/users/%s/we-chat',
    ],
    'api_domain'               => env('APP_URL', 'http://api-dev.wqdhz.com/'),
    'encrypt'                  => [
        'key' => env('INTERFACE_ENCRYPT_KEY'),
        'iv'  => env('INTERFACE_ENCRYPT_IV'),
    ],
    'invite_warn_emails'       => explode(',', env('INVITE_WARN_EMAILS', '1020446694@qq.com')),
    //排序需要和user表的role字段一致
    'role'                     => ['user', 'auth_user', 'user_member', 'play_girl'],
    'pay'                      => [
        'pingxx' => [
            'base'   => [
                'app_key' => env('PINGXX_APP_KEY', ''),
                'app_id'  => env('PINGXX_APP_ID', ''),
            ],
            'mianju' => [
                'app_key' => env('PINGXX_MIANJU_KEY', ''),
                'app_id'  => env('PINGXX_MIANJU_ID', ''),
            ]
        ],
        'apple'  => [
            'password' => env('APPLE_IPA_PASSWORD', '')
        ]
    ],
    'config'                   => [
        'height' => [
            100,
            200,
            300
        ],
        'weight' => [
            100,
            200,
            300
        ]
    ],
    'netease'                  => [
        'app_key'    => env('NETEASE_APP_KEY', ''),
        'app_secret' => env('NETEASE_APP_SECRET', ''),
    ],
    'menu'                     => [
        'feed'    => [
            [
                'key'    => 'active_user',
                'name'   => '活跃',
                'action' => 0,  //普通列表
                'sort'   => 140 //排序
            ],
            [
                'key'    => 'new_user',
                'name'   => '新入',
                'action' => 0,  //普通列表
                'sort'   => 130 //排序
            ],
            [
                'key'    => 'lbs_user',
                'name'   => '附近',
                'action' => 100,  //需要定位权限
                'sort'   => 150   //排序
            ]
        ],
        'version' => [
            [
                'man'     => [
                    ['key' => 'lbs_user', 'name' => '附近', 'style' => 'square', 'action' => 100],
                    ['key' => 'charm_girl', 'name' => '女生', 'style' => 'list', 'action' => 0],
                ],
                'women'   => [
                    ['key' => 'lbs_user', 'name' => '附近', 'style' => 'list', 'action' => 100],
                    ['key' => 'active_user', 'name' => '活跃', 'style' => 'list', 'action' => 0],
                    ['key' => 'new_user', 'name' => '新入', 'style' => 'list', 'action' => 0],
                ],
                'version' => version_to_integer('1.5.0')
            ]
        ],
        'moment'  => [
            [
                'topic'   => [
                    ['key' => 'hot', 'name' => '热门', 'show_banner' => 1],
                    ['key' => 'lbs', 'name' => '附近', 'show_banner' => 1],
                    ['key' => 'new', 'name' => '最新', 'show_banner' => 1]
                ],
                'moment'  => [
                    ['key' => 'new', 'name' => '全部', 'show_banner' => 1],
                    ['key' => 'lbs', 'name' => '附近', 'show_banner' => 1],
                ],
                'version' => version_to_integer('1.0.0')
            ]
        ],

        'lbs_menu' => [
            [
                'man'     => [
                    ['key' => 'lbs_all', 'name' => '距离优先', 'style' => 'square'],
                    ['key' => 'lbs_online', 'name' => '在线女生', 'style' => 'square'],
                    ['key' => 'lbs_new', 'name' => '新入女生', 'style' => 'square']
                ],
                'woman'   => [
                    ['key' => 'lbs_all', 'name' => '全部男生', 'style' => 'list'],
                    ['key' => 'lbs_online', 'name' => '在线男生', 'style' => 'list'],
                    ['key' => 'lbs_vip', 'name' => 'VIP男生', 'style' => 'list'],
                    ['key' => 'lbs_girl', 'name' => '女生', 'style' => 'list']
                ],
                'version' => version_to_integer('1.0.0')
            ],
            [
                'man'     => [
                    'a'    => [ //奇数
                        ['key' => 'lbs_online', 'name' => '在线优先', 'style' => 'square'],
                        ['key' => 'lbs_all', 'name' => '距离优先', 'style' => 'square'],
                        ['key' => 'lbs_new', 'name' => '新入优先', 'style' => 'square']
                    ],
                    'b'    => [ //偶数
                        ['key' => 'lbs_online', 'name' => '在线优先', 'style' => 'square'],
                        ['key' => 'lbs_all', 'name' => '距离优先', 'style' => 'square'],
                        ['key' => 'lbs_new', 'name' => '新入优先', 'style' => 'square']
                    ],
                    'city' => [ //携带城市
                        ['key' => 'lbs_online', 'name' => '在线优先', 'style' => 'square'],
                        ['key' => 'lbs_charm_first', 'name' => '魅力优先', 'style' => 'square'],//距离优先按照被关注量排序
                        ['key' => 'lbs_new', 'name' => '新入优先', 'style' => 'square']
                    ],
                ],
                'woman'   => [
                    ['key' => 'lbs_all', 'name' => '全部男生', 'style' => 'list'],
                    ['key' => 'lbs_online', 'name' => '在线优先', 'style' => 'list'],
                    ['key' => 'lbs_vip', 'name' => 'VIP男生', 'style' => 'list'],
                    ['key' => 'lbs_girl', 'name' => '女生', 'style' => 'list']
                ],
                'version' => version_to_integer('2.0.0')
            ]
        ],
    ],
    'upload_path'              => [
        'user'   => [
            'avatar'        => [
                'db_path' => disk_path('user/avatar/')['db_path']
            ],
            'photo'         => [
                'db_path' => disk_path('user/photo/')['db_path']
            ],
            'video'         => [
                'db_path' => disk_path('video/')['db_path']
            ],
            'user_video'    => [
                'db_path' => disk_path('user_video/')['db_path']
            ],
            'voice'         => [
                'db_path' => disk_path('voice/')['db_path']
            ],
            'qrcode'        => [
                'db_path' => disk_path('qrcode/')['db_path']
            ],
            'report'        => [
                'db_path' => disk_path('report/')['db_path']
            ],
            'feedback'      => [
                'db_path' => disk_path('feedback/')['db_path']
            ],
            'qrcode_poster' => [
                'db_path' => disk_path('qrcode_poster/')['db_path']
            ],
            'user_qrcode'   => [
                'db_path' => disk_path('user_qrcode/')['db_path']
            ],
            'face_auth'     => [
                'db_path' => disk_path('face_auth/')['db_path']
            ]
        ],
        'moment' => [
            'images' => [
                'db_path' => disk_path('moment/images/')['db_path']
            ],
        ],
        'banner' => [
            'images' => [
                'db_path' => disk_path('banner/images/')['db_path']
            ],
        ],
        'common' => [
            'watermark'    => [
                'db_path' => disk_path('common/')['db_path']
            ],
            'error_report' => [
                'db_path' => disk_path('error_report/')['db_path']
            ],
            'chat_image'   => [
                'db_path' => disk_path('chat_image/')['db_path']
            ],
        ],
    ],
    'common_image_path'        => [
        'watermark'         => [
            'path' => env('COMMON_IMAGE_PATH_WATERMARK', 'uploads/common/watermark.png'),
        ],
        'poster_background' => [
            'path' => env('COMMON_IMAGE_PATH_POSTER_BACKGROUND', 'uploads/common/background.png'),
        ],
    ],
    'aliyun'                   => [
        'face_auth' => [
            'key'    => env('FACE_AUTH_KEY'),
            'secret' => env('FACE_AUTH_SECRET')
        ]
    ],
    'file'                     => [
        'url' => env('FILE_URL', 'http://file-dev.wqdhz.com'),
    ],
    'cdn_url'                  => env('CDN_URL', 'https://file.wqdhz.com/'),
    'cdn_http_url'             => env('CDN_HTTP_URL', 'https://file.wqdhz.com/'),
    'file_url'                 => env('FILE_URL', 'https://file.wqdhz.com/'),
    'web_url'                  => env('WEB_URL', 'https://api.wqdhz.com/'),
    'little_helper_uuid'       => env('LITTLE_HELPER_UUID', '151968463340961792'),
    'recharge_helper_uuid'     => env('RECHARGE_HELPER_UUID', '151968657671454720'),
    'ios_audit'                => [
        'users_list_uuids' => explode(',', env('IOS_AUDIT_USERS_LIST_UUIDS', ''))
    ],
    'promote'                  => [
        'app_id'   => env('PROMOTE_APP_ID', 3),
        'base_url' => env('PROMOTE_BASE_URL', 'https://ad.ruanruan.club'),
    ],
    'user_photo'               => [
        'check_url' => env('USER_PHOTO_CHECK_URL', 'https://api-dev.wqdhz.com'),
    ],
    'open_register_client_ids' => env('OPEN_REGISTER_CLIENT_IDS',
        'C9BEC1CCBF9C4E221E50660D05692792,E06285C1DAF34AEE77E2F5DF38CD2E65,140fe1da9e17b8aa974,120c83f7609f9ab6951,B6DCF977DA337D6B,BF9C4E221E50660D,AC41632AF7804216,BF9C4E221E50660D,E6172A4D9476BAB2,7AEC90621F8AF14E'),
    // 允许沙盒
    'allow_sandbox_users'      => explode(',', env('ALLOW_SANDBOX_USERS', '18,17')),
    'es'                       => [
        'host' => env('ELASTIC_HOST', '127.0.0.1:9200')
    ],

    'check_resource'           => [
        'url'    => env('CHECK_RESOURCE_URL', 'uploads/common/check_resource.png'),
        'width'  => 248,
        'height' => 440
    ],
    'check_video'              => [
        'url'    => env('CHECK_VIDEO_URL', 'uploads/common/check_video.png'),
        'width'  => 248,
        'height' => 440
    ],
    //活跃优先的最近活跃时间秒
    'sort_active_time'         => env('SORT_ACTIVE_TIME', 5 * 60 * 60),
    'fake_evaluate_user'       => env('FAKE_EVALUATE_USER', 1),
    'user_destroy_time'        => env('USER_DESTROY_TIME', 86400 * 14),
    'wgc'                      => [
        //是否可以重复打款，1：可以(有重复打款的风险)  0 ：不可以
        'can_repay'   => env('WGC_CAN_REPAY', 0),
        'dealer_id'   => env('WGC_DEALER_ID', '28448463'),
        'broker_id'   => env('WGC_BROKER_ID', '27532644'),
        'app_key'     => env('WGC_APP_KEY', 'a2vGMzlBCS9p2AFq54OlSiJgTlSsxZ9h'),
        'des3_key'    => env('WGC_DES3_KEY', 'QbPKvzp6XhMe949720uBpzt7'),
        'private_key' => env('WGC_PRIVATE_KEY', '-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEApPAeDw9YffHZ+TkN8qZdCsl+5xvOvH+kITy8UCirG6joAMwU
uWkd4x5qRu5H90XxmR5ExsItYn0sEcPVKfB49JgY9y/sJBjffyQ9rX9etDEa9ST3
wsVFQwHn97jqgjUVDcB0PdLQpYyBlWkOM+Lmhl2yqMx2trvsqKgtxIjmNsFVllon
zFRFd5PlGzj/oTKKaVVgmkGWe8BoKmWnWkEc5WmA0+qjLqaKIqXE2P1tS+c0TS+2
5E8jEoBhmsBV3u9Pt40zV2Y7StKAA3fLhYZVBzPvZT2qmvykmGS3hAsfvwvXaOnC
0BjSCsxpi9oNrb0AH8afa/qynCGOsQKflsnobwIDAQABAoIBAHw81hd38qsjgpHE
lSoCgDEA59MDUi0QZDwY+KvUhlaWWvNGxhGHCVkrbtgw4gpzJ/GzKBEi8HawXIKh
JS6rESEEdEG1WkUyax7k2ISYXWiTWH/xMaMHXw2DIQyqwBIGE+7A2Y47/qfEd1No
x6bzsbriVxHouT8ZvMCptZfFmssAjfXax43JEczsiOcTDqyh6tNNWlxD8wK7E52+
sEBLC5QSgKm0oZVhTaqASzhy7jNQjTt93dorUPgTl6wbhqBa03l54W2qu82i9Jzg
KQUf6kab3rNkTXLHZ1eiLzOSmiODvVMhZxz5LEWkeJqK1WUEj6Ggc5udUO0F0y3i
nbeH8IECgYEA2c3SB4c2DNSkh5jROjvyjldi7Mps8zhWMJaHCiPiFu5WZpWduYnE
pz5UhrDX3Hp6zw2pAr3d175XUSWWcm5BoEb8ySRovB/CdAnr0qy8rQF5C85ctT4Y
jA6ErXEquu/bgrEuWPp2n0QDexoAgcASkBvv5AAM6teudMoF4dG+jy8CgYEAwdzo
iMXEAUWyWsH6oXmDYgXiQ43gD45e8kj6n3qMU+B8DioW8SHkr7lXo27NpXzVPDZT
cePm41r7ahodTG2zDbY+o7Z0xOPjU+tKwVX9g0scZFext1GcziG0PDjmeR3Wkyv0
LTRksVlIFmLa7nne9OLZ4kdmZ+UF9rUVtUTx6sECgYAfsezrfYinC1a6CMoBwHGG
tz7FGJwMNNmODomuNxSSo7JdEU63jk3YzKA+TYPMKOKwONusc3bSC3fnbiHbmyHf
shTdLHg1UCXa/wqbSNnYD0vPJQOmuFeYIhC6sKo4M+lstl35QtF6Ucpz4o/KUeoh
oH8jXcKDdkmD41ZBy2UArwKBgQCdA6by3/qYbTK/f0zXa+uVgN55iHdpIg6Ufbc/
Y6o0ZSUU89IRCzqFeMAahqvY1PqAAiAjpkpi6lWm4e/I7zCOcoTZY/W/YjPEzFWH
sXXa2QLt4nj12Qv/iBzKiethPWGFYZwq4LyNR5qzRu27KMoD3ZhLRTkT/NKPtqsW
XfjyAQKBgHbDZSdevDNtfyEyYySUtxOGxNX8UrdJuJg7b1uEzM/2WBiYsDZCB033
nJAgGCpYWKZL/SGVQO7PK6Z+tfzG1eZs6ucp40y9nTLy+10KDdsgBHYWVMJmG5Bx
d4V3S7maoeagwJG/sPOhAYqs1tHfE8Bq7DQf8pnSqJ73UCLXv5UG
-----END RSA PRIVATE KEY-----'),
        'public_key'  => env('WGC_PUBLIC_KEY', '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC6a6c8tJRjNIXGXXQsVqZq3ug2
GAKdIp2m6ZVPCsbe1/rlpH8zURkiFOLwe8OA8aK9LrPuEL99MeSa7qB+VNG1h4Iq
VNZW/ctJmMVfdCB85adDnkfRfGXkwfAWJjHdQ/uYMDezPJHTJv5Qwat+Fh/pu+yE
l5ctEnDZ5ZXOlDFm5QIDAQAB
-----END PUBLIC KEY-----'),
    ],
    'destroy_user_avatar'      => [
        'path'   => env('DESTROY_USER_AVATAR', ''),
        'width'  => 422,
        'height' => 422,
    ],
    'wechat_template_msg_chat' => env('WECHAT_TEMPLATE_MSG_CHAT', ''),
    'greet'                    => [
        'is_open'     => env('GREET_IS_OPEN', 1),
        'msg'         => [
            "你好吖",
            "哈喽,有空吗哥哥",
            "你跟我一样无聊吗？",
            "今天的我是你喜欢的样子吗？",
            "你好，陌生人",
            "一起健身吗~",
            "我可以做你的奶茶吗~",
            "今天有时间吗~",
            "在线等你回复。",
            "嗨，哥哥！",
            "嗯...我想和你认识一下~",
            "我的照片好看吗~",
            "相册是我，随时来聊~",
            "深夜的酒不及清晨的酒，白天的她不如夜晚的我",
            "小孩子才谈恋爱，成年人只享受。你呢？",
            "一起做梦吗",
            "天暖和起来了",
            "爱不要说出来，请做出来,有空吗~",
            "我可以握住你吗？",
            "Hi",
            "hello",
            "我这么可爱，要不要理我一下？",
            "现在好无聊，来聊聊天呀。",
            "我们离的不远哦~",
            "出差旅游可以带上我吗？",
            "❤❤❤",
            "初来乍到，请多指教！",
            "喜欢品茶吗"
        ],
        'expire_time' => app()->environment('production') ? 2 * 24 * 60 * 60 : 20 * 60,
    ],
    'gio_account'              => env('GIO_ACCOUNT_ID'),
];
