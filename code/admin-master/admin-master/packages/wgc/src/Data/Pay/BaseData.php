<?php

namespace WGCYunPay\Data\Pay;

use WGCYunPay\Data\Base;

class BaseData extends Base
{
    /**
     * 商户订单号，由商户保持唯⼀性(必填)，64个 英⽂字符以内
     * @var
     */
    public $order_id;

    /**
     * 姓名(必填)
     * @var
     */
    public $real_name;

    /**
     * 身份证(必填)
     * @var
     */
    public $id_card;

    /**
     * 打款⾦额(单位为元, 必填)
     * @var
     */
    public $pay;

    /**
     * 备注信息(选填)
     * @var
     */
    public $notes;

    /**
     * 打款备注(选填，最⼤20个字符，⼀个汉字占2个 字符，不允许特殊字符：' " & | @ % * ( ) - : # ￥)
     * @var
     */
    public $pay_remark;

    /**
     * 回调地址(选填，最⼤⻓度为200)
     * @var string
     */
    public $notify_url = '';

    protected $method = 'post';
}
