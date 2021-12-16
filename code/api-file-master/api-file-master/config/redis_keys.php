<?php

return [
    'auth'                => [
        'user_login_at' => [
            'key'  => 'auth:user_login_at',
            'type' => 'zsort'
        ]
    ],
    'cache'               => [
        'pay_channel_cache'          => 'cache:pay_channel',
        'invite_stat_cache'          => 'cache:invite_stat_%s_cache',
        'invite_warn_cache'          => 'cache:invite_warn',
        'goods_cache'                => 'cache:goods_%s_list',
        'woman_goods_cache'          => 'cache:woman_goods_%s_list',
        'proxy_currency_goods_cache' => 'cache:proxy_currency_goods',
        'auto_block_break_users'     => 'cache:auto_block_break_users',
        'active_at'                  => 'cache:active_at:%d',
        'has_remained'               => 'cache:has_remained:%d',
        'invite_popup_cache'         => 'cache:invite_popup',
        'cold_start_user_cache'      => 'cache:cold_start_user_%d',
        'feed_lbs_exists_user'       => [
            'key' => 'cache:feed_lbs_exists_user:%d',
            'ttl' => 86400
        ],
    ],
    'has_remained_block'  => 'has_remained:block:%d',
    'blacklist'           => [
        'client' => [
            'key'  => 'black:client',
            'type' => 'zset'
        ],
        'user'   => [
            'key'  => 'black:user',
            'type' => 'zset'
        ]
    ],
    'is_look'             => [
        'key'  => 'look:%d',
        'type' => 'zset'
    ],
    'is_chat'             => [
        'key'  => 'chat:%d',
        'type' => 'zset'
    ],
    'is_follow'           => [
        'key'  => 'follow:%d',
        'type' => 'zset'
    ],
    'lock_mark_user'      => 'mark:%d',
    'follow_lock'         => 'follow:lock:%d',
    'mobile'              => [
        'error_times' => [
            'key'  => 'mobile_error',
            'type' => 'hash'
        ],
        'block'       => [
            'key'  => 'mobile_block:%s',
            'type' => 'set'
        ]
    ],
    'number'              => [
        'cache_number' => [
            'key'  => 'xiaoquan:cache_number',
            'type' => 'set'
        ]
    ],
    'office_access_token' => [
        'key'  => 'cache:office_access_token',
        'type' => 'string',
        'ttl'  => 7200,
    ],
    'greets'              => [
        'key'  => 'greets',//谁给谁在什么时间发了消息 eg:   A_B_时间戳:时间戳
        'type' => 'zset'
    ],
    'ab_test'             => [
        'key'  => 'ab_test:%s_%s_%s',
        'type' => 'set'
    ],

    'sms_block'             => [
        'key'  => 'sms_block:%s',
        'type' => 'set'
    ],
    'user_switch_cache'     => [
        'key'  => 'user_switch_cache',
        'type' => 'hash'
    ],
    'user_blacklist_manual' => [
        'key'  => 'user_blacklist_manual:%s',
        'type' => 'zset'
    ],
    'user_blacklist_phone'  => [
        'key'  => 'user_blacklist_phone:%s',
        'type' => 'zset'
    ],
    'pingxx_order_scene'    => 'pingxx_order_scene:%s',
    'charm_popup'           => 'charm_popup:%d',
    'hide_users'            => [
        'key'  => 'hide_users',
        'type' => 'set'
    ],
];
