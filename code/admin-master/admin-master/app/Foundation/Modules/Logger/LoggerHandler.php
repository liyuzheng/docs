<?php

namespace App\Foundation\Modules\Logger;

use App\Exceptions\ServiceException;
use Illuminate\Support\Facades\Log;

/**
 * Class LoggerHandler
 * @package App\Foundation\Handlers
 *
 * @method void emergency(string $message, array $context = [])
 * @method void alert(string $message, array $context = [])
 * @method void critical(string $message, array $context = [])
 * @method void error(string $message, array $context = [])
 * @method void warning(string $message, array $context = [])
 * @method void notice(string $message, array $context = [])
 * @method void info(string $message, array $context = [])
 * @method void debug(string $message, array $context = [])
 */
class LoggerHandler
{
    protected $logType = 'general';
    protected $methods = ['notice', 'emergency', 'alert', 'critical', 'error', 'warning', 'info', 'debug'];

    const LOG_TYPE = 'log_type';
    // 给云信发送的消息
    const LOG_TYPE_NIM_ROOM_MSG = 'nim_room_msg';
    // PINGXX 支付成功
    const LOG_TYPE_PINGPP_RECHARGE_SUCCESS = 'pingxx_recharge_success';
    // PINGXX 支付失败
    const LOG_TYPE_PINGPP_RECHARGE_FAILED = 'pingxx_recharge_failed';
    // iap 支付成功
    const LOG_TYPE_IAP_RECHARGE_SUCCESS = 'iap_recharge_success';
    // iap 支付失败
    const LOG_TYPE_IAP_RECHARGE_FAILED = 'iap_recharge_failed';
    // 通话扣费
    const LOG_TYPE_TRAN_CALL_COST = 'tran_call_cost';
    // 提现失败
    const LOG_TYPE_WITHDRAW_FAILED = 'withdraw_failure';
    // YEE_PAY 支付成功
    const LOG_TYPE_YEEPAY_RECHARGE_SUCCESS = 'yeepay_recharge_success';
    // YEE_PAY 支付失败
    const LOG_TYPE_YEEPAY_RECHARGE_FAILED = 'yeepay_recharge_failed';

    /**
     * Set current logger object custom type
     *
     * @param  string  $name
     *
     * @return $this
     */
    public function setLogType($name)
    {
        $this->logType = $name;

        return $this;
    }

    /**
     * Get custom logger type
     *
     * @return mixed|string
     */
    public function getLogType()
    {
        return $this->logType;
    }

    /**
     * 发送云信房间消息的type
     *
     * @return $this
     */
    public function setTypeNimRoomMsg()
    {
        $this->logType = self::LOG_TYPE_NIM_ROOM_MSG;

        return $this;
    }

    /**
     * ping++支付成功日志
     *
     * @return $this
     */
    public function setPingPPRechargeSuccess()
    {
        $this->logType = self::LOG_TYPE_PINGPP_RECHARGE_SUCCESS;

        return $this;
    }
    /**
     * yeepay支付成功日志
     *
     * @return $this
     */
    public function setYeepayRechargeSuccess()
    {
        $this->logType = self::LOG_TYPE_YEEPAY_RECHARGE_SUCCESS;

        return $this;
    }

    /**
     * ping++支付成功日志
     *
     * @return $this
     */
    public function setPayJsRechargeSuccess()
    {
        $this->logType = self::LOG_TYPE_PAYJS_RECHARGE_SUCCESS;

        return $this;
    }

    /**
     * ping++支付失败日志
     *
     * @return $this
     */
    public function setPingPPRechargeFailed()
    {
        $this->logType = self::LOG_TYPE_PINGPP_RECHARGE_FAILED;

        return $this;
    }

    /**
     * yeepay支付失败日志
     *
     * @return $this
     */
    public function setYeepayRechargeFailed()
    {
        $this->logType = self::LOG_TYPE_YEEPAY_RECHARGE_FAILED;

        return $this;
    }

    /**
     * ping++支付失败日志
     *
     * @return $this
     */
    public function setTranCallCostFailed()
    {
        $this->logType = self::LOG_TYPE_TRAN_CALL_COST;

        return $this;
    }

    /**
     * iap支付成功日志
     *
     * @return $this
     */
    public function setIAPRechargeSuccess()
    {
        $this->logType = self::LOG_TYPE_IAP_RECHARGE_SUCCESS;

        return $this;
    }

    /**
     * iap支付失败日志
     *
     * @return $this
     */
    public function setIAPRechargeFailed()
    {
        $this->logType = self::LOG_TYPE_IAP_RECHARGE_FAILED;

        return $this;
    }

    /**
     * 提现失败 LogType
     *
     * @return $this
     */
    public function setWithdrawFailed()
    {
        $this->logType = self::LOG_TYPE_WITHDRAW_FAILED;

        return $this;
    }

    /**
     * Keep different levels of logs for different rooms
     *
     * @param  string  $method
     * @param  array   $args
     *
     * @throws ServiceException
     */
    public function __call($method, $args)
    {
        if (!in_array($method, $this->methods)) {
            throw new ServiceException(sprintf("Method %s not exists", $method));
        }
        $context = [self::LOG_TYPE => $this->getLogType()];
        Log::channel('stack')->$method($args[0], isset($args[1]) ? array_merge($context, $args[1]) : $context);
    }
}