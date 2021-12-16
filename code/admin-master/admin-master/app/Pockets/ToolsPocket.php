<?php


namespace app\Pockets;

use Zxing\QrReader;
use App\Foundation\Modules\Pocket\BasePocket;
use App\Foundation\Modules\ResultReturn\ResultReturn;

class ToolsPocket extends BasePocket
{
    /**
     * 解析二维码内容
     *
     * @param  string  $path
     *
     * @return ResultReturn
     */
    public function getParsingQrCode(string $path)
    {
        if (!file_exists($path)) {
            return ResultReturn::failed('文件不存在');
        }
        $qrcode = new QrReader($path);
        $text   = $qrcode->text();

        $returnText = $text ? $text : '';

        return ResultReturn::success($returnText);
    }
}
