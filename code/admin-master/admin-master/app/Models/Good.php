<?php


namespace App\Models;


class Good extends BaseModel
{
    protected $table = 'goods';
    protected $hidden = ['id', 'os', 'platform', 'related_id', 'sort', 'type', 'uuid'];
    protected $fillable = [
        'app_name',
        'product_id',
        'os',
        'platform',
        'type',
        'related_type',
        'related_id',
        'ori_price',
        'price',
        'sort',
        'is_default',
        'uuid'
    ];

    const RELATED_TYPE_CURRENCY     = 100; // 货币类型
    const RELATED_TYPE_CARD         = 200; // 会员卡类型
    const RELATED_TYPE_SERVICE      = 300; // 服务类型
    const RELATED_TYPE_STR_CURRENCY = 'currency';
    const RELATED_TYPE_STR_CARD     = 'card';

    const TYPE_CURRENCY       = 90;                  // 代币支付
    const TYPE_ALIPAY         = 100;                 // 支付宝支付
    const TYPE_WECHAT         = 200;                 // 微信支付
    const TYPE_ALIPAY_WAP     = 300;                 // 支付宝手机网页支付
    const TYPE_WX_WAP         = 400;                 // 微信h5支付
    const TYPE_STR_ALIPAY     = 'alipay';            // 生成ping++订单需要使用字符串类型的类型 支付宝支付
    const TYPE_STR_WECHAT     = 'wx';                // 微信支付
    const TYPE_STR_ALIPAY_WAP = 'alipay_wap';        // 支付宝手机网页支付
    const TYPE_STR_WX_WAP     = 'wx_wap';            // 微信h5支付
    const TYPE_STR_CURRENCY   = 'currency';          // 代币支付

    const PLATFORM_PINGXX         = 100;
    const PLATFORM_APPLE          = 200;
    const PLATFORM_APPLE_ICON     = 300; // 苹果金币类目支付
    const PLATFORM_STR_PINGXX     = 'pingxx';
    const PLATFORM_STR_APPLE      = 'apple';
    const PLATFORM_STR_APPLE_ICON = 'apple-icon';

    const PLATFORM_MAPPING = [
        self::PLATFORM_STR_PINGXX     => self::PLATFORM_PINGXX,
        self::PLATFORM_STR_APPLE      => self::PLATFORM_APPLE,
        self::PLATFORM_STR_APPLE_ICON => self::PLATFORM_APPLE_ICON,
        self::PLATFORM_PINGXX         => self::PLATFORM_STR_PINGXX,
        self::PLATFORM_APPLE          => self::PLATFORM_STR_APPLE,
        self::PLATFORM_APPLE_ICON     => self::PLATFORM_STR_APPLE_ICON,
    ];

    const TRADE_PAY_RELATED_TYPES = [
        self::RELATED_TYPE_CURRENCY => TradePay::RELATED_TYPE_RECHARGE,
        self::RELATED_TYPE_CARD     => TradePay::RELATED_TYPE_RECHARGE_VIP,
    ];

    const CLIENT_OS_IOS            = 100;              // 客户端类型 ios
    const CLIENT_OS_ANDROID        = 200;              // 客户端类型 android
    const CLIENT_OS_WEB            = 300;              // 客户端类型 WEB 网页
    const CLIENT_OS_NATIVE_WEB     = 400;              // 客户端类型 客户端内嵌 web
    const CLIENT_OS_STR_IOS        = 'ios';            //  客户端字符串类型 ios
    const CLIENT_OS_STR_ANDROID    = 'android';        // 客户端字符串类型 android
    const CLIENT_OS_STR_WEB        = 'web';            // 客户端字符串类型 WEB 客户端
    const CLIENT_OS_STR_NATIVE_WEB = 'native_web';     // 客户端字符串类型 客户端内嵌web

