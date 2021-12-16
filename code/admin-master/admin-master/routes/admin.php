<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->get('/', function () use ($router) {
    return response()->json([
        'frame_version' => $router->app->version(),
        'timestamp'     => time()
    ]);
});

$router->group(['as' => 'admin', 'prefix' => 'admin'], function () use ($router) {
    $router->post('login', ['as' => 'login', 'uses' => 'AuthController@login']);

    $router->group(['middleware' => 'admin'], function () use ($router) {
        $router->group(['as' => 'accounts', 'prefix' => 'accounts'], function () use ($router) {
            $router->post('pass', ['as' => 'pass', 'uses' => 'AccountController@checkPass']);
            $router->post('fail', ['as' => 'fail', 'uses' => 'AccountController@checkFail']);
            $router->post('ignore', ['as' => 'ignore', 'uses' => 'AccountController@checkIgnore']);
            $router->get('charm', ['as' => 'charm', 'uses' => 'AccountController@charms']);
            $router->get('{uuid}/charm_detail', ['as' => 'charm_detail', 'uses' => 'AccountController@charmDetail']);
            $router->get('charm_update', ['as' => 'charm_update', 'uses' => 'AccountController@charmUpdate']);
            $router->get('wechat_update', ['as' => 'wechat_update', 'uses' => 'AccountController@wechatUpdate']);
            $router->delete('{uuid}/charm', ['as' => 'charm_del', 'uses' => 'AccountController@charmDel']);
            //            $router->get('{uuid}/send_msg', ['as' => 'sendMsg', 'uses' => 'AccountController@sendAuthMsg']);
            $router->post('{uuid}/lock_wechat', ['as' => 'lock_wechat', 'uses' => 'AccountController@lockWechat']);
            $router->post('{uuid}/open_wechat', ['as' => 'open_wechat', 'uses' => 'AccountController@openWechat']);
            $router->post('{uuid}/hide_user', ['as' => 'hide_user', 'uses' => 'AccountController@hideUser']);
            $router->post('{uuid}/close_wechat_trade',
                ['as' => 'close_wechat_trade', 'uses' => 'AccountController@closeUserWeChatTrade']);
            $router->get('{uuid}/recharge_detail',
                ['as' => 'recharge_detail', 'uses' => 'AccountController@userRechargeDetail']);
            $router->get('{uuid}/free_recharge_detail',
                ['as' => 'free_recharge_detail', 'uses' => 'AccountController@userFreeRechargeDetail']);
            $router->get('customer_script',['as'=>'show_customer_service_script','uses'=>'AccountController@showCustomerServiceScript']);
            $router->post('customer_script',['as'=>'create_customer_service_script','uses'=>'AccountController@createCustomerServiceScript']);
            $router->put('customer_script',['as'=>'update_customer_service_script','uses'=>'AccountController@updateCustomerServiceScript']);
            $router->delete('customer_script',['as'=>'delete_customer_service_script','uses'=>'AccountController@deleteCustomerServiceScript']);
        });

        $router->group(['as' => 'user', 'prefix' => 'user'], function () use ($router) {
            $router->get('/', ['as' => 'list', 'uses' => 'UserController@list']);
            $router->get('{uuid}/detail', ['as' => 'detail', 'uses' => 'UserController@detail']);

            $router->get('member', ['as' => 'member', 'uses' => 'UserController@memberList']);
            $router->get('report', ['as' => 'report', 'uses' => 'UserController@export']);
            $router->get('report/{uuid}/detail', ['as' => 'report_detail', 'uses' => 'UserController@reportDetail']);
            $router->get('feedback', ['as' => 'feedback', 'uses' => 'UserController@feedback']);
            $router->get('charm', ['as' => 'charm', 'uses' => 'UserController@charmList']);
            $router->get('withdraw', ['as' => 'withdraw', 'uses' => 'UserController@withdraw']);
            $router->post('commit_withdraw', ['as' => 'commit_withdraw', 'uses' => 'UserController@commitWithdraw']);
            $router->get('black', ['as' => 'blacklist', 'uses' => 'UserController@blackList']);
            $router->get('user_black', ['as' => 'user_black_status', 'uses' => 'UserController@getUserBlackStatus']);
            $router->get('job', ['as' => 'job', 'uses' => 'UserController@job']);
            $router->post('black/{uuid}', ['as' => 'black', 'uses' => 'UserController@blackStore']);
            $router->delete('black', ['as' => 'black_del', 'uses' => 'UserController@blackDel']);
            $router->post('report/feedback', ['as' => 'report_feedback', 'uses' => 'UserController@sendReportMessage']);
            $router->post('report/finish', ['as' => 'report_finish', 'uses' => 'UserController@finishReport']);
            $router->get('{uuid}/report', ['as' => 'user_report', 'uses' => 'UserController@getUserReport']);
            $router->post('repay', ['as' => 'repay', 'uses' => 'UserController@repay']);
            $router->get('strong_remind', ['as' => 'remind_list', 'uses' => 'UserController@getStrongRemind']);
            $router->post('strong_remind', ['as' => 'strong_remind', 'uses' => 'UserController@sendStrongRemind']);
            $router->get('invite-records', ['as' => 'invite_records', 'uses' => 'UserController@inviteUsers']);
            $router->get('special_log', ['as' => 'special_log', 'uses' => 'UserController@getSpecialLog']);
            $router->get('all_operator', ['as' => 'all_operator', 'uses' => 'UserController@getAllOperator']);
            $router->post('{uuid}/mark', ['as' => 'mark_charm_girl', 'uses' => 'UserController@markCharmGirl']);
            $router->get('wechat', ['as' => 'wechat_user', 'uses' => 'UserController@getUsersByWechat']);
            $router->get('login_record', ['as' => 'login_record', 'uses' => 'UserController@userLoginRecord']);
            $router->get('login_record_detail', ['as' => 'login_record_detail', 'uses' => 'UserController@loginRecordDetail']);
            $router->get('regions', ['as' => 'regions', 'uses' => 'UserController@getUserCitys']);
        });

        $router->group(['as' => 'record', 'prefix' => 'record'], function () use ($router) {
            $router->get('daily', ['as' => 'daily', 'uses' => 'RecordController@dailyRecord']);
        });

        $router->group(['as' => 'chat', 'prefix' => 'chat'], function ($router) {
            /** 聊天记录 */
            $router->get('/{sendNumber}/{receiveNumber}', ['as' => 'show', 'uses' => 'ChatController@show']);
            $router->get('/user', ['as' => 'user', 'uses' => 'ChatController@user']);
            $router->get('/spam', ['as' => 'spam', 'uses' => 'ChatController@spamChat']);
            $router->get('/spam/detail/{uuid}', ['as' => 'detail', 'uses' => 'ChatController@spamDetail']);
            $router->post('/spam/mark/{uuid}', ['as' => 'spam_mark', 'uses' => 'ChatController@spamMark']);
        });
        $router->group(['as' => 'setting', 'prefix' => 'setting'], function () use ($router) {
            $router->get('configs', ['as' => 'configs', 'uses' => 'SettingController@configs']);
            $router->get('configs/value',
                ['as' => 'config_default_value', 'uses' => 'SettingController@configDefaultValue']);
            $router->get('configs/{id}', ['as' => 'config_detail', 'uses' => 'SettingController@configDetail']);
            $router->post('configs/{id}', ['as' => 'config_update', 'uses' => 'SettingController@configUpdate']);
            $router->post('configs', ['as' => 'store_config', 'uses' => 'SettingController@storeConfig']);

            $router->get('goods', ['as' => 'goods', 'uses' => 'SettingController@goods']);
            $router->get('goods/value', ['as' => 'good_default_value', 'uses' => 'SettingController@goodDefaultValue']);
            $router->get('goods/{id}', ['as' => 'good_detail', 'uses' => 'SettingController@goodDetail']);
            $router->post('goods/{id}', ['as' => 'good_update', 'uses' => 'SettingController@goodUpdate']);

            $router->get('configs_jpush', ['as' => 'configs_jpush', 'uses' => 'SettingController@configsJpush']);
            $router->post('configs_jpush',
                ['as' => 'configs_jpush_add', 'uses' => 'SettingController@storeConfigsPush']);
            $router->get('configs_jpush/value',
                ['as' => 'jpush_default_value', 'uses' => 'SettingController@configsJpushDefaultValue']);
            $router->get('configs_jpush/{id}',
                ['as' => 'jpush_detail', 'uses' => 'SettingController@configsJpushDetail']);
            $router->post('configs_jpush/{id}',
                ['as' => 'jpush_update', 'uses' => 'SettingController@configsJpushUpdate']);
        });

        $router->group(['as' => 'version', 'prefix' => 'version'], function () use ($router) {
            $router->get('/', ['as' => 'list', 'uses' => 'VersionController@version']);
            $router->post('/', ['as' => 'store', 'uses' => 'VersionController@store']);
            $router->get('{id}', ['as' => 'show', 'uses' => 'VersionController@show']);
            $router->post('{id}', ['as' => 'update', 'uses' => 'VersionController@update']);
            $router->post('audit/{id}', ['as' => 'audit', 'uses' => 'VersionController@audit']);
        });

        $router->group(['as' => 'banner', 'prefix' => 'banner'], function () use ($router) {
            $router->get('/', ['as' => 'list', 'uses' => 'BannerController@banner']);
            $router->post('/', ['as' => 'store', 'uses' => 'BannerController@store']);
            $router->get('{id}', ['as' => 'show', 'uses' => 'BannerController@show']);
            $router->post('{id}', ['as' => 'update', 'uses' => 'BannerController@update']);
            $router->post('publish/{id}', ['as' => 'publish', 'uses' => 'BannerController@publish']);
        });

        $router->group(['as' => 'moment', 'prefix' => 'moment'], function () use ($router) {
            $router->post('topic', ['as' => 'add_topic', 'uses' => 'MomentController@setTopic']);
            $router->get('topic/tab', ['as' => 'topic_tab', 'uses' => 'MomentController@getTopic']);
            $router->get('topic', ['as' => 'topic', 'uses' => 'MomentController@topicList']);
            $router->post('topic/{uuid}', ['as' => 'change_topic', 'uses' => 'MomentController@changeTopic']);
            $router->get('/', ['as' => 'moment_list', 'uses' => 'MomentController@getMoment']);
            $router->delete('{uuid}', ['as' => 'del_moment', 'uses' => 'MomentController@delMoment']);
            $router->get('report', ['as' => 'report', 'uses' => 'MomentController@reportMoments']);
            $router->post('/report/{uuid}/dismiss', ['as' => 'dismiss', 'uses' => 'MomentController@dismissReport']);
            $router->delete('/report/{uuid}/moment',
                ['as' => 'report_del_moment', 'uses' => 'MomentController@reportMomentDel']);
            $router->post('top/{uuid}', ['as' => 'top_moment', 'uses' => 'MomentController@topMoment']);
            $router->post('top_cancel/{uuid}',
                ['as' => 'top_cancel_moment', 'uses' => 'MomentController@topCancelMoment']);
        });

        $router->group(['as' => 'auth', 'prefix' => 'auth'], function () use ($router) {
            $router->get('option', ['as' => 'option', 'uses' => 'AuthController@getAuthOption']);
            $router->get('{uuid}/option', ['as' => 'user_option', 'uses' => 'AuthController@getUserAuth']);
            $router->get('roles', ['as' => 'roles', 'uses' => 'AuthController@roles']);
            $router->get('user', ['as' => 'user', 'uses' => 'AuthController@users']);
            $router->post('user', ['as' => 'user_store', 'uses' => 'AuthController@setUser']);
            $router->post('option', ['as' => 'option_store', 'uses' => 'AuthController@setOption']);
            $router->post('role', ['as' => 'role_store', 'uses' => 'AuthController@setRole']);
            $router->post('set_auth', ['as' => 'set_auth', 'uses' => 'AuthController@setUserAuth']);
            $router->get('is_need_google', ['as' => 'is_need_google', 'uses' => 'AuthController@isNeedGoogle']);
            $router->get('function_mapping',
                ['as' => 'function_mapping', 'uses' => 'AuthController@getFunctionMapping']);
            $router->post('function_mapping',
                ['as' => 'add_function_mapping', 'uses' => 'AuthController@addOrUpdateFunctionMapping']);
        });

        /** 统计 */
        $router->group(['as' => 'stat', 'prefix' => 'stat'], function () use ($router) {
            $router->get('new-user', ['as' => 'new-user', 'uses' => 'StatController@statDailyNewUser']);
            $router->get('invite', ['as' => 'invite', 'uses' => 'StatController@statDailyInvite']);
            $router->get('trade', ['as' => 'trade', 'uses' => 'StatController@statDailyTrade']);
            $router->get('member', ['as' => 'member', 'uses' => 'StatController@statDailyMember']);
            $router->get('recharge', ['as' => 'recharge', 'uses' => 'StatController@statDailyRecharge']);
            $router->get('consume', ['as' => 'consume', 'uses' => 'StatController@statDailyConsume']);
            $router->get('active', ['as' => 'active', 'uses' => 'StatController@statDailyActive']);
        });

        $router->group(['as' => 'discount', 'prefix' => 'discount'], function () use ($router) {
            $router->get('/', ['as' => 'index', 'uses' => 'DiscountController@index']);
            $router->post('/', ['as' => 'store', 'uses' => 'DiscountController@store']);
            $router->get('{id}', ['as' => 'show', 'uses' => 'DiscountController@show']);
            $router->delete('{id}', ['as' => 'destroy', 'uses' => 'DiscountController@destroy']);
        });

        $router->group(['as' => 'wgc', 'prefix' => 'wgc'], function () use ($router) {
            $router->post('repay', ['as' => 'repay', 'uses' => 'WGCYunPayController@repay']);
            $router->get('repay', ['as' => 'repay_index', 'uses' => 'WGCYunPayController@repayIndex']);
            $router->get('balance', ['as' => 'balance', 'uses' => 'WGCYunPayController@getBalance']);
        });

        $router->group(['as' => 'tag', 'prefix' => 'tag'], function () use ($router) {
            $router->post('report', ['as' => 'set_report_tag', 'uses' => 'TagController@setReportFixTag']);
            $router->get('report', ['as' => 'get_report_tag', 'uses' => 'TagController@getReportFixTag']);
        });

        $router->group(['as' => 'ab', 'prefix' => 'ab'], function () use ($router) {
            $router->get('/', ['as' => 'ab', 'uses' => 'ABTestController@ab']);
            $router->get('detail', ['as' => 'ab_detail', 'uses' => 'ABTestController@abDetail']);
        });

        $router->group(['as' => 'invite', 'prefix' => 'invite'], function () use ($router) {
            $router->get('user', ['as' => 'user', 'uses' => 'InviteController@getInviteUsers']);
            $router->get('{uuid}/users', ['as' => 'invite_detail', 'uses' => 'InviteController@inviteDetail']);
            $router->get('punishment', ['as' => 'punish_list', 'uses' => 'InviteController@punishList']);
            $router->post('{uuid}/punishment', ['as' => 'punishment', 'uses' => 'InviteController@punishment']);
        });

        $router->group(['as' => 'static', 'prefix' => 'static'], function () use ($router) {
            $router->get('msg', ['as' => 'msg', 'uses' => 'StaticController@msgStatic']);
            $router->get('msg/show', ['as' => 'show_msg', 'uses' => 'StaticController@showMsg']);
        });


        $router->group(['as' => 'translate', 'prefix' => 'translate'], function () use ($router) {
            $router->get('/', ['as' => 'list', 'uses' => 'TranslateController@translateList']);
            $router->post('/', ['as' => 'add_translate', 'uses' => 'TranslateController@setTranslate']);
            $router->get('/export', ['as' => 'export', 'uses' => 'TranslateController@export']);
        });
    });
});
