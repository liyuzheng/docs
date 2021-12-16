<?php


namespace WGCYunPay\Service;

use WGCYunPay\AbstractInterfaceTrait\BaseService;
use WGCYunPay\AbstractInterfaceTrait\MethodTypeTrait;
use WGCYunPay\Data\Router;

/**
 * 2.3 用户信息验证相关操作
 * 银行卡四要素（鉴权、确认、验证）、银行卡三要素验证、身份证实名认证、银行卡信息查询、上传用户免验证信息、用户免验证身份校验
 * Class AuthenticationService
 * @package WGCYunPay\Service
 */
class AuthenticationService extends BaseService
{
    /**
     * 银行卡四要素鉴权请求
     */
    const  VERIFY_REQUEST = 'verfy_request';

    /**
     * 银行卡四要素鉴权确认
     */
    const  VERIFY_CONFIRM = 'verify_confirm';

    /**
     * 银行卡四要素验证
     */
    const  VERIFY_FOUR = 'verify_four';

    /**
     * 银行卡三要素验证
     */
    const  VERIFY_THREE = 'verify_three';

    /**
     * 身份证实名验证
     */
    const  VERIFY_ID = 'verify_id';

    /**
     * 银行卡信息查询
     */
    const  BANK_INFO = 'bank_info';

    /**
     * 上传用户免验证名单信息
     */
    const  USER_EXEMPTED_INFO = 'user_exempted_info';

    /**
     * 校验用户是否在免验证名单内
     */
    const  USER_WHITE_CHECK = 'user_white_check';


    /**
     * 请求类型
     */
    const  METHOD_ARR = [self::VERIFY_REQUEST, self::VERIFY_CONFIRM, self::VERIFY_FOUR, self::VERIFY_THREE,self::VERIFY_ID, self::BANK_INFO, self::USER_EXEMPTED_INFO,self::USER_WHITE_CHECK];

   // protected $dealer_broker = false;
    /**
     * 姓名
     * @var string
     */
    protected $real_name= '';


    /**
     * 身份证号
     * @var string
     */
    protected $id_card = '';

    /**
     * 银行卡号
     * @var string
     */
    protected $card_no = '';

    /**
     * 银行名称
     * @var string
     */
    protected $bank_name = '';

    /**
     * 手机号
     * @var  string
     */
    protected $mobile;

    /**
     * 交易凭证、平台流水号
     * @var string
     */
    protected $ref = '';

    /**
     * 验证码
     * @var string
     */
    protected $captcha = '';

    /**
     * 证件类型
     * @var string
     */
    protected $card_type = '';

    /**
     * 申请备注
     * @var string
     */
    protected $comment_apply = '';

    /**
     * 国家代码
     * @var string
     */
    protected $country = '';

    /**
     * 出生日期
     * @var string
     */
    protected $birthday = '';

    /**
     * 性别
     * @var string
     */
    protected $gender = '';

    /**
     * 回调地址
     * @var string
     */
    protected $notify_url = '';

    /**
     * 人员信息
     * @var
     */
    protected $user_images = [];



    use MethodTypeTrait;


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
     * @param string $card_no
     */
    public function setCardNo(string $card_no)
    {
        $this->card_no = $card_no;
        return $this;
    }

    /**
     * @param string $bank_name
     */
    public function setBankName(string $bank_name)
    {
        $this->bank_name = $bank_name;
        return $this;
    }

    /**
     * @param string $mobile
     */
    public function setMobile(string $mobile)
    {
        $this->mobile = $mobile;
        return $this;
    }

    /**
     * @param string $captcha
     */
    public function setCaptcha(string $captcha)
    {
        $this->captcha = $captcha;
        return $this;
    }

    /**
     * @param string $card_type
     */
    public function setCardType(string $card_type)
    {
        $this->card_type = $card_type;
        return $this;
    }

    /**
     * @param string $comment_apply
     */
    public function setCommentApply(string $comment_apply)
    {
        $this->comment_apply = $comment_apply;
        return $this;
    }

    /**
     * @param string $country
     */
    public function setCountry(string $country)
    {
        $this->country = $country;
        return $this;
    }