    // 客户端类型字符串映射
    const CLIENT_OS_MAPPING = [
        self::CLIENT_OS_IOS            => self::CLIENT_OS_STR_IOS,
        self::CLIENT_OS_ANDROID        => self::CLIENT_OS_STR_ANDROID,
        self::CLIENT_OS_WEB            => self::CLIENT_OS_STR_WEB,
        self::CLIENT_OS_NATIVE_WEB     => self::CLIENT_OS_STR_NATIVE_WEB,
        // 以下是 string 映射 int
        self::CLIENT_OS_STR_IOS        => self::CLIENT_OS_IOS,
        self::CLIENT_OS_STR_ANDROID    => self::CLIENT_OS_ANDROID,
        self::CLIENT_OS_STR_WEB        => self::CLIENT_OS_WEB,
        self::CLIENT_OS_STR_NATIVE_WEB => self::CLIENT_OS_NATIVE_WEB,
    ];

    // 商品类型映射
    const GOODS_TYPE_MAPPING = [
        self::RELATED_TYPE_CURRENCY     => self::RELATED_TYPE_STR_CURRENCY,
        self::RELATED_TYPE_CARD         => self::RELATED_TYPE_STR_CARD,
        // 以下是 string 映射 int
        self::RELATED_TYPE_STR_CURRENCY => self::RELATED_TYPE_CURRENCY,
        self::RELATED_TYPE_STR_CARD     => self::RELATED_TYPE_CARD,
    ];

    const GOODS_PAY_METHOD_MAPPING = [
        self::TYPE_CURRENCY   => self::TYPE_STR_CURRENCY,
        self::TYPE_ALIPAY     => self::TYPE_STR_ALIPAY,
        self::TYPE_WECHAT     => self::TYPE_STR_WECHAT,
        self::TYPE_ALIPAY_WAP => self::TYPE_STR_ALIPAY_WAP,
        self::TYPE_WX_WAP     => self::TYPE_STR_WX_WAP,
    ];

    const OS_DEFAULT_PLATFORM_MAPPING = [
        self::CLIENT_OS_IOS        => self::PLATFORM_APPLE,
        self::CLIENT_OS_ANDROID    => self::PLATFORM_PINGXX,
        self::CLIENT_OS_WEB        => self::PLATFORM_PINGXX,
        self::CLIENT_OS_NATIVE_WEB => self::PLATFORM_PINGXX,
    ];

    // 打折最后时限秒数
    const DISCOUNT_MINIMUM_SECONDS = 259200;

    /**
     * 获取某个具体商品下到条目 (会员卡｜或代币)
     *
     * @return \App\Models\Card|\App\Models\Currency|null
     */
    public function getInfoItem()
    {
        switch ($this->getRawOriginal('related_type')) {
            case self::RELATED_TYPE_CARD:
                return rep()->card->getQuery()->find($this->related_id);
            case self::RELATED_TYPE_CURRENCY:
                return rep()->currency->getQuery()->find($this->related_id);
            default:
                return null;
        }
    }

    /**
     * 获取映射后的支付方式
     *
     * @param  int  $type
     *
     * @return string
     */
    public function getTypeAttribute($type)
    {
        return isset(self::GOODS_PAY_METHOD_MAPPING[$type])
            ? self::GOODS_PAY_METHOD_MAPPING[$type]
            : 'default';
    }

    /**
     * 获取映射后的商品类型
     *
     * @param  int  $relatedType
     *
     * @return string
     */
    public function getRelatedTypeAttribute($relatedType)
    {
        return self::GOODS_TYPE_MAPPING[$relatedType];
    }

    public function getPriceAttribute($price)
    {
        return $this->getRawOriginal('type') == self::TYPE_CURRENCY ? $price / 10 : $price / 100;
    }

    public function getOriPriceAttribute($price)
    {
        return $this->getRawOriginal('type') == self::TYPE_CURRENCY ? $price / 10 : $price / 100;
    }
}
