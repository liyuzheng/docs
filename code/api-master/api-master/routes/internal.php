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

$router->group(['prefix' => 'internal', 'as' => 'internal'], function () use ($router) {
    $router->get('/users/{uuid}', ['as' => 'users', 'uses' => 'InternalController@getUserInfo']);
    $router->post('/messages/cc', ['as' => 'messages.cc', 'uses' => 'InternalController@coldStartMessageCc']);
    $router->post('/downloads-cold-start-resources',
        ['as' => 'downloads.cold.start.resources', 'uses' => 'InternalController@downloadsColdStartResources']);
    $router->post('/users/{uuid}/cut-sync',
        ['as' => 'users.update.cut_sync', 'uses' => 'InternalController@cutSyncColdStartUser']);
    $router->post('/users/{uuid}/active-at',
        ['as' => 'users.update.active_at', 'uses' => 'InternalController@updateColdStartUserActiveTime']);
    $router->post('/users/{uuid}/location',
        ['as' => 'users.update.location', 'uses' => 'InternalController@updateColdStartUserLocation']);
});