    /**
     * @param string $birthday
     */
    public function setBirthday(string $birthday)
    {
        $this->birthday = $birthday;
        return $this;
    }

    /**
     * @param string $gender
     */
    public function setGender(string $gender)
    {
        $this->gender = $gender;
        return $this;
    }

    /**
     * @param string $notify_url
     */
    public function setNotifyUrl(string $notify_url)
    {
        $this->notify_url = $notify_url;
        return $this;
    }
    /**
     * @param string $ref
     */
    public function setRef(string $ref)
    {
        $this->ref = $ref;
        return $this;
    }

    /**
     * @param mixed $user_images
     */
    public function setUserImages($user_images)
    {
        $this->user_images = $user_images;
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
        switch ($this->methodType ?? self::VERIFY_REQUEST) {
            case self::VERIFY_REQUEST:
                $data = ['card_no' => $this->card_no, 'id_card' => $this->id_card, 'real_name' => $this->real_name,'mobile'=>$this->mobile];
                break;
            case self::VERIFY_CONFIRM:
                $data = ['card_no' => $this->card_no, 'id_card' => $this->id_card, 'real_name' => $this->real_name,'mobile'=>$this->mobile,
                         'ref'=>$this->ref,'captcha'=>$this->captcha];
                break;
            case self::VERIFY_FOUR:
                $data = ['card_no' => $this->card_no, 'id_card' => $this->id_card, 'real_name' => $this->real_name,'mobile'=>$this->mobile];
                break;
            case self::VERIFY_THREE:
                $data = ['card_no' => $this->card_no, 'id_card' => $this->id_card, 'real_name' => $this->real_name];
                break;
            case self::VERIFY_ID:
                $data = ['id_card' => $this->id_card, 'real_name' => $this->real_name];
                break;
            case self::BANK_INFO:
                $data = ['card_no' => $this->card_no, 'bank_name' => $this->bank_name];
                break;
            case self::USER_EXEMPTED_INFO:
                $data = ['card_type' => $this->card_type, 'id_card' => $this->id_card, 'real_name' => $this->real_name,'comment_apply' => $this->comment_apply,
                         'dealer_id' => $this->config->dealer_id,'broker_id' => $this->config->broker_id,'country'=>$this->country,'gender'=>$this->gender,'ref'=>$this->ref,
                         'notify_url'=>$this->notify_url,'birthday'=>$this->birthday,'user_images'=>$this->user_images];
                break;
            case self::USER_WHITE_CHECK:
                $data = ['id_card' => $this->id_card, 'real_name' => $this->real_name];
                break;

            default:
                throw new \Exception('not des3Data');
        }
        return $data;
    }

    protected function getRequestInfo()
    {
        $methodType = $this->methodType ?? self::VERIFY_REQUEST;

        $method = 'post';
        if (in_array($methodType, [self::BANK_INFO])) {
            $method = 'get';
        }

        $route = Router::VERIFY_REQUEST;
        switch ($methodType) {
            case self::VERIFY_REQUEST:
                $route = Router::VERIFY_REQUEST;
                break;
            case self::VERIFY_CONFIRM:
                $route = Router::VERIFY_CONFIRM;
                break;
            case self::VERIFY_FOUR:
                $route = Router::VERIFY_BANKCARD_FOUR_FACTOR;
                break;
            case self::VERIFY_THREE:
                $route = Router::VERIFY_BANKCARD_THREE_FACTOR;
                break;
            case self::VERIFY_ID:
                $route = Router::VERIFY_ID;
                break;
            case self::BANK_INFO:
                $route = Router::BANK_INFO;
                break;
            case self::USER_EXEMPTED_INFO:
                $route = Router::WHITE_INFO_UPLOAD;
                break;
            case self::USER_WHITE_CHECK:
                $route = Router::USER_WHITE_CHECK;
                break;


        }

        return [$route, $method];
    }

    protected function callback($res)
    {
        if (isset($res['data']) && is_string($res['data'])) {
            $res['data'] = Des3Service::decode($res['data'], $this->config->des3_key);
        }
        return $res;
    }
}
