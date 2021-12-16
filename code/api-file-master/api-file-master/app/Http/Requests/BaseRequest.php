<?php

namespace App\Http\Requests;

use App\Constant\ApiBusinessCode;
use Illuminate\Contracts\Validation\Validator;
use App\Foundation\Modules\FormRequest\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Foundation\Modules\FormRequest\ParameterErrorException;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response as FoundationResponse;

/**
 * Class BaseRequest
 * @package App\Http\Requests
 */
class BaseRequest extends FormRequest
{
    protected $routeName;

    /**
     * BaseRequest constructor.
     *
     * @param  array  $query
     * @param  array  $request
     * @param  array  $attributes
     * @param  array  $cookies
     * @param  array  $files
     * @param  array  $server
     * @param  null   $content
     */
    public function __construct(
        array $query = [],
        array $request = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        $content = null
    ) {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);
        $this->routeName = current_route_name();
    }

    /**
     * Get current request path alias name
     *
     * @return string
     */
    public function getRouteName()
    {
        return $this->routeName;
    }

    public function authorize()
    {
        return true;
    }

    /**
     * 判断是request验证中是否有需要发送错误信息的方法
     *
     * @param  Validator  $validator
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->toArray();
        if ($validator->fails()) {
            $details = [];
            foreach ($errors as $key => $error) {
                $details[$key] = array_first($error);
            }
            $realMessage = array_first($errors)[0];
            $this->log($errors, $realMessage);
            $response = [
                'code'    => ApiBusinessCode::REQUEST_PARAMETERS_VERIFY_FAILED,
                'message' => '参数错误',
                'details' => (object)$details,
            ];
            //记录到log系统
            $response = response()->json($response, FoundationResponse::HTTP_PAYMENT_REQUIRED, []);
            throw new HttpResponseException($response);
        }
    }

    /**
     * 记录日志
     *
     * @param $errors
     * @param $realMessage
     */
    public function log($errors, $realMessage)
    {
        $urlArr = parse_url(URL::full());
        $arr    = [
            'route_name' => $this->routeName,
            'path'       => $urlArr['path'] ?? '',
            'query'      => $urlArr['query'] ?? '',
            'body_query' => request()->query(),
            'body_post'  => request()->post(),
            'error_arr'  => $errors,
            'error'      => $realMessage,
            'created_at' => time()
        ];
        logger()->setLogType('request_verify_failed:' . str_replace('.', '_', $this->routeName))
            ->error(json_encode($arr));
        //        pocket()->common->commonQueueMoreByPocketJob(
        //            pocket()->mongodb,
        //            'postApiParameterError',
        //            [$arr]
        //        );
    }
}
