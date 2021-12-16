<?php


namespace WGCYunPay\Data\Pay;

use WGCYunPay\Data\Pay\BaseData;
use WGCYunPay\Data\Router;

class BankPayData extends BaseData
{
    /**
     * 银⾏开户卡号(必填)
     * @var
     */
    public $card_no;

    /**
     * ⽤户或联系⼈⼿机号(选填)
     * @var
     */
    public $phone_no;

    /**
     * 收款⼈id(选填)
     * @var
     */
    public $anchor_id;

    protected $route = Router::BANK_CARD;
}
