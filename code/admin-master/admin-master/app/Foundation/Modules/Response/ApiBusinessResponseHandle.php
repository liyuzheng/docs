<?php
/**
 * Created by PhpStorm.
 * User: ailuoy
 * Date: 2019/6/1
 * Time: 下午3:48
 */

namespace App\Foundation\Modules\Response;


use App\Constant\ApiBusinessCode;
use App\Constant\ErrorCode;
use Symfony\Component\HttpFoundation\Response as FoundationResponse;

class ApiBusinessResponseHandle
{
    private $apiHandle;

    public function __construct()
    {
        $this->apiHandle = new ApiResponseHandle();
    }

    /**
     * 服务端未知错误
     *
     * @param  string  $message
     * @param  array   $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function serviceUnknownForbid($message = 'service unknown forbid', $data = [])
    {
        return $this->apiHandle->internalServerError($message, $data, ApiBusinessCode::SERVICE_UNKNOWN_FORBID);
    }


    /**
     * 数据库错误
     *
     * @param  string  $message
     * @param  array   $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateDbError($message = 'db error', $data = [])
    {
        return $this->apiHandle->internalServerError($message, $data, ApiBusinessCode::SERVICE_UPDATE_DB_ERROR);
    }

    /**
     * get请求成功
     *
     * @param          $data
     * @param  string  $message
     * @param  array   $header
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOK($data, $message = 'succeed', $header = [])
    {
        return $this->apiHandle->ok($data, $message, ApiBusinessCode::SUCCESS, $header);
    }


    /**
     * [分页]业务基本获取不到结果,比如没有关注人
     *
     * @param  string  $message
     * @param  array   $header
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOKnotFoundResultPaging($message = 'succeed', $header = [])
    {
        return $this->apiHandle->ok((object)[], $message, ApiBusinessCode::SUCCESS, $header);
    }

    /**
     * [分页]业务基本获取不到结果,比如没有关注人
     *
     * @param  string  $message
     * @param  array   $header
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOKnotFoundResult($message = 'succeed', $header = [])
    {
        return $this->apiHandle->ok([], $message, ApiBusinessCode::SUCCESS, $header);
    }

    /**
     * post请求成功
     *
     * @param          $data
     * @param  string  $message
     * @param  array   $header
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function postOK($data, $message = 'succeed', $header = [])
    {
        return $this->apiHandle->ok($data, $message, ApiBusinessCode::SUCCESS, $header);
    }

    /**
     * put请求成功
     *
     * @param          $data
     * @param  string  $message
     * @param  array   $header
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function putOK($data, $message = 'succeed', $header = [])
    {
        return $this->apiHandle->ok($data, $message, ApiBusinessCode::SUCCESS, $header);
    }

    /**
     * delete请求成功
     *
     * @param          $data
     * @param  string  $message
     * @param  array   $header
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteOK($data, $message = 'succeed', $header = [])
    {
        return $this->apiHandle->ok($data, $message, ApiBusinessCode::SUCCESS, $header);
    }

    /**
     * 找不到用户
     *
     * @param  string  $message
     * @param  array   $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function notFoundUser($message = '找不到用户', $data = [])
    {
        return $this->apiHandle->notFound($message, $data, ApiBusinessCode::USER_NOT_FOUND);
    }


    /**
     * 找不到结果
     *
     * @param  string  $message
     * @param  array   $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function notFoundResult($message = '找不到结果', $data = [])
    {
        return $this->apiHandle->notFound($message, $data, ApiBusinessCode::NOT_FOUND);
    }

    /**
     * 用户余额不足
     *
     * @param  string  $message
     * @param  array   $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function lackOfBalance($message = '余额不足', $data = [])
    {
        return $this->apiHandle->forbidden($message, $data, ApiBusinessCode::LACK_OF_BALANCE);
    }

    /**
     * 操作被禁止,客户端弹出提示
     *
     * @param  string  $message
     * @param  array   $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function forbidCommon($message = '禁止的动作', $data = [])
    {
        return $this->apiHandle->forbidden($message, $data, ApiBusinessCode::FORBID_COMMON);
    }

    /**
     * 用户未注册
     *
     * @param  string  $message
     * @param  array   $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function notRegistered($message = '用户未注册', $data = [])
    {
        return $this->apiHandle->forbidden($message, $data, ApiBusinessCode::NOT_REGISTER);
    }

    /**
     * header中缺失token
     *
     * @param  string  $message
     * @param  array   $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function authTokenMissing($message = 'not found Auth-Token in headers', $data = [])
    {
        return $this->apiHandle->unauthorized($message, $data, ApiBusinessCode::AUTH_TOKEN_MISSING);
    }


    /**
     * 请求参数错误
     *
     * @param  string  $message
     * @param  array   $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestParameterError($message = 'Parameter error', $data = [])
    {
        return $this->apiHandle->parametersError($message, $data, ApiBusinessCode::REQUEST_PARAMETER_ERROR);
    }

    /**
     * 请求参数缺失
     *
     * @param  string  $message
     * @param  array   $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestParameterMissing($message = 'Parameter missing', $data = [])
    {
        return $this->apiHandle->parametersError($message, $data, ApiBusinessCode::REQUEST_PARAMETER_MISSING);
    }

    /**
     * 图片比对失败
     *
     * @param  string  $message
     * @param  array   $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function picCompareFail($message = '图片比对失败', $data = [])
    {
        return $this->apiHandle->forbidden($message, $data, ApiBusinessCode::PIC_COMPARE_FAIL);
    }

    /**
     * 图片比对失败
     *
     * @param  string  $message
     * @param  array   $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function goodsFailure($message = '商品已失效', $data = [])
    {
        return $this->apiHandle->forbidden($message, $data, ApiBusinessCode::GOODS_FAILURE);
    }

    /**
     * 重复解锁用户
     *
     * @param  string  $message
     * @param  array   $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userHaveUnlocked($message = '已经解锁过啦', $data = [])
    {
        return $this->apiHandle->forbidden($message, $data, ApiBusinessCode::HAVE_UNLOCKED);
    }

    /**
     * 用户被某个用户拉黑
     *
     * @param  string  $message
     * @param  array   $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function blockedByUser($message = '你已经被用户拉黑啦, 不能查看她对信息哦～', $data = [])
    {
        return $this->apiHandle->forbidden($message, $data, ApiBusinessCode::USER_BLOCK);
    }

    /**
     * 用户被拉黑
     *
     * @param  string  $message
     * @param  array   $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userBlacklist($message = '您已经被关进小黑屋了', $data = [])
    {
        return $this->apiHandle->forbidden($message, $data, ApiBusinessCode::BLACKLIST_LOCK);
    }

    /**
     * 引导用户充值
     *
     * @param $message
     * @param $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function guideRecharge($message, $data)
    {
        return $this->apiHandle->forbidden($message, $data, ApiBusinessCode::GUIDE_RECHARGE);
    }

    /**
     * 返回自定义错误
     *
     * @param  string  $message  消息
     * @param  int     $code     业务code
     * @param  array   $data     返回数据
     * @param  int     $status   http code
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function customFailed(
        string $message,
        int $code,
        array $data = [],
        int $status = FoundationResponse::HTTP_FORBIDDEN
    ) {
        return $this->apiHandle->failed($message, $data, $status, $code);
    }
}
