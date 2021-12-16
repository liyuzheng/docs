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
$router->group(['middleware' => 'base'], function () use ($router) {
    $router->get('/', function () use ($router) {
        return response()->json([
            'frame_version' => $router->app->version(),
            'timestamp'     => time()
        ]);
    });
});
/** file文件处理 */
$router->group(['as' => 'file', 'prefix' => 'file'], function () use ($router) {
    /** 上传单张图片 */
    $router->post('single', ['as' => 'single', 'uses' => 'UploadsController@single']);
    /** 上传远程单张图片 */
    $router->post('remote/single', ['as' => 'remoteSingle', 'uses' => 'UploadsController@remoteSingle']);
    /** 获取资源信息 */
    $router->get('/', ['as' => '/', 'uses' => 'UploadsController@fileInfo']);
    /** 获取二维码海报 */
    $router->get('/poster', ['as' => '/', 'uses' => 'UploadsController@qrcodePoster']);
});
$router->group(['middleware' => ['base', 'xss']], function () use ($router) {
    $router->group(['prefix' => 'v1', 'as' => 'api.v1'], function () use ($router) {
        /** 获取es数据 */
        //        $router->get('/es/{type}/{id}', ['as' => 'es_user', 'uses' => 'UserController@getEsDoc']);
        /** mock数据 */
        $router->group(['as' => 'mock', 'prefix' => 'mock'], function () use ($router) {
            $router->get('/', ['as' => 'mock', 'uses' => 'AuthController@mock']);
            $router->post('/', ['as' => 'mock', 'uses' => 'AuthController@mock']);
        });
        /** 认证 */
        $router->group(['as' => 'sms', 'prefix' => 'auth'], function () use ($router) {
            $router->post('/', ['as' => 'store', 'uses' => 'AuthController@auth']);
        });

        /** 设置密码 */
        $router->group(['as' => 'auth', 'prefix' => 'auth', 'middleware' => 'auth'], function () use ($router) {
            $router->post('password', ['as' => 'store', 'uses' => 'AuthController@setPassword']);
        });
        $router->group(['as' => 'auth', 'prefix' => 'auth'], function () use ($router) {
            $router->post('reset_password', ['as' => 'reset_password', 'uses' => 'AuthController@resetPassword']);
            $router->get('mobile_register', ['as' => 'mobile_register', 'uses' => 'AuthController@mobileRegister']);
            $router->get('registered', ['as' => 'registered', 'uses' => 'AuthController@registered']);
        });
        /** 短信 */
        $router->group(['as' => 'emails', 'prefix' => 'emails'], function () use ($router) {
            $router->post('/', ['as' => 'store', 'uses' => 'CodeController@emailStore']);
        });
        /** 短信 */
        $router->group(['as' => 'sms', 'prefix' => 'sms'], function () use ($router) {
            $router->post('/', ['as' => 'store', 'uses' => 'CodeController@smsStore']);
        });
        /** 配置 */
        $router->group(['as' => 'configs', 'prefix' => 'configs'], function ($router) {
            $router->get('orange', ['as' => 'orange', 'uses' => 'ConfigController@orange']);
            $router->get('global', ['as' => 'global', 'middleware' => 'is_audit', 'uses' => 'ConfigController@global']);
            $router->get('update', ['as' => 'update', 'uses' => 'ConfigController@update']);
            $router->group(['middleware' => 'auth'], function ($router) {
                $router->get('job', ['as' => 'job', 'uses' => 'ConfigController@job']);
            });
            $router->get('ban_world', ['as' => 'ban_world', 'uses' => 'ConfigController@banWorld']);
            $router->get('ban_exact_world', ['as' => 'ban_exact_world', 'uses' => 'ConfigController@banExactWorld']);
        });

        /** feed菜单 */
        $router->group(['as' => 'menus', 'prefix' => 'menus'], function ($router) {
            $router->group(['middleware' => 'auth'], function ($router) {
                $router->get('users', ['as' => 'users', 'uses' => 'MenuController@users']);
                $router->get('dots', ['as' => 'users', 'uses' => 'MenuController@dots']);
                $router->get('moments', ['as' => 'moments', 'uses' => 'MenuController@moments']);
                $router->get('lbs', ['as' => 'lbs_menu', 'uses' => 'MenuController@lbsMenu']);
            });
        });
        /** 动态 */
        $router->group(['as' => 'moment', 'prefix' => 'moment'], function ($router) {
            $router->group(['middleware' => 'auth'], function ($router) {
                $router->get('/', ['as' => '/', 'uses' => 'MomentController@index']);
                $router->get('like', ['as' => 'likeList', 'uses' => 'MomentController@likeList']);
                $router->get('followed', ['as' => 'followed', 'uses' => 'MomentController@followed']);
                $router->get('banner', ['as' => 'banner', 'uses' => 'MomentController@banner']);
                $router->get('topic', ['as' => 'topic', 'uses' => 'MomentController@topics']);
                $router->get('topic/{uuid}', ['as' => 'topicDetail', 'uses' => 'MomentController@topicDetail']);
                $router->get('{uuid}', ['as' => 'moment', 'uses' => 'MomentController@moment']);
                $router->delete('{uuid}', ['as' => 'destroy', 'uses' => 'MomentController@destroy']);
                $router->get('user/{uuid}', ['as' => 'userMoments', 'uses' => 'MomentController@userMoments']);
                $router->post('{uuid}/like', ['as' => 'like', 'uses' => 'MomentController@like']);
                $router->post('{uuid}/unlike', ['as' => 'unlike', 'uses' => 'MomentController@unlike']);
                $router->post('/', ['as' => 'store', 'uses' => 'MomentController@momentStore']);
                $router->post('report/{uuid}', ['as' => 'report', 'uses' => 'MomentController@report']);
                $router->get('like/msg', ['as' => 'likeMsg', 'uses' => 'MomentController@likeMsg']);
            });
        });
        /** feed流 */
        $router->group(['as' => 'feed', 'prefix' => 'feed'], function ($router) {
            $router->group(['middleware' => 'auth'], function ($router) {
                $router->get('users', ['as' => 'users', 'uses' => 'FeedController@users']);
                $router->get('greet', ['as' => 'greet_user', 'uses' => 'FeedController@greetUser']);
                $router->post('greet', ['as' => 'greet', 'uses' => 'FeedController@greet']);
            });
            $router->get('web_users', ['as' => 'webUsers', 'uses' => 'FeedController@webUsers']);
        });

        /** 上传 */
        $router->group(['as' => 'uploads', 'prefix' => 'uploads', 'middleware' => 'auth'], function () use ($router) {
            /** 上传单张图片 */
            $router->post('single', ['as' => 'single', 'uses' => 'UploadsController@single']);
        });
        /** 用户 */
        $router->group(['as' => 'users', 'prefix' => 'users'], function ($router) {
            $router->group(['middleware' => 'auth'], function ($router) {
                $router->get('/{uuid}/unlocked', ['as' => 'unlocked', 'uses' => 'TradeBuyController@unlockedUsers']);
                $router->get('/{uuid}/be-unlocked', ['as' => 'unlocked', 'uses' => 'TradeBuyController@beUnlocked']);
                $router->get('{uuid}',
                    ['as' => 'single_user', 'middleware' => 'blocked_verify', 'uses' => 'UserController@singleUser']);
                $router->get('/', ['as' => 'multi_user', 'uses' => 'UserController@multiUser']);
                $router->post('{uuid}/tags', ['as' => 'tags', 'uses' => 'TagController@tags']);
                $router->get('{uuid}/each-powers', ['as' => 'each_powers', 'uses' => 'UserController@eachPowers']);
                $router->get('{uuid}/contact', ['as' => 'contact', 'uses' => 'AccountController@contact']);
                $router->get('{uuid}/member', ['as' => 'member', 'uses' => 'MemberController@member']);
                $router->get('{uuid}/member/renewal-status',
                    ['as' => 'member', 'uses' => 'MemberController@renewalStatus']);
                $router->post('auth/cancel', ['as' => 'user.cancel_auth', 'uses' => 'UserController@cancelAuthUser']);
                $router->post('channel', ['as' => 'update.channel', 'uses' => 'UserController@updateChannel']);
                $router->get('{uuid}/is_look', ['as' => 'is_look', 'uses' => 'UserController@isLook']);
                $router->post('{uuid}/look', ['as' => 'look', 'uses' => 'UserController@look']);
                $router->post('{uuid}/chat', ['as' => 'chat', 'uses' => 'UserController@chat']);
                $router->post('{uuid}/fire', ['as' => 'fire', 'uses' => 'UserController@fire']);
                $router->post('{uuid}/unlock_video',
                    ['as' => 'unlock_video', 'uses' => 'TradeBuyController@unlockVideo']);
                $router->post('{uuid}/hide', ['as' => 'hide', 'uses' => 'UserController@userHide']);
            });
        });

        /** 交易相关 */
        $router->group(['as' => 'trades', 'prefix' => 'trades'], function ($router) {
            $router->get('/{order}/status', ['as' => 'pay.status', 'uses' => 'PaymentController@tradeOrderStatus']);
            $router->post('pingxx/web/pay', ['as' => 'pingxx.web.pay', 'uses' => 'PaymentController@webPingXxPay']);
            $router->group(['middleware' => 'optional_auth'], function ($router) {
                $router->get('goods', ['as' => 'goods', 'uses' => 'PaymentController@goods']);
                $router->get('/native-web/goods', ['as' => 'native_web.goods', 'uses' => 'PaymentController@goods']);
                $router->get('proxy-currency/goods',
                    ['as' => 'proxy_currency.goods', 'uses' => 'PaymentController@proxyCurrencyGoods']);
            });

            $router->get('/users/{uuid}/discount', ['as' => 'users.discount', 'uses' => 'PaymentController@discount']);
            $router->group(['middleware' => 'auth'], function ($router) {
                $router->get('pre', ['as' => 'pre', 'uses' => 'PaymentController@tradePre']);
                $router->post('proxy-currency/pay',
                    ['as' => 'proxy_currency.pay', 'uses' => 'PaymentController@proxyCurrencyPay']);
                $router->post('apple/pay', ['as' => 'apple.pay', 'uses' => 'PaymentController@applePay']);
                $router->post('google/pay', ['as' => 'google.pay', 'uses' => 'PaymentController@googlePay']);
                $router->post('pingxx/pay', ['as' => 'pingxx.pay', 'uses' => 'PaymentController@pingXxPay']);
                $router->post('native-web/pingxx/pay',
                    ['as' => 'native_web.pingxx.pay', 'uses' => 'PaymentController@pingXxPay']);
                $router->get('records', ['as' => 'records', 'uses' => 'PaymentController@records']);
                $router->get('buy/consumes/records',
                    ['as' => 'buy.consumes.records', 'uses' => 'TradeBuyController@consumeRecords']);
                $router->get('buy/incomes/records',
                    ['as' => 'buy.incomes.records', 'uses' => 'TradeBuyController@incomeRecords']);

                $router->group(['as' => 'withdraw', 'prefix' => 'withdraw'], function ($router) {
                    $router->post('income', ['as' => 'income', 'uses' => 'WithdrawController@incomeWithdraw']);
                    $router->get('records', ['as' => 'records', 'uses' => 'WithdrawController@records']);
                    $router->post('invite', ['as' => 'invite', 'uses' => 'WithdrawController@inviteWithdraw']);
                    $router->get('account', ['as' => 'account', 'uses' => 'WithdrawController@account']);
                });
            });

            $router->get('withdraw/region', ['as' => 'withdraw.region', 'uses' => 'WithdrawController@region']);
        });

        /** Tag标签 */
        $router->group(['as' => 'tags', 'prefix' => 'tags'], function () use ($router) {
            /** 获取关系 */
            $router->get('in-relation', ['as' => 'in_relation', 'uses' => 'TagController@inRelation']);
            $router->group(['middleware' => 'auth'], function ($router) {
                $router->get('user-evaluate/{uuid}', ['as' => 'user-evaluate', 'uses' => 'TagController@userEvaluate']);
            });
        });
        /** 举报 */
        $router->group(['as' => 'report', 'prefix' => 'report'], function () use ($router) {
            $router->group(['middleware' => 'auth'], function ($router) {
                $router->post('{uuid}', ['as' => 'report', 'uses' => 'ReportController@report']);
                $router->get('/user', ['as' => 'report_user', 'uses' => 'ReportController@reportTags']);
            });
        });

        /** 反馈 */
        $router->group(['as' => 'feedback', 'prefix' => 'feedback'], function () use ($router) {
            $router->group(['middleware' => ['auth']], function ($router) {
                $router->post('{uuid}', ['as' => 'report', 'uses' => 'ReportController@feedback']);
            });
        });

        /** 零散路由 */
        $router->group(['middleware' => 'auth'], function ($router) {
            $router->get('jobs', ['as' => 'jobs', 'uses' => 'JobController@jobs']);
            $router->post('jobs', ['as' => 'jobsAdd', 'uses' => 'JobController@create']);
            $router->post('follow', ['as' => 'follow', 'uses' => 'FollowController@batchFollow']);
            $router->post('blacklists', ['as' => 'blacklists', 'uses' => 'BlackController@blacklistStore']);
            //        $router->delete('unfollow', ['as' => 'unfollow', 'uses' => 'FollowController@unFollow']);
            $router->delete('unfollow/users/{uuid}', [
                'as'   => 'follow.users.destroy',
                'uses' => 'FollowController@unFollow'
            ]);
            //        $router->delete('blacklists', ['as' => 'del_blacklists', 'uses' => 'UserController@BlackController']);
            $router->delete('blacklists/users/{uuid}', [
                'as'   => 'blacklists.users.destroy',
                'uses' => 'BlackController@destroyBlacklistsUsers'
            ]);
            $router->post('unlock/users/{uuid}',
                ['as' => 'unlock.user', 'middleware' => 'blocked_verify', 'uses' => 'TradeBuyController@unlockUser']);
        });

        /** 获得账户信息 */
        $router->group(['as' => 'accounts', 'prefix' => 'accounts'], function ($router) {
            $router->group(['middleware' => 'auth'], function ($router) {
                $router->get('{uuid}', ['as' => 'wallets', 'uses' => 'AccountController@userInfo']);
                $router->put('{uuid}', ['as' => 'wallets', 'uses' => 'AccountController@userUpdate']);
                $router->post('ios/{uuid}', ['as' => 'wallets', 'uses' => 'AccountController@userUpdate']);
                $router->get('{uuid}/wallets',
                    [
                        'as'         => 'wallets',
                        'middleware' => 'android_income_decimal',
                        'uses'       => 'AccountController@wallet'
                    ]);
                $router->get('{uuid}/switches', ['as' => 'switches', 'uses' => 'AccountController@switches']);
                $router->get('{uuid}/evaluate', ['as' => 'evaluate', 'uses' => 'AccountController@evaluate']);
                $router->get('{uuid}/resource', ['as' => 'resource', 'uses' => 'AccountController@resource']);
                $router->post('{uuid}/destroy', ['as' => 'resource', 'uses' => 'AccountController@destroy']);
                $router->post('{uuid}/activate', ['as' => 'resource', 'uses' => 'AccountController@activate']);
                $router->put('{uuid}/resource', [
                    'as'   => 'updateResource',
                    'uses' => 'AccountController@userResourceUpdate'
                ]);
                $router->post('ios/{uuid}/resource', [
                    'as'   => 'updateResource',
                    'uses' => 'AccountController@userResourceUpdate'
                ]);
                $router->delete('{uuid}/resource', ['as' => 'delResource', 'uses' => 'AccountController@delResource']);
                $router->put('{uuid}/photos', ['as' => 'delResource', 'uses' => 'AccountController@photos']);
                $router->get('{uuid}/blacklists', ['as' => 'blacklists', 'uses' => 'AccountController@blacklist']);
                $router->get('{uuid}/followed', ['as' => 'followed', 'uses' => 'AccountController@followed']);
                $router->get('{uuid}/follow', ['as' => 'follow', 'uses' => 'AccountController@follow']);
                $router->post('{uuid}/mobile-books', ['as' => 'mobileBook', 'uses' => 'AccountController@mobileBook']);
                $router->get('{uuid}/tags', ['as' => 'tags', 'uses' => 'AccountController@tags']);
                $router->put('{uuid}/switches',
                    ['as' => 'updateSwitches', 'uses' => 'AccountController@updateSwitches']);
                $router->get('{uuid}/powers', ['as' => 'powers', 'uses' => 'AccountController@powers']);
                $router->get('{uuid}/face_token', ['as' => 'face_token', 'uses' => 'AccountController@getFaceToken']);
                $router->get('{uuid}/face_result',
                    ['as' => 'face_result', 'uses' => 'AccountController@getFaceResult']);
                $router->post('{uuid}/auth', ['as' => 'auth', 'uses' => 'AccountController@checkStore']);
                $router->get('{uuid}/auth_status', ['as' => 'auth_status', 'uses' => 'AccountController@checkStatus']);
                $router->post('{uuid}/finish_alert',
                    ['as' => 'finish_alert', 'uses' => 'AccountController@finishAlert']);
                $router->post('{uuid}/set_auth', ['as' => 'set_auth', 'uses' => 'AccountController@setAuthUser']);
                $router->post('location', ['as' => 'location', 'uses' => 'AccountController@uploadLocation']);
                $router->post('lock_wechat', ['as' => 'lock_wechat', 'uses' => 'AccountController@lockWechat']);
                $router->post('mobile_shield', ['as' => 'mobile_shield', 'uses' => 'AccountController@mobileShield']);
                $router->post('{uuid}/task_invite_member',
                    ['as' => 'task_invite_member', 'uses' => 'InviteController@taskInviteMember']
                );
                $router->get('{uuid}/invite', ['as' => 'invite', 'uses' => 'InviteController@invite']);
                $router->get('{uuid}/applet-invite',
                    ['as' => 'applet.invite', 'uses' => 'InviteController@appletInvite']);
                $router->get('{uuid}/discount-invite',
                    ['as' => 'applet.invite', 'uses' => 'InviteController@discountInvite']);
                $router->get('{uuid}/invite_users', ['as' => 'invite', 'uses' => 'InviteController@users']);
                $router->post('resource/{uuid}/change_status',
                    ['as' => 'change_status', 'uses' => 'AccountController@changePhotoStatus']
                );
                $router->get('{uuid}/want', ['as' => 'want', 'uses' => 'AccountController@want']);
                //校验是否绑定了公众号
                $router->get('{uuid}/follow_of_state',
                    ['as' => 'follow_of_state', 'uses' => 'AccountController@followOfState']
                );
                $router->get('{uuid}/detail_extra',
                    ['as' => 'detail_extra', 'uses' => 'AccountController@detailExtraTags']);
                $router->get('{uuid}/visited', ['as' => 'visited', 'uses' => 'AccountController@visited']);
                $router->get('{uuid}/popup', ['as' => 'popup', 'uses' => 'AccountController@popup']);
                //修改是否推送公众号模板消息开关
                $router->post('{uuid}/switch_tmp_msg',
                    ['as' => 'switch_tmp_msg', 'uses' => 'AccountController@switchTmpMsg']
                );
                $router->post('{uuid}/active', ['as' => 'user_active', 'uses' => 'AccountController@updateActiveTime']);
                $router->get('{uuid}/refund_locked', [
                    'as'   => 'refund_locked',
                    'uses' => 'AccountController@refundLocked'
                ]);
                $router->post('{uuid}/refund_locked_read', [
                        'as'   => 'refund_locked_read',
                        'uses' => 'AccountController@refundLockedRead'
                    ]
                );
                $router->get('{uuid}/member_operation',
                    ['as' => 'member_operation', 'uses' => 'AccountController@getGIOMemberOperation']);
            });

            $router->post('invite/bind', ['as' => 'invite', 'uses' => 'InviteController@bind']);

            $router->post('photo_compare', ['as' => 'photo_compare', 'uses' => 'AccountController@comparePhoto']);
        });

        /** 用户 */
        $router->group(['as' => 'search', 'prefix' => 'search'], function ($router) {
            $router->get('users', ['as' => 'users', 'uses' => 'UserController@searchUsers']);
        });

        /** web 可被调用的接口 */
        $router->group(['prefix' => 'web', 'as' => 'web'], function ($router) {
            $router->get('users/{uuid}', ['as' => 'users', 'uses' => 'UserController@webSingleUser']);
        });

        /** 统计可被调用的接口 */
        $router->group(['prefix' => 'stat', 'as' => 'stat'], function ($router) {
            $router->post('sms_recall', ['as' => 'sms_recall', 'uses' => 'StatController@smsRecall']);
        });

        /** 聊天相关接口 */
        $router->group(['prefix' => 'chat', 'as' => 'chat'], function ($router) {
            $router->post('girl_first', ['as' => 'girl_first', 'uses' => 'ChatController@checkGirlFirstMsg']);
        });

        /** 推广 */
        $router->group(['prefix' => 'popularize', 'as' => 'popularize', 'middleware' => ['auth']], function ($router) {
            $router->post('is_ocpc_recharge',
                [
                    'as'   => 'is_ocpc_recharge',
                    'uses' => 'PopularizeController@isOcpcRecharge'
                ]
            );
        });
        //探针
        $router->post('manager_record', ['as' => 'manager_record', 'uses' => 'StatController@managerRecord']);
        $router->get('manager_record', ['as' => 'manager_record', 'uses' => 'StatController@managerRecord']);
    });
});
