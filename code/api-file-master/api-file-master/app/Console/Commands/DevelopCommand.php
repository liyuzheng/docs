<?php

namespace App\Console\Commands;


use App\Constant\SpamWorld;
use App\Jobs\CommonQueueMoreByPocketJob;
use App\Jobs\SendWeChatTemplateMsgJob;
use App\Mail\InviteWarnMail;
use App\Mail\VerifyCodeMail;
use App\Models\MemberRecord;
use App\Models\SwitchModel;
use App\Models\User;
use App\Models\UserAb;
use App\Models\UserAuth;
use App\Models\UserDetail;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use App\Models\UserReview;
use App\Models\Wechat;
use Illuminate\Support\Str;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use App\Models\Card;
use App\Models\Resource;
use App\Models\Blacklist;
use App\Models\FacePic;
use App\Foundation\Modules\ResultReturn\ResultReturn;
use Zxing\QrReader;

class DevelopCommand extends Command
{
    protected $signature = 'z_:develop';
    protected $description = '命令集合';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $action = $this->choice('请选择动作类型', [
            'get_netease_user_info'        => '获得云信id',
            'set_auth_user'                => '设置真人认证',
            'set_charm_girl'               => '设置魅力女生认证',
            'refresh_daily_record'         => '更新日常数据',
            'add_biz_id'                   => '补充人脸验证biz_id',
            'push_to_ios_20201205'         => '推送给ios用户紧急',
            'update_audit_user_lbs'        => '更新审核账号看到的用户lbs',
            'replenish_auth_user'          => '补充魅力女生真人认证',
            'replenish_blacklist'          => '补充黑名单',
            'update_user_nim_token'        => '更新用户云信token',
            'update_all_user_nim_token'    => '更新所有用户云信token',
            'update_user_netease_info'     => '更新用户云信信息',
            'replenish_evaluate'           => '补充评价',
            'update_moment_location'       => '更新动态坐标',
            'update_follow_count'          => '更新用户关注数被关注数',
            'insert_spam_word'             => '写入spam_word数据',
            'face_pic_save'                => '将阿里的留底图存在自己的库中',
            'general_wechat_office_qrcode' => '生成老用户公众号二维码生成',
            'set_user_random_attr'         => '生成在线优先的用户',
            'change_moment_user_id'        => '改变动态用户id',
            'clear_redis_look_cache'       => '清理redis看过数据缓存',
            'add_base_user_detail_extra'   => '补充更多资料基础数据',
            'send_test_yuanzhi_msg'        => '发送测试元知消息',
            'get_fake_evaluate_user'       => '获取假评论用户',
            'verify_user_card'             => '验证身份证信息是否争取',
            'add_invite_ab_test'           => '补充用户邀请AB测试数据',
            'refresh_blacklist'            => '补充用户拉黑数据',
            'es_chat_img_count'            => '获取用户某天聊天图片数量',
            'update_spam_status'           => '设置默认值',
            'reset_user_login_log'         => '重制用户活跃',
            'test_es_except_ids'           => '排除id测试',
            'fix_member_record'            => '修复用户所有会员详情数据',
            'unlock_user_netease'          => '修复用户所有会员详情数据',
            'test_post_parse_wechat'       => '测试检测微信图片',
            'clear_women_wechat_trade'            => '修复ab测试字段',
            'add_gold_trade_ab_test'       => '修复30元钻石统计老用户ab数据',
            'fix_ab_test_invite_count'     => '统计B类邀请数量',
            'compensate_member'            => '补偿用户会员天数',
            'get_moment_id'                => '获取moment的doc',
            'test_google_login'            => '测试谷歌登录',
            'decryption_admin_id'          => '解密admin账户',
            'send_netease_to_charm'        => '给魅力女生发消息',
        ]);
        switch ($action) {
            case 'test_google_login':
                $client       = new \Google_Client(['client_id' => '860738669285-7mjl163n7h8j4vsfa6ng9530b6mvbdi8.apps.googleusercontent.com']);
                $guzzleClient = new Client(['proxy' => '47.242.228.103:8888']);
                $packageName  = 'com.l.peipei';
                $productId    = '10';
                $token        = 'ecddcafababpecibckmgklmg.AO-J1OxR03rJIYf7x_LkbpwEDVljnGfISncMdRVZFigxZOtooG6AClqoifom26znbDY3iErKreRvUnRRKIVrWeHmXBRI-NeoTA';
                $client->setHttpClient($guzzleClient);
                $client->setScopes([\Google_Service_AndroidPublisher::ANDROIDPUBLISHER]);
                $client->setAuthConfig(storage_path('secret/service_acccount_credentials.json'));
                $publisher = new \Google_Service_AndroidPublisher($client);
                d($publisher->purchases_products->get($packageName, $productId, $token));
                break;
            case 'test_mail':
                //                Mail::to(['imaxing@126.com', 'axing0204@gmail.com', 'ailuoy@outlook.com'])->send(new VerifyCodeMail(123));
                //                Mail::to(['1020446694@qq.com'])->send(new VerifyCodeMail(123));
                //                Mail::to(['1020446694@qq.com'])->send(new InviteWarnMail(1, 5));
                //                pocket()->common->commonQueueMoreByPocketJob(pocket()->stat, 'incrInviteUserReg',
                //                    [2133, time()]);
                //                Mail::to(['imaxing@126.com', 'axing0204@gmail.com', 'ailuoy@outlook.com'])->queue(new VerifyCodeMail(123));
            case 'compensate_member':
                $userId = $this->ask('请输入用户ID');
                $days   = $this->ask('请输入需要补偿的天数');
                $member = rep()->member->getQuery()->where('user_id', $userId)->first();
                if ($member) {
                    $currentExpiredAt = $member->getRawOriginal('start_at') + $member->getRawOriginal('duration');
                    if ($currentExpiredAt > time()) {
                        DB::transaction(function () use ($userId, $days, $currentExpiredAt, $member) {
                            $seconds = $days * 86400;
                            rep()->member->getQuery()->where('user_id', $userId)->update(
                                ['duration' => DB::raw('duration + ' . $seconds)]);
                            $memberRecordData = [
                                'type'           => MemberRecord::TYPE_BUY,
                                'user_id'        => $userId,
                                'pay_id'         => 0,
                                'first_start_at' => $member->getRawOriginal('start_at'),
                                'duration'       => $seconds,
                                'expired_at'     => $currentExpiredAt + $seconds,
                                'next_cycle_at'  => 0,
                                'status'         => 4,
                            ];
                            rep()->memberRecord->getQuery()->create($memberRecordData);
                        });
                    } else {
                        DB::transaction(function () use ($userId, $days, $member) {
                            $seconds    = $days * 86400;
                            $currentNow = time();
                            rep()->member->getQuery()->where('user_id', $userId)->update(
                                ['duration' => $seconds, 'start_at' => $currentNow]);
                            $memberRecordData = [
                                'type'           => MemberRecord::TYPE_BUY,
                                'user_id'        => $userId,
                                'pay_id'         => 0,
                                'first_start_at' => $currentNow,
                                'duration'       => $seconds,
                                'expired_at'     => $currentNow + $seconds,
                                'next_cycle_at'  => 0,
                                'status'         => 4,
                            ];
                            rep()->memberRecord->getQuery()->create($memberRecordData);
                        });
                    }
                } else {
                    $card = rep()->card->getQuery()->where('level', Card::LEVEL_MONTH)
                        ->where('continuous', 0)->first();
                    DB::transaction(function () use ($userId, $days, $card) {
                        $seconds    = $days * 86400;
                        $currentNow = time();
                        rep()->member->getQuery()->create([
                            'duration' => $seconds,
                            'start_at' => $currentNow,
                            'user_id'  => $userId,
                            'card_id'  => $card->id
                        ]);
                        $memberRecordData = [
                            'type'           => MemberRecord::TYPE_BUY,
                            'user_id'        => $userId,
                            'pay_id'         => 0,
                            'first_start_at' => $currentNow,
                            'duration'       => $seconds,
                            'expired_at'     => $currentNow + $seconds,
                            'next_cycle_at'  => 0,
                            'status'         => 4,
                        ];
                        rep()->memberRecord->getQuery()->create($memberRecordData);
                    });
                }
                $this->info(sprintf('%d 用户的会员补充完成', $userId));

                break;
            case 'unlock_user_netease':
                $unlockUserIds = rep()->blacklist->getQuery()->where('related_type', Blacklist::RELATED_TYPE_OVERALL)
                    ->where('expired_at', '!=', 0)->where('expired_at', '<', time())
                    ->pluck('related_id')->toArray();
                $users         = rep()->user->getQuery()->select('id', 'uuid')->whereIn('id', $unlockUserIds)->get();
                $this->info('总共' . $users->count());
                foreach ($users as $user) {
                    pocket()->netease->userUnblock($user->uuid);
                    $this->info(sprintf('%d用户云信已解封', $user->id));
                }
                break;
            case 'fix_member_record':
                $offset = 0;
                $limit  = 500;

                do {
                    $users = rep()->memberRecord->getQuery()->select('user_id')->groupBy('user_id')
                        ->offset($offset)->limit($limit)->get();
                    foreach ($users as $user) {
                        $this->info(sprintf('开始修改用户%d的会员详情数据', $user->user_id));
                        $records    = rep()->memberRecord->getQuery()->where('user_id', $user->user_id)->get();
                        $preStartAt = $preExpiredAt = 0;
                        foreach ($records as $record) {
                            if ($preStartAt != $record->getRawOriginal('first_start_at')
                                && $record->getRawOriginal('first_start_at') + $record->getRawOriginal('duration')
                                != $record->getRawOriginal('expired_at')) {
                                $preStartAt   = $record->getRawOriginal('first_start_at');
                                $preExpiredAt = $preStartAt + $record->getRawOriginal('duration');
                                $record->update(['expired_at' => $preExpiredAt]);
                            } elseif ($preStartAt == $record->getRawOriginal('first_start_at')
                                && $preExpiredAt + $record->getRawOriginal('duration')
                                != $record->getRawOriginal('expired_at')) {
                                $preExpiredAt += $record->getRawOriginal('duration');
                                $record->update(['expired_at' => $preExpiredAt]);
                            } else {
                                $preStartAt   = $record->getRawOriginal('first_start_at');
                                $preExpiredAt = $record->getRawOriginal('expired_at');
                            }
                        }
                    }

                    $offset += $users->count();
                } while ($users->count() == $limit);

                break;
            case 'reset_user_login_log':
                $userId  = $this->ask('请输入用户ID');
                $addDays = $this->ask('请输入活跃天数');
                $user    = rep()->user->getQuery()->where('id', $userId)->first();
                rep()->statRemainLoginLog->getQuery()->where('user_id', $user->id)
                    ->forceDelete();

                $userRegAt  = $user->getRawOriginal('created_at');
                $startAt    = strtotime('-' . ($addDays + 1) . ' days');
                $insertData = [];
                for ($i = 0; $i < $addDays; $i++) {
                    $insertData[] = [
                        'user_id'     => $user->id,
                        'os'          => 100,
                        'login_at'    => $startAt,
                        'remain_day'  => 1,
                        'register_at' => $userRegAt,
                        'created_at'  => $startAt,
                        'updated_at'  => $startAt
                    ];

                    $startAt += 86400;
                }

                rep()->statRemainLoginLog->getQuery()->insert($insertData);
                $redisKey = sprintf(config('redis_keys.cache.has_remained'), $user->id);
                redis()->client()->exists($redisKey) && redis()->client()->del($redisKey);
                $authRedisKey   = config('redis_keys.auth.user_login_at.key');
                $historyLoginAt = redis()->zscore($authRedisKey, $userId);
                if ($historyLoginAt !== false) {
                    redis()->client()->zRem($authRedisKey, $userId);
                }
                break;
            case 'add_gold_trade_ab_test':
                $todayStartAt = strtotime(date('Y-m-d')) - 86400;
                $userAllIds   = rep()->user->getQuery()->where('gender', User::GENDER_MAN)
                    ->where('active_at', '>=', $todayStartAt)
                    ->where('created_at', '<', $todayStartAt)->pluck('id')->toArray();
                $data         = array_chunk($userAllIds, ceil(count($userAllIds) / 500));
                $timestamps   = ['created_at' => time(), 'updated_at' => time()];
                foreach ($data as $userIds) {
                    $existsUsers      = rep()->userAb->getQuery()->whereIn('user_id', $userIds)
                        ->whereIn('type', [
                            UserAb::TYPE_GOLD_TRADE_TEST_B,
                            UserAb::TYPE_NEW_GOLD_TRADE_TEST_A,
                            UserAb::TYPE_NEW_GOLD_TRADE_TEST_B
                        ])->pluck('user_id')->toArray();
                    $notExistsUserIds = array_values(array_diff($userIds, $existsUsers));
                    $userAbData       = [];
                    for ($i = 0; $i < count($notExistsUserIds); $i++) {
                        $type = ($i + 1) % 2 > 0 ? UserAb::TYPE_NEW_GOLD_TRADE_TEST_A
                            : UserAb::TYPE_NEW_GOLD_TRADE_TEST_B;
                        $this->info(sprintf('绑定%dAB数据', $notExistsUserIds[$i]));
                        $userAbData[] = array_merge($timestamps,
                            ['user_id' => $notExistsUserIds[$i], 'type' => $type]);
                    }

                    rep()->userAb->getQuery()->insert($userAbData);
                }


//                $offset      = 0;
//                $limit       = 500;
//                $startUserId = $this->ask('请输入起始用户ID');
//                $endUserId   = $this->ask('请输入结束用户ID');
//                $timestamps  = ['created_at' => time(), 'updated_at' => time()];
//                do {
//                    $users            = rep()->user->getQuery()->select('id')->where('id', '>=', $startUserId)
//                        ->where('id', '<=', $endUserId)->orderBy('id', 'asc')->offset($offset)
//                        ->limit($limit)->get();
//                    $userIds          = $users->pluck('id')->toArray();
//                    $existsUsers      = rep()->userAb->getQuery()->whereIn('user_id', $userIds)->whereIn('type',
//                        [UserAb::TYPE_GOLD_TRADE_TEST_A, UserAb::TYPE_GOLD_TRADE_TEST_B])
//                        ->pluck('user_id')->toArray();
//                    $notExistsUserIds = array_diff($userIds, $existsUsers);
//                    $userAbData       = [];
//                    foreach ($notExistsUserIds as $notExistsUserId) {
//                        $this->info(sprintf("补充%d用户的test数据", $notExistsUserId));
//                        $userAbData[] = array_merge($timestamps,
//                            ['user_id' => $notExistsUserId, 'type' => UserAb::TYPE_GOLD_TRADE_TEST_A]);
//                    }
//
//                    $userAbData && rep()->userAb->getQuery()->insert($userAbData);
//                    $offset += $users->count();
//                } while ($users->count() == $limit);

                break;
            case 'clear_redis_look_cache':
                $offset       = 0;
                $limit        = 1000;
                $todayStartAt = strtotime(date('Y-m-d'));
                $startUserId  = $this->ask('请输入起始用户ID');
                $endUserId    = $this->ask('请输入结束用户ID');

                do {
                    $users  = rep()->user->getQuery()->select('id', 'active_at')->withTrashed()
                        ->where('id', '>=', $startUserId)->where('id', '<=', $endUserId)->orderBy('id', 'asc')
                        ->offset($offset)->limit($limit)->get();
                    $offset += $users->count();

                    foreach ($users as $user) {
                        $redisKey = sprintf(config('redis_keys.is_look.key'), $user->id);
                        if ($user->getRawOriginal('active_at') < $todayStartAt && redis()->exists($redisKey)) {
                            $this->info(sprintf('删除%d用户的 look 数据', $user->id));
                            redis()->del($redisKey);
                        }
                    }
                } while ($users->count() == $limit);
                break;
            case 'update_user_netease_info':
                $startUserId = $this->ask('请输入起始用户ID');
                $endUserId   = $this->ask('请输入结束用户ID');
                $offset      = 0;
                $limit       = 500;

                do {
                    $this->info(sprintf('开始ID % d -%d用户的云信数据更新', $offset + 1, $limit + $offset));
                    $users = rep()->user->getQuery()->select('user . id', 'user . uuid', 'user . nickname',
                        'resource . resource')->join('resource', 'user . id', 'resource . related_id')
                        ->where('user . id', ' >= ', $startUserId)->where('user . id', ' <= ', $endUserId)
                        ->where('resource . related_type', 100)->where('resource . deleted_at', 0)
                        ->offset($offset)->limit($limit)->get();
                    foreach ($users as $user) {
                        try {
                            pocket()->netease->userUpdateUinfo($user->uuid, $user->nickname, cdn_url($user->resource));
                        } catch (\Exception $e) {
                            $this->error(sprintf("ID: %d用户更新失败 %s", $user->id, $e->getMessage()));
                        }
                    }

                    $offset += $users->count();
                } while ($users->count() == $limit);
                break;
            case 'get_netease_user_info':
                $uuid     = $this->ask('请输入用户 uuid');
                $response = pocket()->netease->userGetUinfos([$uuid]);
                dd($response->getData());
                break;
            case 'set_auth_user':
                $uuid = $this->ask('请输入用户 uuid');
                $user = rep()->user->getByUUid($uuid);
                rep()->user->m()->where('uuid', $uuid)->update(['role' => 'user,auth_user']);
                $authUser = rep()->role->m()->where('key', Role::KEY_AUTH_USER)->first();
                $authData = [
                    'user_id' => $user->id,
                    'role_id' => $authUser->id
                ];
                $auth     = rep()->userRole->m()->where('user_id', $user->id)->where('role_id', $authUser->id)->first();
                if (!$auth) {
                    rep()->userRole->m()->create($authData);
                }
                dd('修改成功');
                break;
            case 'set_charm_girl':
                $uuid       = $this->ask('请输入用户 uuid');
                $user       = rep()->user->getByUUid($uuid);
                $userDetail = rep()->userDetail->m()->where('user_id', $user->id)->first();
                rep()->user->m()->where('uuid', $uuid)->update(['role' => 'user,auth_user,charm_girl']);
                $authUser   = rep()->role->m()->where('key', Role::KEY_AUTH_USER)->first();
                $charmGirl  = rep()->role->m()->where('key', Role::KEY_CHARM_GIRL)->first();
                $auth       = rep()->userRole->m()->where('user_id', $user->id)->where('role_id',
                    $authUser->id)->first();
                $charm      = rep()->userRole->m()->where('user_id', $user->id)->where('role_id',
                    $charmGirl->id)->first();
                $authData   = [
                    'user_id' => $user->id,
                    'role_id' => $authUser->id
                ];
                $charmData  = [
                    'user_id' => $user->id,
                    'role_id' => $charmGirl->id
                ];
                $reviewData = [
                    'user_id'      => $user->id,
                    'nickname'     => $user->nickname,
                    'birthday'     => $user->birthday,
                    'region'       => $userDetail->region,
                    'height'       => $userDetail->height,
                    'weight'       => $userDetail->weight,
                    'job'          => 0,
                    'intro'        => $userDetail->intro,
                    'check_status' => UserReview::CHECK_STATUS_PASS
                ];
                $wechatData = [
                    'user_id'      => $user->id,
                    'wechat'       => strtolower(Str::random(6)),
                    'qr_code'      => '',
                    'check_status' => Wechat::STATUS_PASS
                ];
                if (!$auth) {
                    rep()->userRole->m()->create($authData);
                }
                if (!$charm) {
                    rep()->userRole->m()->create($charmData);
                }
                rep()->userReview->m()->create($reviewData);
                rep()->wechat->m()->create($wechatData);
                dd('修改成功');
                break;
            case 'refresh_daily_record':
                $dateList = [
                    '2021-05-16',
                    //                    '2021-12-14',
                    //                    '2021-12-13',
                    //                    '2021-12-12',
                    //                    '2021-12-11',
                    //                    '2021-12-10',
                    //                    '2021-12-09',
                    //                    '2021-12-08',
                    //                    '2021-12-07',
                    //                    '2021-12-06',
                    //                    '2021-12-05',
                    //                    '2021-12-04',
                    //                    '2021-12-03',
                    //                    '2021-12-02',
                    //                    '2021-12-01',
                    //                    '2021-12-31',
                    //                    '2021-11-30',
                    //                    '2021-11-29',
                    //                    '2021-11-28',
                    //                    '2021-11-27',
                    //                    '2021-11-26',
                    //                    '2021-11-25',
                    //                    '2021-11-24',
                    //                    '2021-11-23',
                    //                    '2021-11-22',
                    //                    '2021-11-21',
                    //                    '2021-11-20',
                    //                    '2021-11-19',
                    //                    '2021-11-18',
                    //                    '2021-12-17',
                    //                    '2021-12-16',
                ];
                $type     = $this->ask('请输入模式');
                foreach ($dateList as $startDate) {
                    $startTime        = strtotime($startDate);
                    $endTime          = $startTime + 86399;
                    $activeCount      = rep()->statRemainLoginLog->m()
                        ->whereBetween('login_at', [$startTime, $endTime])
                        ->count([DB::raw('distinct(user_id)')]);
                    $roles            = pocket()->role->getUserRoleArr(['charm_girl']);
                    $activeCharmCount = rep()->statRemainLoginLog->m()
                        ->join('user', 'user.id', '=', 'stat_remain_login_log.user_id')
                        ->whereBetween('stat_remain_login_log.login_at', [$startTime, $endTime])
                        ->whereIn('user.role', $roles)
                        ->count([DB::raw('distinct(user_id)')]);

                    $groupActiveCharmCount = rep()->statRemainLoginLog->m()
                        ->select(['user_detail.os', DB::raw('count(*) as count')])
                        ->join('user', 'user.id', '=', 'stat_remain_login_log.user_id')
                        ->whereBetween('stat_remain_login_log.login_at', [$startTime, $endTime])
                        ->join('user_detail', 'user.id', '=', 'user_detail.user_id')
                        ->whereIn('user.role', $roles)
                        ->groupBy('user_detail.os')
                        ->get();
                    $androidActiveCharm    = 0;
                    $iosActiveCharm        = 0;
                    foreach ($groupActiveCharmCount as $item) {
                        if ($item->os == 'android') {
                            $androidActiveCharm = $item->count;
                        }
                        if ($item->os == 'ios') {
                            $iosActiveCharm = $item->count;
                        }
                    }

                    $createData = [
                        'active'               => $activeCount,
                        'charm_active'         => $activeCharmCount,
                        'android_charm_active' => $androidActiveCharm,
                        'ios_charm_active'     => $iosActiveCharm,
                    ];
                    if ($type == 1) {
                        d($createData);
                    } elseif ($type == 2) {
                        $status = rep()->dailyRecord->m()->where('date',
                            date('Y-m-d H:i:s', $startTime))->update($createData);
                        if ($status) {
                            echo $startDate . "更新成功" . PHP_EOL;
                        }
                    }
                }
                dd('更新成功');
                break;
            case 'add_biz_id':
                $allIds = rep()->faceRecord->m()->select(['user_id'])->get();
                foreach ($allIds as $all) {
                    $userId    = $all->user_id;
                    $resources = DB::table('resource')
                        ->where('related_type', Resource::RELATED_TYPE_USER_AVATAR)
                        ->where('related_id', $userId)
                        ->get();
                    foreach ($resources as $resource) {
                        $uuid         = $resource->uuid;
                        $response     = pocket()->aliYun->getAuthResponse('result', $uuid);
                        $data         = $response->getData();
                        $verifyStatus = $data['VerifyStatus'];
                        if ($verifyStatus != 1) {
                            continue;
                        } else {
                            rep()->faceRecord->m()->where('user_id', $userId)->update(['biz_id' => $uuid]);
                            break;
                        }
                    }
                }
                dd('补充成功');
            case 'push_to_ios_20201205':
                $msg     = '重要！！！由于近期用户量急增，为了给您带来更好的体验，今日早上服务器进行升级优化，充值功能暂时关闭。';
                $userIds = rep()->userDetail->m()->where('os', 'ios')->pluck('user_id')->toArray();
                //                $chunkArr = array_chunk($userIds, 499);
                $sender = config('custom . little_helper_uuid');
                $users  = rep()->user->m()->whereIn('id', $userIds)->get();
                $this->info('count: ' . count($userIds));
                foreach ($users as $user) {
                    $response = pocket()->netease->msgSendMsg($sender, $user->uuid, $msg);
                    $this->info(get_command_output_date() . ' user_id: ' . $user->id . ' response:' . json_encode($response->getData()));
                }
                break;
            case 'update_audit_user_lbs':
                while (true) {
                    $userId = $this->ask('请输入用户ID');
                    $lng    = $this->ask('lng');
                    $lat    = $this->ask('lat');
                    $user   = rep()->user->getById($userId);
                    if (!$userId) {
                        $this->error('用户ID错误');
                        break;
                    }
                    pocket()->account->updateLocation($user->id, $lng, $lat);
                    $this->info('update succeed');
                }
                break;
            case 'replenish_auth_user':
                $girls = rep()->user->m()->where('role', 'charm_girl,user')->get();
                foreach ($girls as $girl) {
                    echo $girl->uuid . '开始检测' . PHP_EOL;
                    $userId = $girl->id;
                    $record = rep()->faceRecord->m()->where('user_id', $userId)->where('biz_id', ' != ', 0)->first();
                    if (!$record) {
                        echo $girl->uuid . '没有认证ID，已跳过' . PHP_EOL;
                        continue;
                    }
                    $result = pocket()->aliYun->getAuthResponse('result', $record->biz_id);
                    if ($result->getStatus() == false) {
                        echo $girl->uuid . '获取认证结果失败，已跳过' . PHP_EOL;
                        continue;
                    }
                    $data         = $result->getData();
                    $authUserRole = rep()->role->m()->where('key', Role::KEY_AUTH_USER)->first();
                    if (key_exists('VerifyStatus', $data) && $data['VerifyStatus'] == 1) {
                        $girl->update(['role' => 'auth_user,charm_girl,user']);
                        $data = [
                            'user_id' => $girl->id,
                            'role_id' => $authUserRole->id
                        ];
                        rep()->userRole->m()->create($data);
                        echo $girl->uuid . '角色已补充' . PHP_EOL;
                        continue;
                    } else {
                        echo $girl->uuid . '认证不通过，已跳过' . PHP_EOL;
                        continue;
                    }
                }
                break;
            case 'replenish_blacklist':
                $blackClient = rep()->blacklist->m()
                    ->where('related_type', Blacklist::RELATED_TYPE_CLIENT)
                    ->get();
                $redisKey    = config('redis_keys.blacklist.client.key');
                redis()->client()->del($redisKey);
                foreach ($blackClient as $item) {
                    redis()->client()->zAdd($redisKey, $item->expired_at, $item->related_id);
                }
                dd('补充完成');
                break;
            case 'update_user_nim_token':
                $uuid = $this->ask('请输入用户 uuid');
                $user = rep()->user->getByUUid($uuid);
                if (!$user) {
                    $this->error('用户不存在');

                    return;
                }
                $newToken = md5(Str::random(32));
                $oldToken = pocket()->userAuth->getNeteaseTokenByUserId($user->id);
                $this->info('oldToken:  ' . $oldToken . ' newToken: ' . $newToken);
                $dbResponse = rep()->userAuth->m()
                    ->where('user_id', $user->id)
                    ->where('type', UserAuth::TYPE_NETEASE_TOKEN)->update([
                        'secret' => $newToken
                    ]);
                if ($dbResponse) {
                    $this->info('更新数据库成功');
                    $response = pocket()->netease->userUpdate($uuid, $newToken);
                    if ($response->getStatus()) {
                        $this->info('更新云信成功: ' . json_encode($response->getData()));
                    }
                }
                break;
            case 'update_all_user_nim_token':
                $users = rep()->user->getQuery()->get();
                foreach ($users as $user) {
                    $newToken = md5(Str::random(32));
                    $oldToken = pocket()->userAuth->getNeteaseTokenByUserId($user->id);
                    $this->info('oldToken:  ' . $oldToken . ' newToken: ' . $newToken);
                    $dbResponse = rep()->userAuth->m()
                        ->where('user_id', $user->id)
                        ->where('type', UserAuth::TYPE_NETEASE_TOKEN)->update([
                            'secret' => $newToken
                        ]);
                    if ($dbResponse) {
                        $this->info('更新数据库成功');
                        $response = pocket()->netease->userUpdate($user->uuid, $newToken);
                        if ($response->getStatus()) {
                            $this->info('更新云信成功: ' . json_encode($response->getData()));
                        }
                    }
                }

                break;
            case 'replenish_evaluate':
                $now              = time();
                $fakeGradeMapping = [
                    0.5 => [
                        'min' => 3.0,
                        'max' => 3.2
                    ],
                    1.0 => [
                        'min' => 3.2,
                        'max' => 3.4
                    ],
                    1.5 => [
                        'min' => 3.4,
                        'max' => 3.6
                    ],
                    2.0 => [
                        'min' => 3.6,
                        'max' => 3.8
                    ],
                    2.5 => [
                        'min' => 3.8,
                        'max' => 4.0
                    ],
                    3.0 => [
                        'min' => 4.0,
                        'max' => 4.2
                    ],
                    3.5 => [
                        'min' => 4.2,
                        'max' => 4.4
                    ],
                    4.0 => [
                        'min' => 4.4,
                        'max' => 4.6
                    ],
                    4.5 => [
                        'min' => 4.6,
                        'max' => 4.8
                    ],
                    5.0 => [
                        'min' => 5.0,
                        'max' => 5.0
                    ]
                ];
                $fakeEvaluateUser = explode(',', config('custom.fake_evaluate_user'));
                $evaluates        = rep()->userEvaluate->m()
                    ->select('target_user_id', 'tag_id', DB::raw('AVG(star) as star'), DB::raw('count(*) as cnt'))
                    ->where('star', '<', 3)
                    ->groupBy('target_user_id', 'tag_id')
                    ->get();
                $needReplenish    = [];
                foreach ($evaluates as $evaluate) {
                    $needReplenish[$evaluate->target_user_id][] = [
                        'tag_id'     => $evaluate->tag_id,
                        'grade'      => pocket()->account->getShowStar($evaluate->star),
                        'real_grade' => floatval($evaluate->star),
                        'cnt'        => $evaluate->cnt
                    ];
                }
                foreach ($needReplenish as $key => $value) {
                    foreach ($value as $item) {
                        $existUser = rep()->userEvaluate->m()
                            ->where('target_user_id', $key)
                            ->where('tag_id', $item['tag_id'])
                            ->whereIn('user_id', $fakeEvaluateUser)
                            ->get();
                        $fakeUsers = array_diff($fakeEvaluateUser, $existUser->pluck('user_id')->toArray());
                        if (count($fakeUsers) == 0) {
                            echo "假评论用户不足" . PHP_EOL;
                            continue;
                        }
                        $count = $item['cnt'];
                        $grade = $item['real_grade'] * $item['cnt'];
                        if ($item['grade'] == 5) {
                            continue;
                        }
                        $fakeGrade = 5;
                        while (true) {
                            if (count($fakeUsers) == 0) {
                                echo "假用户不足" . PHP_EOL;
                                break;
                            }
                            if ((($grade + $fakeGrade) / ($count + 1)) <= $fakeGradeMapping[$item['grade']]['max']) {
                                $count++;
                                $grade      += $fakeGrade;
                                $addUserKey = array_rand($fakeUsers);
                                rep()->userEvaluate->m()->create([
                                    'uuid'           => pocket()->util->getSnowflakeId(),
                                    'user_id'        => $fakeUsers[$addUserKey],
                                    'target_user_id' => $key,
                                    'tag_id'         => $item['tag_id'],
                                    'star'           => $fakeGrade
                                ]);
                                echo '用户ID：' . $key . '使用' . $fakeUsers[$addUserKey] . '向tagID:' . $item['tag_id'] . '添加' . $fakeGrade . '分' . PHP_EOL;
                                unset($fakeUsers[$addUserKey]);
                            } else {
                                $fakeGrade -= 0.5;
                            }
                            if ($grade / $count > $fakeGradeMapping[$item['grade']]['min']) {
                                break;
                            }
                        }
                        unset($existUser);
                    }
                    echo $key . "添加成功" . PHP_EOL;
                }
                break;
            case
            'update_moment_location':
                $moments = rep()->moment->getQuery()->get();
                foreach ($moments as $moment) {
                    $this->info('title: ' . $moment->content);
                    $response = pocket()->esMoment->updateMomentFieldToEs(
                        $moment->id,
                        [
                            'location' => [
                                'lat' => (float)$moment->lat,
                                'lon' => (float)$moment->lng
                            ]
                        ]
                    );
                    d($response);
                }
                break;
            case 'update_follow_count':
                $startId = $this->ask('请输入起始ID');
                $endId   = $this->ask('请输入结束ID');
                $k       = $startId;
                while ($k < $endId) {
                    $status = DB::transaction(function () use ($k) {
                        $userDetail = rep()->userDetail->m()
                            ->where('user_id', $k)
                            ->lockForUpdate()
                            ->first();
                        if (!$userDetail) {
                            return false;
                        }
                        $userFollow   = rep()->userFollow->m()
                            ->where('user_id', $userDetail->user_id)
                            ->count();
                        $userFollowed = rep()->userFollow->m()
                            ->select([DB::raw('min(id) as id'), 'user_id', 'follow_id', DB::raw('count(*) as count')])
                            ->where('follow_id', $userDetail->user_id)
                            ->groupBy(['user_id', 'follow_id'])
                            ->get();
                        rep()->userFollow->m()
                            ->whereIn('user_id', $userFollowed->pluck('user_id')->toArray())
                            ->whereNotIn('id', $userFollowed->pluck('id')->toArray())
                            ->whereIn('follow_id', $userFollowed->pluck('follow_id')->toArray())
                            ->delete();
                        $userDetail->where('user_id', $userDetail->user_id)->update([
                            'follow_count'   => $userFollow,
                            'followed_count' => count($userFollowed)
                        ]);

                        echo $userDetail->user_id . '补充成功,关注数：' . $userFollow . ',被关注数：' . count($userFollowed) . PHP_EOL;

                        unset($userDetail);
                        unset($userFollow);
                        unset($userFollowed);

                        return true;
                    });
                    if ($status) {
                        $k++;
                    } else {
                        $k++;
                        echo $k . "用户ID 不存在" . PHP_EOL;
                    }
                }
                echo "更新完成" . PHP_EOL;

            case 'insert_spam_word':
                $words = (new SpamWorld())->momo();
                foreach ($words as $word) {
                    $spamWord = rep()->spamWord->getQuery()->create([
                        'version' => 1,
                        'word'    => $word
                    ]);
                    $this->info(get_command_output_date() . ' id: ' . $spamWord->id);
                }
                break;
            case 'face_pic_save':
                $list = rep()->faceRecord->m()
                    ->where('biz_id', '!=', 0)
                    ->get();
                foreach ($list as $item) {
                    $result = pocket()->aliYun->getAuthResponse('result', $item->biz_id);
                    $data   = $result->getData();
                    if ($data && $data['VerifyStatus'] == 1) {
                        $data = pocket()->account->uploadFaceAuth($data['Material']['FaceImageUrl']);
                        if ($data->getStatus() == false) {
                            echo $item->user_id . " 上传底图失败" . PHP_EOL;
                            continue;
                        }
                        $picData = [
                            'user_id'  => $item->user_id,
                            'base_map' => $data->getData()->data->resource,
                            'status'   => FacePic::STATUS_PASS
                        ];
                        rep()->facePic->m()->create($picData);
                    }
                    echo $item->user_id . " 补充底图成功" . PHP_EOL;
                }

            case 'general_wechat_office_qrcode':
                $startUserId = $this->ask('请输入起始用户ID');
                $endUserId   = $this->ask('请输入结束用户ID');
                for ($i = $startUserId; $i <= $endUserId; $i++) {
                    $user = rep()->user->getById($i);
                    if (!$user) {
                        continue;
                    }
                    $isHas                  = 1;
                    $weChatOfficeFollowResp = pocket()->userFollowOffice->getWeChatOfficeFollowArr($i);
                    if (!$weChatOfficeFollowResp->getStatus()) {
                        pocket()->common->commonQueueMoreByPocketJob(
                            pocket()->wechat,
                            'getFollowOfficeQrCode',
                            [$i]
                        );
                        $isHas = 0;
                    }
                    $this->info(get_command_output_date() . ' user_id: ' . $i . ' is_has: ' . $isHas);
                }
                break;
            case 'change_moment_user_id':
                $momentId  = $this->ask('请输入动态ID');
                $userId    = $this->ask('请输入用户ID');
                $location  = $this->ask('请输入经纬度');
                $city      = $this->ask('请输入城市');
                $createdAt = $this->ask('请输入创建时间');
                $moment    = rep()->moment->getById($momentId);
                $this->info('id: ' . $moment->id . ' user_id: ' . $moment->user_id . ' content: ' . $moment->content);
                rep()->moment->getQuery()->where('id', $momentId)->update([
                    'user_id'    => $userId,
                    'city'       => $city,
                    'created_at' => $createdAt
                ]);
                pocket()->esMoment->updateMomentFieldToEs(
                    $momentId,
                    ['user_id' => $userId, 'created_at' => $createdAt]
                );
                $locationArr = explode(',', $location);
                $lat         = $locationArr[0];
                $lng         = $locationArr[1];
                if ($lat && $lng) {
                    rep()->moment->getQuery()->where('id', $momentId)->update(['lat' => $lat, 'lng' => $lng]);
                    pocket()->esMoment->updateMomentFieldToEs($momentId, [
                        'location' => [
                            'lat' => (float)$locationArr[0],
                            'lon' => (float)$locationArr[1],
                        ],
                    ]);
                }
                break;

            case 'set_user_random_attr':
                //                dd(get_distance(39.99752,  116.482599, 40.074571, 117.450615));
                //                $response = pocket()->esUser->updateUserFieldToEs(2065,[
                //                    'location'  => [
                //                        'lat' => (float)40.074571,
                //                        'lon' => (float)117.450615,
                //                    ],
                //                ]);
                //                dd($response);
                //117.450615,40.074571
                $userIds = [
                    1 => [
                        0 => [
                            'user_id' => 197,
                            'lat'     => 40.00758037669034,
                            'lng'     => 116.55068369827377,
                        ],
                        1 => [
                            'user_id' => 73,
                            'lat'     => 39.92677,
                            'lng'     => 116.61608
                        ],
                        2 => [
                            'user_id' => 2051,
                            'lat'     => 39.866602,
                            'lng'     => 116.453165,
                        ],
                        //                        3 => [
                        //                            'user_id' => 101,
                        //                            'lat'     => 39.8271882,
                        //                            'lng'     => 116.330147
                        //                        ],
                        //                        4 => [
                        //                            'user_id' => 2059,
                        //                            'lat'     => 39.8261882,
                        //                            'lng'     => 116.320147
                        //                        ],
                        //                        5 => [
                        //                            'user_id' => 141,
                        //                            'lat'     => 39.8251882,
                        //                            'lng'     => 116.310147
                        //                        ]
                    ],
                    2 => [
                        6 => [
                            'user_id' => 98,
                            'lat'     => 39.99752,
                            'lng'     => 116.482599
                        ],
                        7 => [
                            'user_id' => 89,
                            'lat'     => 39.997443,
                            'lng'     => 116.482684
                        ],
                        8 => [
                            'user_id' => 113,
                            'lat'     => 39.994135,
                            'lng'     => 116.484515
                        ],
                        //                        9  => [
                        //                            'user_id' => 119,
                        //                            'lat'     => 39.996661,
                        //                            'lng'     => 116.477864
                        //                        ],
                        //                        10 => [
                        //                            'user_id' => 23,
                        //                            'lat'     => 39.996712,
                        //                            'lng'     => 116.477416
                        //                        ],
                        //                        11 => [
                        //                            'user_id' => 156,
                        //                            'lat'     => 39.99661,
                        //                            'lng'     => 116.477173
                        //                        ],
                        //                        12 => [
                        //                            'user_id' => 12,
                        //                            'lat'     => 40.0051,
                        //                            'lng'     => 116.474374
                        //                        ],
                        //                        13 => [
                        //                            'user_id' => 163,
                        //                            'lat'     => 40.004714252670404,
                        //                            'lng'     => 116.50192890723528
                        //                        ],
                        //                        14 => [
                        //                            'user_id' => 69,
                        //                            'lat'     => 39.99297917806841,
                        //                            'lng'     => 116.47967117374915
                        //                        ],
                        //                        15 => [
                        //                            'user_id' => 28,
                        //                            'lat'     => 39.99298316642984,
                        //                            'lng'     => 116.4796384363079
                        //                        ],
                        //                        16 => [
                        //                            'user_id' => 5,
                        //                            'lat'     => 39.99658203125,
                        //                            'lng'     => 116.47401570417111
                        //                        ],
                        //                        17 => [
                        //                            'user_id' => 6,
                        //                            'lat'     => 39.99279949360036,
                        //                            'lng'     => 116.47816480495504
                        //                        ],
                        //                        18 => [
                        //                            'user_id' => 134,
                        //                            'lat'     => 40.00162892344757,
                        //                            'lng'     => 116.47182415805072
                        //                        ],
                        //                        19 => [
                        //                            'user_id' => 66,
                        //                            'lat'     => 40.002699,
                        //                            'lng'     => 116.468345
                        //                        ],
                        //                        20 => [
                        //                            'user_id' => 2058,
                        //                            'lat'     => 39.995563026373055,
                        //                            'lng'     => 116.47043932913203
                        //                        ],
                        //                        21 => [
                        //                            'user_id' => 86,
                        //                            'lat'     => 39.994607,
                        //                            'lng'     => 116.469854
                        //                        ],
                        //                        22 => [
                        //                            'user_id' => 105,
                        //                            'lat'     => 39.98907470703125,
                        //                            'lng'     => 116.47648775403385
                        //                        ],
                        //                        23 => [
                        //                            'user_id' => 2033,
                        //                            'lat'     => 39.98780563650435,
                        //                            'lng'     => 116.4767325993601
                        //                        ],
                        //                        24 => [
                        //                            'user_id' => 95,
                        //                            'lat'     => 39.996596,
                        //                            'lng'     => 116.466975
                        //                        ],
                        //                        25 => [
                        //                            'user_id' => 194,
                        //                            'lat'     => 39.9963,
                        //                            'lng'     => 116.465972
                        //                        ],
                        //                        26 => [
                        //                            'user_id' => 2018,
                        //                            'lat'     => 39.9964,
                        //                            'lng'     => 116.465982
                        //                        ],
                        //                        27 => [
                        //                            'user_id' => 158,
                        //                            'lat'     => 39.9965,
                        //                            'lng'     => 116.465983
                        //                        ]
                    ],
                    3 => [
                        34 => [
                            'user_id' => 196,
                            "lat"     => 39.9366333,
                            "lng"     => 116.26935
                        ],
                        35 => [
                            'user_id' => 35,
                            "lat"     => 39.839799,
                            "lng"     => 116.385688
                        ],
                        //                        36 => [
                        //                            'user_id' => 2036,
                        //                            "lat"     => 39.882351792234516,
                        //                            "lon"     => 116.30992108114063
                        //                        ],
                        //                        37 => [
                        //                            'user_id' => 71,
                        //                            "lat"     => 39.894656,
                        //                            "lon"     => 116.678987
                        //                        ],
                        //                        38 => [
                        //                            'user_id' => 128,
                        //                            "lat"     => 39.8974609375,
                        //                            "lon"     => 116.68178124374246
                        //                        ],
                        //                        39 => [
                        //                            'user_id' => 24,
                        //                            "lat"     => 40.11504043311833,
                        //                            "lon"     => 116.29963700783723
                        //                        ]
                    ],
                    4 => [
                        44 => [
                            'user_id' => 1,
                            "lat"     => 39.99752,
                            "lng"     => 116.482599
                        ],
                        45 => [
                            'user_id' => 114,
                            "lat"     => 39.997443,
                            "lng"     => 116.482684
                        ],
                        //                        46 => [
                        //                            'user_id' => 2073,
                        //                            "lat"     => 39.994135,
                        //                            "lng"     => 116.484515
                        //
                        //                        ],
                        //                        47 => [
                        //                            'user_id' => 135,
                        //                            "lat"     => 39.996661,
                        //                            "lng"     => 116.477864
                        //                        ],
                        //                        48 => [
                        //                            'user_id' => 2011,
                        //                            "lat"     => 39.996712,
                        //                            "lng"     => 116.477416
                        //                        ],
                        //                        49 => [
                        //                            'user_id' => 103,
                        //                            "lat"     => 39.99661,
                        //                            "lng"     => 116.477173
                        //                        ],
                        //                        50 => [
                        //                            'user_id' => 2020,
                        //                            "lat"     => 40.0051,
                        //                            "lng"     => 116.474374
                        //                        ],
                        //                        51 => [
                        //                            'user_id' => 50,
                        //                            "lat"     => 40.004714252670404,
                        //                            "lng"     => 116.50192890723528
                        //                        ],
                        //                        52 => [
                        //                            'user_id' => 177,
                        //                            "lat"     => 39.99297917806841,
                        //                        ],
                        //                        53 => [
                        //                            'user_id' => 2004,
                        //                            "lat"     => 39.99298316642984,
                        //                            "lng"     => 116.4796384363079
                        //                        ],
                        //                        54 => [
                        //                            'user_id' => 13,
                        //                            "lat"     => 39.99658203125,
                        //                            "lng"     => 116.47401570417111
                        //                        ],
                        //                        55 => [
                        //                            'user_id' => 36,
                        //                            "lat"     => 39.99279949360036,
                        //                            "lng"     => 116.47816480495504
                        //                        ],
                        //                        56 => [
                        //                            'user_id' => 57,
                        //                            "lat"     => 40.00162892344757,
                        //                            "lng"     => 116.47182415805072
                        //
                        //                        ],
                    ],
                    5 => [
                        57 => [
                            'user_id' => 200,
                            "lat"     => 40.002699,
                            "lng"     => 116.468345
                        ],
                        58 => [
                            'user_id' => 2043,
                            "lat"     => 39.995563026373055,
                            "lng"     => 116.47043932913203
                        ],
                        59 => [
                            'user_id' => 2006,
                            "lat"     => 39.994607,
                            "lng"     => 116.469854
                        ],
                        60 => [
                            'user_id' => 59,
                            "lat"     => 39.98907470703125,
                            "lng"     => 116.47648775403385
                        ],
                        61 => [
                            'user_id' => 130,
                            "lat"     => 36.327689429320705,
                            "lng"     => 114.37921910104871
                        ],
                        62 => [
                            'user_id' => 112,
                            "lat"     => 40.872956956101736,
                            "lng"     => 111.5031602005978
                        ],
                        63 => [
                            'user_id' => 132,
                            "lat"     => 36.227689429320705,
                            "lng"     => 114.47921910104871
                        ],
                        64 => [
                            'user_id' => 26,
                            "lat"     => 40.82956956101736,
                            "lng"     => 111.6031602005978
                        ]
                    ]
                ];
                $times   = [
                    1 => [
                        'start_active' => time() - (10 * 60),
                        'end_active'   => time(),
                    ],
                    2 => [
                        'start_active' => time() - (60 * 60),
                        'end_active'   => time() - (10 * 60),
                    ],
                    3 => [
                        'start_active' => time() - (60 * 60),
                        'end_active'   => time() - (10 * 60),
                    ],
                    4 => [
                        'start_active' => 0,
                        'end_active'   => time() - (60 * 60),
                    ],
                    5 => [
                        'start_active' => time() - (86400 * 24),
                        'end_active'   => time(),
                    ],
                ];
                //                $usersId      = rep()->user->getQuery()->get()->pluck('id')->toArray();
                //                $finalUsersId = array_random($usersId, 65);
                //                shuffle($finalUsersId);
                //                dd($finalUsersId);
                foreach ($userIds as $key => $users) {
                    foreach ($users as $user) {
                        $activeAt = rand($times[$key]['start_active'], $times[$key]['end_active']);
                        $userId   = $user['user_id'];
                        $nickName = $userId . '-' . date('d H:i', $activeAt) . '-T' . $key;
                        $this->info($nickName);
                        $selfUser = rep()->user->getById($userId);
                        rep()->user->getQuery()->where('id', $selfUser->id)->update([
                            'nickname'   => $nickName,
                            'gender'     => User::GENDER_WOMEN,
                            'destroy_at' => 0
                        ]);
                        rep()->userDetail->m()->where('user_id', $selfUser->id)
                            ->update(['reg_schedule' => UserDetail::REG_SCHEDULE_FINISH]);
                        pocket()->netease->userUpdateUinfo($selfUser->uuid, $nickName);

                        $role     = rep()->role->m()->where('key', 'charm_girl')->first();
                        $userRole = rep()->userRole->m()
                            ->where('user_id', $userId)
                            ->where('role_id', $role->id)
                            ->first();
                        if (!$userRole) {
                            rep()->userRole->m()->create([
                                'user_id' => $userId,
                                'role_id' => $role->id
                            ]);
                        }
                        pocket()->user->updateUserTableRoleField($selfUser->id);

                        rep()->user->getQuery()->where('id', $userId)->update([
                            'active_at' => $activeAt,
                            'hide'      => 0
                        ]);
                        rep()->userDetail->getQuery()
                            ->where('user_id', $userId)
                            ->update(['lat' => $user['lat'], 'lng' => $user['lng']]);
                        $response = pocket()->esUser->updateOrPostUserLocation(
                            $userId,
                            (float)$user['lng'],
                            (float)$user['lat']
                        );
                        $response = pocket()->esUser->updateUserFieldToEs(
                            $userId,
                            [
                                'location'   => [
                                    'lon' => (float)$user['lng'],
                                    'lat' => (float)$user['lat']
                                ],
                                'active_at'  => $activeAt,
                                'charm_girl' => 1
                            ]
                        );

                        pocket()->account->updateLocation($userId, (float)$user['lng'], (float)$user['lat']);
                        echo PHP_EOL;
                    }
                }
                break;
            case 'send_test_yuanzhi_msg':
                $sendMobiles = [13777178285, 13797491927];
                $uid         = pocket()->util->getSnowflakeId();
                foreach ($sendMobiles as $mobile) {
                    $now          = time();
                    $createData[] = [
                        'biz_key'    => $uid,
                        'mobile'     => $mobile,
                        'send_at'    => 0,
                        'status'     => 0,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }
                dd(pocket()->yuanZhi->sendBatchMsg(
                    $uid,
                    $sendMobiles,
                    '【小圈】没上线的这段时间，新入上百位同城女生、有女生多次查看你的主页，点击查看 https://dwz.cn/JxkuauVE 回TD退订')
                );
                break;
            case 'add_base_user_detail_extra':
                $startUserId = $this->ask('请输入起始用户ID');
                $endUserId   = $this->ask('请输入结束用户ID');
                $data        = [];
                $now         = time();
                for ($i = $startUserId; $i <= $endUserId; $i++) {
                    $data[] = [
                        'user_id'    => $i,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    if (count($data) % 5000 == 0 || $i >= $endUserId) {
                        rep()->userDetailExtra->m()->insert($data);
                        $this->info(get_command_output_date() . "start:" . $data[0]['user_id'] . "end:" . $data[count($data) - 1]['user_id'] . "添加成功");
                        echo PHP_EOL;
                        $data = [];
                    }
                }
                break;
            case 'get_fake_evaluate_user':
                dd(explode(',', config('custom.fake_evaluate_user')));

            case 'verify_user_card':
                $checkResp = pocket()->wgcYunPay->checkIdCardAndName('刘天晨', '62012319940422661X');
                dd($checkResp);
            case 'refresh_blacklist':
                $blackUser = rep()->blacklist->m()->select('related_id', DB::raw('MAX(expired_at) as expired_at'))
                    ->where('related_type', Blacklist::RELATED_TYPE_OVERALL)
                    ->groupBy('related_id')->get();

                $users    = rep()->user->m()->select('id', 'uuid')
                    ->whereIn('id', $blackUser->pluck('related_id')->toArray())
                    ->get();
                $userUids = [];
                foreach ($users as $user) {
                    $userUids[$user->id] = $user->uuid;
                }

                $redisUserKey = config('redis_keys.blacklist.user.key');
                foreach ($blackUser as $item) {
                    redis()->client()->zAdd($redisUserKey, $item->expired_at, $item->related_id);
                    pocket()->netease->userBlock($userUids[$item->related_id]);
                    $this->info($item->related_id . "用户补充封禁成功" . PHP_EOL);
                }
                break;
            case 'es_chat_img_count':
                $filters   = $ranges = [];
                $filters[] = ['type' => ['query' => 100]];
                $ranges[]  = ['created_at' => ['from' => 1616256000]];
                $ranges[]  = ['created_at' => ['lte' => 1616342400]];
                $response  = pocket()->esImChat->searchChatImgCount($filters, $ranges);
                dd($response);
            case 'update_spam_status':
                mongodb('message_spam')->update(['status' => 0]);
                $this->line('success');
            case 'test_es_except_ids':
                $ids = range(1, 3000);
                $this->line(json_encode($ids));
                $this->line(microtime(true));
                pocket()->esUser->testEsExceptIds($ids);
                $this->line(PHP_EOL);
                $this->line(microtime(true));
                break;
            case 'test_post_parse_wechat':
                $wechat = rep()->wechat->getById(101);
                pocket()->common->commonQueueMoreByPocketJob(
                    pocket()->wechat,
                    'postParseWeChat',
                    [$wechat]
                );
                break;
            case 'clear_women_wechat_trade':
                $userAllIds = rep()->user->getQuery()->where('gender', User::GENDER_WOMEN)
                    ->pluck('id')->toArray();
                $data       = array_chunk($userAllIds, ceil(count($userAllIds) / 500));
                $timestamps = ['created_at' => time(), 'updated_at' => time()];
                $switch     = rep()->switchModel->getQuery()->where('key',
                    SwitchModel::KEY_CLOSE_WE_CHAT_TRADE)->first();
                foreach ($data as $userIds) {
                    $existsUsers      = rep()->userSwitch->write()->whereIn('user_id', $userIds)
                        ->where('switch_id', $switch->id)->pluck('user_id')->toArray();
                    $notExistsUserIds = array_values(array_diff($userIds, $existsUsers));
                    $userSwitchData   = [];
                    foreach ($notExistsUserIds as $notExistsUserId) {
                        $this->info(sprintf('取消%d用户的微信支付', $notExistsUserId));
                        $userSwitchData[] = array_merge($timestamps, [
                            'user_id'   => $notExistsUserId,
                            'switch_id' => $switch->id,
                            'status'    => 1,
                            'uuid'      => pocket()->util->getSnowflakeId()
                        ]);
                    }

                    $userSwitchData && rep()->userSwitch->getQuery()
                        ->insert($userSwitchData);
                }
                break;
            case 'fix_ab_test_invite_user':
                $users = mongodb('invite_user')
                    ->where('user_id', '>', 0)
                    ->get();
                foreach ($users as $user) {
                    $userAbInfo = rep()->userAb->m()
                        ->where('user_id', $user['user_id'])
                        ->whereIn('type', [UserAb::TYPE_MAN_INVITE_TEST_A, UserAb::TYPE_MAN_INVITE_TEST_B])
                        ->first();
                    if ($userAbInfo) {
                        $type = $userAbInfo->type;
                        mongodb('invite_user')->where('user_id', $user['user_id'])->update(['type' => $type]);
                    }
                }
                $types = mongodb('ab')->where('type', '>', 0)->get();
                foreach ($types as $type) {
                    $count = mongodb('invite_user')
                        ->where('type', $type['type'])
                        ->count();
                    mongodb('ab')->where('type', $type['type'])->update(['invite_count' => $count]);
                }
                break;
            case 'fix_ab_test_invite_count':
                //                $userIds = rep()->userAb->m()
                //                    ->where('created_at', '>=', 1617033600)
                //                    ->where('type', 202)
                //                    ->pluck('user_id')
                //                    ->toArray();
                //                $count   = rep()->userDetail->m()
                //                    ->whereIn('inviter', $userIds)
                //                    ->where('created_at', '>=', 1617033600)
                //                    ->pluck('inviter')->count();
                //                mongodb('ab')->where('type', 202)->update(['invite_count' => $count]);
                //                $payCount = mongodb('pay_user')->where('type', 202)->count();
                //                $percent  = round($payCount / $count, 2);
                mongodb('ab')->where('type', 202)->update(['invite_recharge_percent' => round(107 / 187, 2)]);
                break;
            case 'get_moment_id':
                $result = pocket()->esUser->getUserByUserId(454112);
                dd($result);
                break;
            case 'decryption_admin_id':
                $token    = $this->ask('请输入起始admin Token');
                $tokenArr = explode('.', $token);
                if (count($tokenArr) != 2) {
                    return $this->error('请输入正确的token');
                }
                $token   = $tokenArr[1];
                $authArr = json_decode(aes_encrypt()->decrypt($token), true);
                dd($authArr);
                break;
            case 'send_netease_to_charm':
                $users = rep()->user->m()->where('uuid', '>', 198248367818756096)->where('role',
                    'auth_user,charm_girl,user')->get();
                foreach ($users as $user) {
                    $msg = '为了净化网络化境，为用户提供优质的网络空间，平台自日起，将针对平台内的‘聊天内容’进行严格的整治抽查，请各位用户切勿使用低俗、色情、涉黄文案、图片等，如有违规，平台有权对违规人员进行封禁处理。 请大家务必遵守，营造绿色、安全的社交环境';
                    pocket()->netease->msgSendMsg(config('custom.little_helper_uuid'), $user->uuid, $msg);
                    echo $user->uuid . "发送成功" . PHP_EOL;
                }
                break;
        }
    }
}
