<?php


namespace App\Http\Controllers;


use Illuminate\Http\Request;

class ChatController extends BaseController
{
    /**
     * 检测女生发的第一条消息
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkGirlFirstMsg(Request $request)
    {
        $content = $request->post('content');
        $alert   = '';
        $preg    = '/[a-zA-Z0-9]{1}/isu';
        preg_match_all($preg, $content, $matches);
        if (count($matches[0]) >= 6) {
            $content = preg_replace($preg, '*', $content);
            $alert   = '消息疑似包含联系方式，请勿散播个人联系方式，多次违反将作出惩罚';
        }

        return api_rr()->postOK(['content' => $content, 'alert' => $alert]);
    }
}
