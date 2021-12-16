<?php

namespace WGCYunPay\Service;

use WGCYunPay\AbstractInterfaceTrait\BaseService;
use WGCYunPay\AbstractInterfaceTrait\DataTrait;
/**
 * 下单相关操作（银行卡、支付宝、微信）
 * Class PayService
 * @package WGCYunPay\Service
 */
class PayService  extends BaseService
{
    protected $dealer_broker = true;

    use DataTrait;
}
