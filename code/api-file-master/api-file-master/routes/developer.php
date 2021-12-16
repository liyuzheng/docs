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

$router->group(['prefix' => 'developer'], function () use ($router) {
    $router->get('/', ['as' => '/', 'uses' => 'DeveloperController@index']);
    $router->get('sms', ['as' => '/', 'uses' => 'DeveloperController@sms']);
    $router->get('messages/audit_pass/{uuid}', ['as' => '/', 'uses' => 'DeveloperController@messagesAuditPass']);
    $router->get('messages/audit_failed/{uuid}', ['as' => '/', 'uses' => 'DeveloperController@messagesAuditFailed']);
    $router->get('notify/{sUUid}/{rUUid}', ['as' => '/', 'uses' => 'DeveloperController@sendMessage']);
    $router->get('icon-notify/{uuid}', ['as' => '/', 'uses' => 'DeveloperController@iconNotice']);
    $router->get('auth/{uuid}/get_auth_token', ['as' => '/', 'uses' => 'DeveloperController@getAuthToken']);
    $router->get('user/{uuid}/update-invite-test', ['as' => '/', 'uses' => 'DeveloperController@createUserInviteTest']);
    $router->get('user/{uuid}/add-active-days', ['as' => '/', 'uses' => 'DeveloperController@addUserLoginDays']);
    $router->get('user/{uuid}/cold-start-sync', ['as' => '/', 'uses' => 'DeveloperController@syncColdStartUser']);
    $router->get('test/forced_to_update', ['as' => '/', 'uses' => 'DeveloperController@forcedToUpdate']);
    $router->get('code', ['as' => 'code', 'uses' => 'CodeController@code']);
    $router->get('code_list', ['as' => 'code_list', 'uses' => 'CodeController@codeList']);
    $router->get('update_config', ['as' => 'update_config', 'uses' => 'DeveloperController@updateConfig']);
    $router->get('get_user_by_name', ['as' => 'get_user_by_name', 'uses' => 'DeveloperController@getUserByName']);
});
