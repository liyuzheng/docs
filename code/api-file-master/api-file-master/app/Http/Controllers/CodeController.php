<?php


namespace App\Http\Controllers;


use App\Mail\VerifyCodeMail;
use App\Models\Sms;
use Illuminate\Http\Request;
use App\Http\Requests\Sms\CodeStoreRequest;
use Illuminate\Support\Facades\Mail;

class CodeController extends BaseController
{
    /**
     * 发送验证码
     *
     * @param  CodeStoreRequest  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function smsStore(CodeStoreRequest $request)
    {
        $code       = rand(1000, 9999);
        $mobile     = $request->get('mobile');
        $sendStatus = pocket()->sms->IsSendSmsLastMinute($mobile);
        if (!$sendStatus->getStatus()) {
            return api_rr()->serviceUnknownForbid($sendStatus->getMessage());
        }

        $smsResp = $request->has('area')
            ? pocket()->notify->sms($this->getAppName(), $request->get('area'), $code, $mobile)
            : pocket()->notify->sms($this->getAppName(), 86, $code, $mobile);

        if ($smsResp->getStatus()) {
            $smsData = ['mobile' => $mobile, 'code' => $code,];
            rep()->sms->m()->create(array_merge($smsData, [
                'type'       => Sms::TYPE_STR_MAPPING[$request->type] ?? Sms::TYPE_MOBILE_SMS,
                'area'       => $request->has('area') ? $request->get('area') : 86,
                'client_id'  => $this->getClientId(),
                'client_ip'  => get_client_real_ip(),
                'expired_at' => time() + 300
            ]));
            pocket()->sms->smsLimit($mobile);

            return api_rr()->getOK([], trans('messages.sms_send_success'));
        }

        return api_rr()->serviceUnknownForbid($smsResp->getMessage());
    }

    /**
     * 发送邮件验证码
     *
     * @param  CodeStoreRequest  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function emailStore(CodeStoreRequest $request)
    {
        $code     = rand(1000, 9999);
        $email    = $request->get('email');
        $codeType = Sms::TYPE_STR_MAPPING[$request->type] ?? Sms::TYPE_MOBILE_SMS;
        $smsData  = ['email' => $email, 'code' => $code,];
        rep()->sms->m()->create(array_merge($smsData, [
            'type'       => $codeType,
            'client_id'  => $this->getClientId(),
            'client_ip'  => get_client_real_ip(),
            'expired_at' => time() + 300
        ]));

        $option = $codeType == Sms::TYPE_RESET_PASSWORD ? '重置密码' : '注册小圈';
        $mail   = new VerifyCodeMail($code, $option);
        Mail::to($email)->send($mail);

        return api_rr()->getOK([], '邮件发送成功');
    }

    /**
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function code(Request $request)
    {
        $sms = rep()->sms->getQuery()->select('code', 'created_at')
            ->where('mobile', $request->mobile)->get();

        return api_rr()->getOK($sms);
    }

    /**
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function codeList(Request $request)
    {
        $sms = rep()->sms->getQuery()->select('mobile', 'code')
            ->orderByDesc('id')->limit(10)->get();

        return api_rr()->getOK($sms);
    }
}
