<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['middleware' => ['base', 'xss']], function () use ($router) {
    $router->group(['prefix' => 'v2'], function () use ($router) {
        $router->group(['as' => 'accounts', 'prefix' => 'accounts'], function ($router) {
            $router->group(['middleware' => 'auth'], function ($router) {
                $router->post('{uuid}/resource', [
                    'as'   => 'updateResource',
                    'uses' => 'AccountController@userResourceUpdateV2'
                ]);
                $router->get('{uuid}/face_token', ['as' => 'face_token', 'uses' => 'AccountController@getFaceTokenV2']);
                $router->post('{uuid}/set_auth', ['as' => 'set_auth', 'uses' => 'AccountController@setAuthUserV2']);
            });
        });
        /** 认证 */
        $router->group(['as' => 'sms', 'prefix' => 'auth'], function () use ($router) {
            $router->post('/', ['as' => 'store', 'uses' => 'AuthController@authV2']);
            $router->post('check', ['as' => 'check_auth', 'uses' => 'AuthController@checkFaceAuth']);
        });
    });
});
