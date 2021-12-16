<?php


namespace WGCYunPay\Service;


use WGCYunPay\AbstractInterfaceTrait\BaseService;
use WGCYunPay\AbstractInterfaceTrait\MethodTypeTrait;
use WGCYunPay\Data\Router;

/**
 * 个体工商注册
 * Class OrderService
 * @package WGCYunPay\Service
 */
class AicService extends BaseService
{
    /**
     *工商实名信息录入
     */
    const  AIC_REALNAME   = 'realname';

    /**
     * 预启动
     */
    const  AIC_H5URL    = 'h5_url';

    /**
     * 获取注册状态
     */
    const  AIC_STATUS = 'aic_status';



    /**
     * 请求类型
     */
    const  METHOD_ARR = [self::AIC_REALNAME, self::AIC_H5URL, self::AIC_STATUS];

    /**
     * 商户端的⽤户id，在商户系统唯⼀且不变
     * @var string
     */
    protected $dealer_user_id ;

    /**
     * 信息提供方
     * @var string
     */
    protected $info_provider;
    /**
     * 姓名
     * @var string
     */
    protected $real_name;

    /**
     * 证件号码
     * @var string
     */
    protected $id_card ;

    /**
     * 身份证有效期开始时间
     * @var string
     */

    protected  $id_card_validity_start ;
    /**
     * 身份证有效期结束时间
     * @var string
     */

    protected  $id_card_validity_end ;

    /**
     * 手机号
     * @var string
     */

    protected   $phone_no ;


    /**
     * 活体照片
     * @var string
     */

    protected  $live_image;
    /**
     * 客户端类型
     * @var int
     */

    protected  $client_type = 1;
    /**
     * 异步通知地址
     * @var string
     */

    protected  $notify_url;

    /**
     * h5主题颜色
     * @var string
     */

    protected  $color;
    /**
     * 页面回跳地址
     * @var string
     */

    protected  $return_url;
    use MethodTypeTrait;
    /**
     * @param string $dealer_user_id
     */
    public function setUid(string $dealer_user_id)
    {
        $this->dealer_user_id = $dealer_user_id;
        return $this;
    }
    /**
     * @param string setInfoProvider
     */
    public function setInfoProvider(string $info_provider)
    {
        $this->info_provider = $info_provider;
        return $this;
    }
    /**
     * @param string $real_name
     */
    public function setRealName(string $real_name)
    {
        $this->real_name = $real_name;
        return $this;
    }

    /**
     * @param string $id_card
     */
    public function setIdCard(string $id_card)
    {
        $this->id_card = $id_card;
        return $this;
    }

    /**
     * @param string $id_card_validity_start
     */
    public function setValidity_start(string $id_card_validity_start)
    {
        $this->id_card_validity_start = $id_card_validity_start;
        return $this;
    }
    /**
     * @param string $id_card_validity_end
     */
    public function setValidity_end(string $id_card_validity_end)
    {
        $this->id_card_validity_end = $id_card_validity_end;
        return $this;
    }

    /**
     * @param string $phone_no
     */
    public function setPhone(string $phone_no)
    {
        $this->phone_no = $phone_no;
        return $this;
    }
    /**
     * @param string $live_image
     */
    public function setLive(string $live_image)
    {
        $this->live_image = $live_image;
        return $this;
    }

    /**
     * @param int $client_type
     */
    public function setType(int $client_type)
    {
        $this->client_type = $client_type;
        return $this;
    }

    /**
     * @param int $notify_url
     */
    public function setUrl(string $notify_url)
    {
        $this->notify_url = $notify_url;
        return $this;
    }


    /**
     * @param string $color
     */
    public function setColor(string $color)
    {
        $this->color = $color;
        return $this;
    }

    /**
     * @param string $return_url
     */
    public function setReturnUrl(string $return_url)
    {
        $this->return_url = $return_url;
        return $this;
    }



    use MethodTypeTrait;



    /**
     * 根据类型返回数据
     * Date : 2019/7/31 15:58
     * @return array|mixed
     * @throws \Exception
     */
    protected function getDes3Data(): array
    {
        // TODO: Implement getDes3Data() method.
        switch ($this->methodType ?? self::AIC_REALNAME) {
            case self::AIC_REALNAME:
                $data   = ['dealer_user_id' => $this->dealer_user_id, 'broker_id' => $this->config->broker_id, 'dealer_id' => $this->config->dealer_id, 'real_name' => $this->real_name,'info_provider'=>$this->info_provider,
                    'id_card' => $this->id_card,'id_card_validity_start'=>$this->id_card_validity_start,'id_card_validity_end'=>$this->id_card_validity_end,'phone_no' => $this->phone_no,'live_image' => $this->live_image];
                break;
            case self::AIC_H5URL:
                $data     = ['dealer_user_id' => $this->dealer_user_id, 'broker_id' => $this->config->broker_id, 'dealer_id' => $this->config->dealer_id, 'client_type' => $this->client_type,'notify_url' => $this->notify_url,
                    'info_provider' => $this->info_provider,'color'=>$this->color,'return_url'=>$this->return_url];
                break;
            case self::AIC_STATUS:
                $data     = ['dealer_user_id' => $this->dealer_user_id, 'broker_id' => $this->config->broker_id, 'dealer_id' => $this->config->dealer_id];
                break;
            default:
                throw new \Exception('not des3Data');
        }
        return $data;
    }

    protected function getRequestInfo()
    {

        $methodType = $this->methodType ?? self::AIC_REALNAME;
        $method = 'post';

        if (in_array($methodType, [self::AIC_H5URL,self::AIC_STATUS])) {
            $method = 'get';
        }

        $route = Router::AIC_REALNAME;
        switch ($methodType) {
            case self::AIC_REALNAME:
                $route = Router::AIC_REALNAME;
                break;
            case self::AIC_H5URL:
                $route = Router::AIC_H5URL;
                break;
            case self::AIC_STATUS:
                $route = Router::AIC_STATUS;
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
