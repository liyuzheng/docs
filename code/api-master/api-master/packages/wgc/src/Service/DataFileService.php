<?php

namespace WGCYunPay\Service;

use WGCYunPay\AbstractInterfaceTrait\BaseService;
use WGCYunPay\AbstractInterfaceTrait\MethodTypeTrait;
use WGCYunPay\Data\Router;

/**
 * 2.2  数据接⼝相关操作
 * 日订单文件、日流水文件、商户充值记录、日订单数据查询、查询⽇订单⽂件 (打款和退款订单)
 * Class DataFileService
 * @package WGCYunPay\Service
 */
class DataFileService extends BaseService
{
    /**
     * 查询⽇订单⽂件
     */
    const  ORDER   = 'order';

    /**
     * 查询⽇流⽔⽂件
     */
    const  BILL    = 'bill';

    /**
     * 查询充值记录
     */
    const  RECHARGE_RECORD = 'recharge-record';

    /**
     * 查询日订单数据
     */
    const  ORDER_RECORD = 'order-record';

    /**
     * 查询⽇订单⽂件 (打款和退款订单)
     */
    const  ORDER_DAY = 'order-day';

    /**
     * 请求类型
     */
    const  METHOD_ARR = [self::ORDER, self::BILL, self::RECHARGE_RECORD, self::ORDER_RECORD,self::ORDER_DAY];

    /**日订单文件、日订单数据、查询⽇订单⽂件 (打款和退款订单)
     * @var
     */
    private $orderDate;

    /**
     *  日流水文件查询时间
     * @var
     */
    private $billDate;

    /**
     * 查询商户充值记录
     * 最⼤查询时间间隔不能超过30天
     * @var
     */
    private $beginAt;

    private $endAt;

    /**
     * 查询日订单数据
     * @var
     */
    private $offset=0;
    private $length=0;
    private $channel='';
    private $data_type='';

    /**
     * @param mixed $offset
     */
    public function setOffset($offset=0)
    {
        $this->offset = (int)$offset;
        return $this;
    }

    /**
     * @param mixed $length
     */
    public function setLength($length=200)
    {
        $this->length = (int)$length;
        return $this;
    }

    /**
     * @param mixed $channel
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
        return $this;
    }

    /**
     * @param mixed $data_type
     */
    public function setDataType($data_type)
    {
        $this->data_type = $data_type;
        return $this;
    }


    public function setOrderDate($orderDate)
    {
        $this->orderDate=$orderDate;
         return $this;
    }

    public function setBillDate($billDate)
    {
        $this->billDate = $billDate;
         return $this;
    }

    public function setAt($beginAt='', $endAt='')
    {
        $this->beginAt = $beginAt;
        $this->endAt = $endAt;
        return $this;
    }

    use MethodTypeTrait;

    protected function getDes3Data()
    {
        // TODO: Implement getDes3Data() method.
        $data = [];
        switch ($this->methodType) {
            case self::ORDER:
                $data = ['order_date' => $this->orderDate];
                break;
            case self::ORDER_DAY:
                $data = ['order_date' => $this->orderDate];
                break;
            case self::BILL:
                $data = ['bill_date' => $this->billDate];
                break;
            case self::RECHARGE_RECORD:
                $data = ['begin_at' => $this->beginAt, 'end_at' => $this->endAt];
                break;
            case self::ORDER_RECORD:
                $data = ['order_date' => $this->orderDate, 'offset' => $this->offset,'length' => $this->length,'channel'=>$this->channel,'data_type'=>$this->data_type];
                break;
        }
        return $data;
    }

    protected function getRequestInfo()
    {
        // TODO: Implement getRequestInfo() method.
        $route = Router::ORDER_DOWNLOAD;

        switch ($this->methodType) {
            case self::ORDER:
                $route = Router::ORDER_DOWNLOAD;
                break;
            case self::BILL:
                $route = Router::BILL_DOWNLOAD;
                break;
            case self::RECHARGE_RECORD:
                $route = Router::RECHARGE_RECORD;
                break;
            case self::ORDER_RECORD:
                $route = Router::ORDER_RECORD;
                break;
            case self::ORDER_DAY:
                $route = Router::ORDER_DAY;
                break;
        }
        return [$route];
    }
    protected function callback($res){
        if(isset($res['data']) && is_string($res['data'])){
            $res['data'] = Des3Service::decode($res['data'], $this->config->des3_key);
        }
        return $res;
    }
}
