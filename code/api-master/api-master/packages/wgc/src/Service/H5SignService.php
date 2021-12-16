<?php


namespace WGCYunPay\Service;


use WGCYunPay\AbstractInterfaceTrait\BaseService;
use WGCYunPay\AbstractInterfaceTrait\MethodTypeTrait;
use WGCYunPay\Data\Router;

/**
 * H5签约  （预申请、H5签约、签约状态、测试解约）
 * Class OrderService
 * @package WGCYunPay\Service
 */
class H5SignService extends BaseService
{
    /**
     * H5预申请签约
     */
    const  PRESIGN   = 'presign';

    /**
     * H5签约
     */
    const  SIGN_H5    = 'sign_h5';

    /**
     * 获取签约状态
     */
    const  SIGN_STATUS = 'sign_status';

    /**
     * 测试阶段解约接口
     */
    const  SIGN_RELEASE = 'sign_release';

    /**
     * 请求类型
     */
    const  METHOD_ARR = [self::PRESIGN, self::SIGN_H5, self::SIGN_STATUS,self::SIGN_RELEASE];

    /**
     * 商户app端⽤户id，不能重复
     * @var string
     */
    protected $uid  ;


    /**
     * 姓名
     * @var string
     */
    protected $real_name;

    /**
     * 证件号码
     * @var string
     */
    protected $id_card  ;

    /**
     * 证件类型
     * @var int
     */

    private $certificate_type  = 0;
    /**
     * 签约token
     * @var string
     */

    protected  $token;


    /**
     * h5主题颜色
     * @var string
     */

    protected  $color;
    /**
     * 页面回跳地址
     * @var string
     */

    protected  $redirect_url;
    /**
     * 异步通知地址
     * @var string
     */

    protected  $url;

    use MethodTypeTrait;
    /**
     * @param string $uid
     */
    public function setUid(string $uid)
    {
        $this->uid = $uid;
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
     * @param int $certificate_type
     */


    public function setCertificateType(int $certificate_type)
    {
        $this->certificate_type =  $certificate_type;
        return $this;
    }
    /**
     * @param string $token
     */
    public function setToken(string $token)
    {
        $this->token = $token;
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
     * @param string $redirect_url
     */
    public function setRedirectUrl(string $redirect_url)
    {
        $this->redirect_url = $redirect_url;
        return $this;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url)
    {
        $this->url = $url;
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
        switch ($this->methodType ?? self::PRESIGN) {
            case self::PRESIGN:
                $data  = ['uid' => $this->uid, 'broker_id' => $this->config->broker_id, 'dealer_id' => $this->config->dealer_id, 'real_name' => $this->real_name,
                    'id_card' => $this->id_card,'certificate_type'=>$this->certificate_type];
                break;
            case self::SIGN_H5:
                $data  = ['token' => $this->token, 'color' => $this->color,'url'=>$this->url,'redirect_url'=>$this->redirect_url];
                break;
            case self::SIGN_STATUS:
                $data  = ['broker_id' => $this->config->broker_id, 'dealer_id' => $this->config->dealer_id, 'real_name' => $this->real_name, 'id_card' => $this->id_card];
                break;
            case self::SIGN_RELEASE:
                $data  = ['uid' => $this->uid, 'broker_id' => $this->config->broker_id, 'dealer_id' => $this->config->dealer_id, 'real_name' => $this->real_name,
                    'id_card' => $this->id_card,'certificate_type'=>$this->certificate_type];
                break;
            default:
                throw new \Exception('not des3Data');
        }
        return $data;
    }

    protected function getRequestInfo()
    {

        $methodType = $this->methodType ?? self::SIGN_H5;
        $method = 'get';

        if (in_array($methodType, [self::SIGN_RELEASE,self::PRESIGN])) {
            $method = 'post';
        }

        $route = Router::SIGN_PRESIGN_H5;
        switch ($methodType) {
            case self::PRESIGN:
                $route = Router::SIGN_PRESIGN_H5;
                break;
            case self::SIGN_H5:
                $route = Router::SIGN_USER_H5;
                break;
            case self::SIGN_STATUS:
                $route = Router::SIGN_USER_STATUS;
                break;
            case self::SIGN_RELEASE:
                $route = Router::SIGN_RELEASE_H5;
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
