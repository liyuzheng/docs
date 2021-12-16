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

$router->group(['prefix' => 'v99'], function () use ($router) {
    /** 解析二维码内容 */
    $router->post('parse/qrcode', ['as' => 'parse.qrcode', 'uses' => 'InternalController@parseQrCode']);
    $router->get('invite_bind_url/{inviteCode}',
        ['as' => 'invite_bind_url', 'uses' => 'InternalController@InviteBindUrl']
    );
    $router->get('configs', ['as' => 'configs', 'uses' => 'InternalController@configs']);
    $router->get('invite_slb', ['as' => 'invite_slb', 'uses' => 'InternalController@inviteSlb']);
    $router->get('sms_code', ['as' => 'sms_code', 'uses' => 'InternalController@smsCode']);
    $router->get('channel', ['as' => 'channel', 'uses' => 'InternalController@channel']);
});
