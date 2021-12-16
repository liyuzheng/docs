<?php
/**
 * Created by PhpStorm.
 * User: ailuoy
 * Date: 2019/2/21
 * Time: 上午11:28
 */

namespace App\Foundation\Modules\ResultReturn;


use Throwable;

/**
 * Class ResultReturn
 * @package App\Foundation\Modules\ResultReturn
 */
class ResultReturn
{
    private const RESULT_RETURN_STATUS_SUCCESS = true;
    private const RESULT_RETURN_STATUS_FAILED  = false;

    private $status;
    private $message;
    private $data;

    /**
     * ResultReturn constructor.
     *
     * @param bool   $status
     * @param string $message
     * @param mixed  $data
     */
    public function __construct(bool $status, string $message, $data)
    {
        $this->status  = $status;
        $this->message = $message;
        $this->data    = $data;
    }

    /**
     * @return bool
     */
    public function getStatus(): bool
    {
        return $this->status;
    }

    /**
     * @param bool $status
     *
     * @return ResultReturn
     */
    public function setStatus(bool $status): ResultReturn
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     *
     * @return ResultReturn
     */
    public function setMessage(string $message): ResultReturn
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     *
     * @return ResultReturn
     */
    public function setData($data): ResultReturn
    {
        $this->data = $data;

        return $this;
    }


    /**
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     *
     * @throws ResultReturnException
     */
    public static function exception(string $message = "", int $code = 500, Throwable $previous = null)
    {
        throw new ResultReturnException($message, $code, $previous);
    }

    /**
     * Returns a success message
     *
     * @param mixed  $data
     * @param string $msg
     *
     * @return ResultReturn
     */
    public static function success($data, $msg = '')
    {
        return new ResultReturn(self::RESULT_RETURN_STATUS_SUCCESS, $msg, $data);
    }

    /**
     * Returns a failure message
     *
     * @param string $msg
     * @param mixed  $data
     *
     * @return ResultReturn
     */
    public static function failed($msg, $data = null)
    {
        return new ResultReturn(self::RESULT_RETURN_STATUS_FAILED, $msg, $data);
    }
}
