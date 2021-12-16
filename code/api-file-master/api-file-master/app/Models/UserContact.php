<?php


namespace App\Models;


class UserContact extends BaseModel
{
    protected $table    = 'user_contact';
    protected $fillable = [
        'uuid',
        'user_id',
        'platform',
        'name',
        'account',
        'id_card',
        'wechat',
        'mobile',
        'region',
        'region_path'
    ];

    const PLATFORM_BANK_CARD     = 100; // 提现方式银行卡
    const PLATFORM_ALIPAY        = 200; // 提现方式支付宝
    const PLATFORM_WECHAT        = 300; // 提现方式微信
    const PLATFORM_STR_BANK_CARD = 'bank_card';
    const PLATFORM_STR_ALIPAY    = 'alipay';
    const PLATFORM_STR_WECHAT    = 'wechat';

    // 提现方式映射
    const PLATFORM_MAPPING = [
        self::PLATFORM_STR_BANK_CARD => self::PLATFORM_BANK_CARD,
        self::PLATFORM_STR_ALIPAY    => self::PLATFORM_ALIPAY,
        self::PLATFORM_STR_WECHAT    => self::PLATFORM_WECHAT,
    ];

    const PLATFORM_CHINESE_MAPPING = [
        self::PLATFORM_BANK_CARD => '银行卡',
        self::PLATFORM_ALIPAY    => '支付宝',
        self::PLATFORM_WECHAT    => '微信'
    ];
}
