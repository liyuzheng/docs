<?php


namespace App\Pockets;

use App\Foundation\Modules\Pocket\BasePocket;
use Earnp\GoogleAuthenticator\GoogleAuthenticator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GooglePocket extends BasePocket
{
    /**
     * 获取谷歌secret
     *
     * @return array
     */
    public function getGoogleSecret()
    {
        $secret            = GoogleAuthenticator::CreateSecret();
        $secret['qr_code'] = QrCode::encoding('UTF-8')->size(180)->margin(1)->generate($secret['codeurl']);

        return $secret;
    }

    /**
     * 校验谷歌验证码
     *
     * @param $secret
     * @param $code
     *
     * @return bool|\Earnp\GoogleAuthenticator\Response
     */
    public function verifyCode($secret, $code)
    {
        return GoogleAuthenticator::CheckCode($secret, $code);
    }
}
