<?php

namespace App\Foundation\Modules\Response;

use Symfony\Component\HttpFoundation\Response as FoundationResponse;

/**
 * Trait ApiResponse
 * @package App\Traits
 */
class ApiResponseHandle
{
    /**
     * 200 OK - [GET]：服务器成功返回用户请求的数据，该操作是幂等的（Idempotent）。
     *
     * @param          $data
     * @param  string  $message
     * @param  int     $businessCode
     * @param  array   $headers
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function ok($data, string $message, int $businessCode, array $headers = [])
    {
        return $this->succeed($data, $message, FoundationResponse::HTTP_OK, $businessCode, $headers);
    }

    /**
     * 201 CREATED - [POST/PUT/PATCH]：用户新建或修改数据成功。
     *
     * @param          $data
     * @param  string  $message
     * @param  int     $businessCode
     * @param  array   $headers
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function created($data, string $message, int $businessCode, array $headers = [])
    {
        $message = $message ? $message : 'Created';

        return $this->succeed($data, $message, FoundationResponse::HTTP_CREATED, $businessCode, $headers);
    }

    /**
     * 202 Accepted - [*]：表示一个请求已经进入后台排队（异步任务）
     *
     * @param          $data
     * @param  string  $message
     * @param  int     $businessCode
     * @param  array   $headers
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function accepted($data, string $message, int $businessCode, array $headers = [])
    {
        $message = $message ? $message : 'Accepted';

        return $this->succeed($data, $message, FoundationResponse::HTTP_ACCEPTED, $businessCode, $headers);
    }

    /**
     * 204 NO CONTENT - [DELETE]：用户删除数据成功。
     *
     * @param          $data
     * @param  string  $message
     * @param  int     $businessCode
     * @param  array   $headers
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleted($data, string $message, int $businessCode, array $headers = [])
    {
        $message = $message ? $message : 'Deleted';

        return $this->succeed($data, $message, FoundationResponse::HTTP_NO_CONTENT, $businessCode, $headers);
    }

    /**
     * 400 INVALID REQUEST - [POST/PUT/PATCH]：用户发出的请求有错误，服务器没有进行新建或修改数据的操作，该操作是幂等的。
     *
     * @param          $data
     * @param  string  $message
     * @param  int     $businessCode
     * @param  array   $headers
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function badRequest(string $message, $data, int $businessCode, array $headers = [])
    {
        $message = $message ? $message : 'Bad Request';

        return $this->failed($message, $data, FoundationResponse::HTTP_BAD_REQUEST, $businessCode, $headers);
    }


    /**
     * 401 Unauthorized - [*]：表示用户没有权限（令牌、用户名、密码错误）。
     *
     * @param          $data
     * @param  string  $message
     * @param  int     $businessCode
     * @param  array   $headers
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function unauthorized(string $message, $data, int $businessCode, array $headers = [])
    {
        $message = $message ? $message : 'Unauthorized';

        return $this->failed($message, $data, FoundationResponse::HTTP_UNAUTHORIZED, $businessCode, $headers);
    }

    /**
     * 402 Parameters Missing - [POST/PUT/PATCH]：用户请求参数不全
     *
     * @param          $data
     * @param  string  $message
     * @param  int     $businessCode
     * @param  array   $headers
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function parametersMissing(string $message, $data, int $businessCode, array $headers = [])
    {
        $message = $message ? $message : 'Parameters Missing';

        return $this->failed($message, $data, FoundationResponse::HTTP_PAYMENT_REQUIRED, $businessCode, $headers);
    }

    /**
     * 402 Parameters Error - [POST/PUT/PATCH]：用户请求参数错误
     *
     * @param          $data
     * @param  string  $message
     * @param  int     $businessCode
     * @param  array   $headers
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function parametersError(string $message, $data, int $businessCode, array $headers = [])
    {
        $message = $message ? $message : 'Parameters Error';

        return $this->failed($message, $data, FoundationResponse::HTTP_PAYMENT_REQUIRED, $businessCode, $headers);
    }

    /**
     * 403 Forbidden - [*] 表示用户得到授权（与401错误相对），但是访问是被禁止的。
     *
     * @param          $data
     * @param  string  $message
     * @param  int     $businessCode
     * @param  array   $headers
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function forbidden(string $message, $data, int $businessCode, array $headers = [])
    {
        $message = $message ? $message : 'Forbidden';

        return $this->failed($message, $data, FoundationResponse::HTTP_FORBIDDEN, $businessCode, $headers);
    }

    /**
     * 404 NOT FOUND - [*]：用户发出的请求针对的是不存在的记录，服务器没有进行操作，该操作是幂等的。
     *
     * @param          $data
     * @param  string  $message
     * @param  int     $businessCode
     * @param  array   $headers
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function notFound(string $message, $data, int $businessCode, array $headers = [])
    {
        $message = $message ? $message : 'Not Found';

        return $this->succeed($data, $message, FoundationResponse::HTTP_NOT_FOUND, $businessCode, $headers);
    }

    /**
     * 404 NOT FOUND - [*]：用户发出的请求针对的是不存在的记录，服务器没有进行操作，该操作是幂等的。
     *
     * @param  string  $message
     * @param          $data
     * @param  int     $businessCode
     * @param  array   $headers
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function notFoundSingle(string $message, $data, int $businessCode, array $headers = [])
    {
        $message = $message ? $message : 'Not Found';

        return $this->succeed((object)$data, $message, FoundationResponse::HTTP_OK, $businessCode, $headers);
    }

    /**
     * 406 Not Acceptable - [GET]：用户请求的格式不可得（比如用户请求JSON格式，但是只有XML格式）。
     *
     * @param  array   $data
     * @param  string  $message
     * @param  int     $businessCode
     * @param  array   $headers
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function notAcceptable(string $message, array $data, int $businessCode, array $headers = [])
    {
        $message = $message ? $message : 'Request Format Error';

        return $this->failed($message, $data, FoundationResponse::HTTP_NOT_ACCEPTABLE, $businessCode, $headers);
    }

    /**
     * 410 Gone -[GET]：用户请求的资源被永久删除，且不会再得到的。
     *
     * @param          $data
     * @param  string  $message
     * @param  int     $businessCode
     * @param  array   $headers
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function gone(string $message, $data, int $businessCode, array $headers = [])
    {
        $message = $message ? $message : 'Resources Deleted';

        return $this->failed($message, $data, FoundationResponse::HTTP_GONE, $businessCode, $headers);
    }

    /**
     * 422 Unprocessable entity - [POST/PUT/PATCH] 当创建一个对象时，发生一个验证错误。
     *
     * @param          $data
     * @param  string  $message
     * @param  int     $businessCode
     * @param  array   $headers
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function unprocessableEntity(string $message, $data, int $businessCode, array $headers = [])
    {
        $message = $message ? $message : 'Created Validate Error';

        return $this->failed($message, $data, FoundationResponse::HTTP_UNPROCESSABLE_ENTITY, $businessCode, $headers);
    }

    /**
     * 500 INTERNAL SERVER ERROR - [*]：服务器发生错误，用户将无法判断发出的请求是否成功。
     *
     * @param          $data
     * @param  string  $message
     * @param  int     $businessCode
     * @param  array   $headers
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function internalServerError(string $message, $data, int $businessCode, array $headers = [])
    {
        $message = $message ? $message : 'Server Error';

        return $this->failed($message, $data, FoundationResponse::HTTP_INTERNAL_SERVER_ERROR, $businessCode, $headers);
    }

    /**
     * 根据参数返回api的结果[成功]
     *
     * @param          $data
     * @param  string  $message
     * @param  int     $statusCode
     * @param  int     $businessCode
     * @param  array   $header
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function succeed($data, string $message, int $statusCode, int $businessCode, array $header = [])
    {
        return response()->json([
            'code'    => $businessCode,
            'message' => $message,
            'data'    => $data,
        ], $statusCode, $header);
    }

    /**
     * 根据参数返回api的结果[失败]
     *
     * @param  string  $message
     * @param          $errors
     * @param  int     $statusCode
     * @param  int     $businessCode
     * @param  array   $header
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function failed(string $message, $errors, int $statusCode, int $businessCode, array $header = [])
    {
        return response()->json([
            'code'    => $businessCode,
            'message' => $message,
            'details' => (object)$errors,
        ], $statusCode, $header);
    }
}
