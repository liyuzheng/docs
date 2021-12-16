<?php


namespace WGCYunPay\Service;


use WGCYunPay\AbstractInterfaceTrait\BaseService;
use WGCYunPay\AbstractInterfaceTrait\MethodTypeTrait;
use WGCYunPay\Data\Router;

/**
 * 个税接口
 * Class OrderService
 * @package WGCYunPay\Service
 */
class TaxService extends BaseService
{
    /**
     *个税扣缴明细下载
     */
    const  DOWNLOAD   = 'download';




    /**
     * 商户签约主体
     * @var string
     */
    protected $ent_id ;

    /**
     * 所属期
     * @var string
     */
    protected $year_month;

    /**
     * @param string $ent_id
     */
    public function setEntId(string $ent_id)
    {
        $this->ent_id = $ent_id;
        return $this;
    }

    /**
     * @param string $year_month
     */
    public function setYearMonth(string $year_month)
    {
        $this->year_month = $year_month;
        return $this;
    }


    protected function getDes3Data(): array
    {
        $data      = ['dealer_id' => $this->config->dealer_id, 'year_month' => $this->year_month, 'ent_id' => $this->ent_id ];
        return $data;
    }

    protected function getRequestInfo()
    {
        $method = 'post';
        $route = Router::TAX_DOWNLOAD;
        return [$route, $method];
    }


}
