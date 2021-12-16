<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->group(['prefix' => 'callback', 'middleware' => 'callback'], function () use ($router) {
    $router->post('pingxx/pay', [
        'as'   => 'callback.pingxx.pay',
        'uses' => 'CallBackController@pingXXPay'
    ]);
    $router->post('apple/pay', [
        'as'   => 'callback.apple.pay',
        'uses' => 'CallBackController@applePay'
    ]);
    //云信callback
    $router->post('nimserver', [
        'as'   => 'callback.nimserver',
        'uses' => 'CallBackController@callbackNimServer'
    ]);
    //数美视频鉴黄callback
    $router->post('fengkong/video', [
        'as'   => 'callback.fengkong.video',
        'uses' => 'CallBackController@callbackFengkongVideo'
    ]);
    //云账户callback
    $router->post('wgc', [
        'as'   => 'callback.wgc',
        'uses' => 'CallBackController@wgc'
    ]);

    //微信公众号回调
    $router->post('wechat/mp', [
        'as'   => 'wechat.mp',
        'uses' => 'CallBackController@callbackWeChatMp'
    ]);
    $router->get('wechat/mp', [
        'as'   => 'wechat.mp',
        'uses' => 'CallBackController@callbackWeChatMp'
    ]);

    //元知短信平台回调
    $router->post('yuanzhi', [
        'as'   => 'yuanzhi',
        'uses' => 'CallBackController@callbackYuanZhiSendMsg'
    ]);

    //智牙平台回调
    $router->post('zhichi', [
        'as'   => 'zhichi',
        'uses' => 'CallBackController@zhichi'
    ]);

    //云信回调检测
    $router->post('netease/verify', [
        'as'   => 'netease.verify',
        'uses' => 'CallBackController@neteaseVerify'
    ]);

    //云信回调检测
    $router->get('netease/verify', [
        'as'   => 'netease.verify',
        'uses' => 'CallBackController@neteaseVerify'
    ]);
});
