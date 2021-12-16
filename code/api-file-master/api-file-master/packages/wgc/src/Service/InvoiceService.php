<?php

namespace WGCYunPay\Service;

use WGCYunPay\AbstractInterfaceTrait\BaseService;
use WGCYunPay\AbstractInterfaceTrait\DataTrait;
use WGCYunPay\AbstractInterfaceTrait\MethodTypeTrait;
use WGCYunPay\Data\Router;

/**
 * 2.4 发票接口操作
 * Class OrderService
 * @package WGCYunPay\Service
 */
class InvoiceService extends BaseService
{
    /**
     * 查询商户已开具和待开具发票金额
     */
    const  INVOICE_STAT   = 'invoice-stat';

    /**
     * 查询可开票额度
     */
    const  INVOICE_AMOUNT   = 'invoice-amount';

    /**
     * 开票申请
     */
    const  INVOICE_APPLY = 'apply';

    /**
     * 查询开票申请状态
     */
    const  INVOICE_APPLY_STATUS = 'invoice-status';

    /**
     * 下载发票PDF
     */
    const  INVOICE_PDF = 'invoice-pdf';

    /**
     * 请求类型
     */
    const  METHOD_ARR = [self::INVOICE_STAT, self::INVOICE_AMOUNT, self::INVOICE_APPLY,self::INVOICE_APPLY_STATUS,self::INVOICE_PDF];

    /**
     * 发票申请编号
     * @var string
     */
    protected $invoice_apply_id  ;

    /**
     * 申请开票金额
     * @var string
     */
    protected $amount;

    /**
     * 发票类型
     * @var string
     */
    protected $invoice_type ;

    /**
     * 开户行及账号
     * @var string
     */
    protected $bank_name_account   ;
    /**
     * 货物或应税劳务、服务名称
     * @var string
     */
    protected $goods_services_name ;
    /**
     * 发票备注
     * @var string
     */
    protected $remark  ;

    /**
     * 发票申请编号
     * @var string
     */
    protected $application_id  ;

    /**
     * 查询年份
     * @var
     */
    protected $year= 2019 ;
    use MethodTypeTrait;
    /**
     * @param string  $year
     */
    public function setYear( int $year)
    {
        $this->year = $year;
        return $this;
    }

    /**
     * @param string $invoice_apply_id
     */
    public function setInvoiceApplyId(string $invoice_apply_id)
    {
        $this->invoice_apply_id = $invoice_apply_id;
        return $this;
    }

    /**
     * @param string $amount
     */
    public function setAmount(string $amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @param string $invoice_type
     */
    public function setInvoiceType(string $invoice_type)
    {
        $this->invoice_type = $invoice_type;
        return $this;
    }

    /**
     * @param string $bank_name_account
     */
    public function setBankNameAccount(string $bank_name_account)
    {
        $this->bank_name_account = $bank_name_account;
        return $this;
    }

    /**
     * @param string $goods_services_name
     */
    public function setGoodsServicesName(string $goods_services_name)
    {
        $this->goods_services_name = $goods_services_name;
        return $this;
    }

    /**
     * @param string $remark
     */
    public function setRemark(string $remark)
    {
        $this->remark = $remark;
        return $this;
    }

    /**
     * @param string $application_id
     */
    public function setApplicationId(string $application_id)
    {
        $this->application_id = $application_id;
        return $this;
    }


    /**
     * 根据类型返回数据
     * Date : 2019/7/31 15:58
     * @return array|mixed
     * @throws \Exception
     */
    protected function getDes3Data(): array
    {
        // TODO: Implement getDes3Data() method.
        switch ($this->methodType ?? self::INVOICE_STAT) {
            case self::INVOICE_STAT:
                $data      = ['year' => $this->year, 'dealer_id'  => $this->config->dealer_id, 'broker_id'  => $this->config->broker_id];
                break;
            case self::INVOICE_AMOUNT:
                $data      = ['dealer_id'  => $this->config->dealer_id, 'broker_id'  => $this->config->broker_id];
                break;
            case self::INVOICE_APPLY:
                $data      = ['dealer_id'  => $this->config->dealer_id, 'broker_id'  => $this->config->broker_id, 'invoice_apply_id' => $this->invoice_apply_id, 'amount' => $this->amount,
                    'invoice_type' => $this->invoice_type,'bank_name_account' => $this->bank_name_account,'goods_services_name' => $this->goods_services_name, 'remark' => $this->remark];
                break;
            case self::INVOICE_APPLY_STATUS:
                $data      = ['invoice_apply_id' => $this->invoice_apply_id,'application_id' => $this->application_id];
                break;
            case self::INVOICE_PDF:
                $data      = ['invoice_apply_id' => $this->invoice_apply_id,'application_id' => $this->application_id];
                break;
            default:
                throw new \Exception('not des3Data');
        }
        return $data;
    }

    protected function getRequestInfo()
    {
        $methodType = $this->methodType ?? self::INVOICE_STAT;

        $method = 'get';
        if (in_array($methodType, [self::INVOICE_AMOUNT,self::INVOICE_APPLY,self::INVOICE_APPLY_STATUS,self::INVOICE_PDF])) {
            $method = 'post';
        }

        $route = Router::INVOICE_STAT;
        switch ($methodType) {
            case self::INVOICE_STAT:
                $route = Router::INVOICE_STAT;
                break;
            case self::INVOICE_AMOUNT:
                $route = Router::INVOICE_AMOUNT;
                break;
            case self::INVOICE_APPLY:
                $route = Router::INVOICE_APPLY;
                break;
            case self::INVOICE_APPLY_STATUS:
                $route = Router::INVOICE_APPLY_STATUS;
                break;
            case self::INVOICE_PDF:
                $route = Router::INVOICE_PDF;
                break;
        }

        return [$route, $method];
    }

    protected function callback($res){
        if(isset($res['data']) && is_string($res['data'])){
            $res['data'] = Des3Service::decode($res['data'], $this->config->des3_key);
        }
        return $res;
    }




}
