<?php

namespace App\Console\Commands;


use App\Constant\SpamWorld;
use App\Jobs\CommonQueueMoreByPocketJob;
use App\Jobs\SendWeChatTemplateMsgJob;
use App\Mail\InviteWarnMail;
use App\Mail\VerifyCodeMail;
use App\Models\MemberRecord;
use App\Models\User;
use App\Models\UserAb;
use App\Models\UserAuth;
use App\Models\UserDetail;
use Carbon\Carbon;
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
    protected $signature   = 'z_:develop';
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
            'fix_ab_test_field'            => '修复ab测试字段',
            'fix_ab_test_invite_user'      => '修复ab统计邀请',
            'fix_ab_test_invite_count'     => '统计B类邀请数量',
            'compensate_member'            => '补偿用户会员天数',
            'get_moment_id'                => '获取moment的doc',
            'decryption_admin_id'          => '解密admin账户',
            'general_google_secret'        => '生成google秘钥',
        ]);
        switch ($action) {
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
            case 'add_invite_ab_test':
                $offset      = 0;
                $limit       = 500;
                $startUserId = $this->ask('请输入起始用户ID');
                $endUserId   = $this->ask('请输入结束用户ID');
                $timestamps  = ['created_at' => time(), 'updated_at' => time()];
                do {
                    $users            = rep()->user->getQuery()->select('id')->where('id', '>=', $startUserId)
                        ->where('id', '<=', $endUserId)->orderBy('id', 'asc')->offset($offset)
                        ->limit($limit)->get();
                    $userIds          = $users->pluck('id')->toArray();
                    $existsUsers      = rep()->userAb->getQuery()->whereIn('user_id', $userIds)->whereIn('type',
                        [UserAb::TYPE_MAN_INVITE_TEST_A, UserAb::TYPE_MAN_INVITE_TEST_B])
                        ->pluck('user_id')->toArray();
                    $notExistsUserIds = array_diff($userIds, $existsUsers);
                    $userAbData       = [];
                    foreach ($notExistsUserIds as $notExistsUserId) {
                        $this->info(sprintf("补充%d用户的test数据", $notExistsUserId));
                        $userAbData[] = array_merge($timestamps,
                            ['user_id' => $notExistsUserId, 'type' => UserAb::TYPE_MAN_INVITE_TEST_A]);
                    }

                    $userAbData && rep()->userAb->getQuery()->insert($userAbData);
                    $offset += $users->count();
                } while ($users->count() == $limit);

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
                    '2021-05-15',
                    '2021-05-14',
                    '2021-05-13',
                    '2021-05-12',
                    '2021-05-11',
                    '2021-05-10',
                    '2021-05-09',
                    '2021-05-08',
                    '2021-05-07',
                    '2021-05-06',
                    '2021-05-05',
                    '2021-05-04',
                    '2021-05-03',
                    '2021-05-02',
                    '2021-05-01',
                    '2021-04-30',
                    '2021-04-29',
                    '2021-04-28',
                    '2021-04-27',
                    '2021-04-26',
                    '2021-04-25',
                    '2021-04-24',
                    '2021-04-23',
                    '2021-04-22',
                    '2021-04-21',
                    '2021-04-20',
                    '2021-04-19',
                    '2021-04-18',
                    '2021-04-17',
                    '2021-04-16',
                ];
                $type     = $this->ask('请输入模式');
                foreach ($dateList as $startDate) {
                    $startTime    = strtotime($startDate);
                    $endTime      = $startTime + 86399;
                    $newUserCount = rep()->user->m()
                        ->whereBetween('created_at', [$startTime, $endTime])
                        ->count();

                    $groupNewUserCount = rep()->userDetail->m()
                        ->select(['os', DB::raw('count(*) as count')])
                        ->whereBetween('created_at', [$startTime, $endTime])
                        ->groupBy('os')
                        ->get();
                    $androidNewCount   = 0;
                    $iosNewCount       = 0;
                    foreach ($groupNewUserCount as $item) {
                        if ($item->os == 'android') {
                            $androidNewCount = $item->count;
                        }
                        if ($item->os == 'ios') {
                            $iosNewCount = $item->count;
                        }
                    }

                    $newUserTradeNumber = rep()->tradePay->m()
                        ->join('user', 'user.id', '=', 'trade_pay.user_id')
                        ->whereBetween('user.created_at', [$startTime, $endTime])
                        ->where('trade_pay.amount', ' != ', 0)
                        ->where('trade_pay.trade_no', 'not like', '10000%')
                        ->count([DB::raw('distinct(trade_pay.user_id)')]);

                    $groupNewUserTradeNumber = rep()->tradePay->m()
                        ->select([DB::raw('count(distinct(trade_pay.user_id)) as trade_number'), 'user_detail.os'])
                        ->join('user_detail', 'user_detail.user_id', '=', 'trade_pay.user_id')
                        ->whereBetween('user_detail.created_at', [$startTime, $endTime])
                        ->where('trade_pay.amount', '!=', 0)
                        ->where('trade_pay.trade_no', 'not like', '10000%')
                        ->groupBy('user_detail.os')
                        ->get();
                    $androidNewTradeNumber   = 0;
                    $iosNewTradeNumber       = 0;
                    foreach ($groupNewUserTradeNumber as $item) {
                        if ($item->os == 'android') {
                            $androidNewTradeNumber = $item->trade_number;
                        }
                        if ($item->os == 'ios') {
                            $iosNewTradeNumber = $item->trade_number;
                        }
                    }

                    $createData = [
                        'trade_rate'         => $newUserCount == 0 ? 0 : $newUserTradeNumber / $newUserCount,
                        'android_trade_rate' => $androidNewCount == 0 ? 0 : $androidNewTradeNumber / $androidNewCount,
                        'ios_trade_rate'     => $iosNewCount == 0 ? 0 : $iosNewTradeNumber / $iosNewCount,
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
            case 'update_moment_location':
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
                break;

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
            case 'test_post_parse_wechat':
                $wechat = rep()->wechat->getById(101);
                pocket()->common->commonQueueMoreByPocketJob(
                    pocket()->wechat,
                    'postParseWeChat',
                    [$wechat]
                );
                break;
            case 'fix_ab_test_field':
                $users = mongodb('pay_user')
                    ->where('user_id', '>', 0)
                    ->get();
                foreach ($users as $user) {
                    $userAbInfo = rep()->userAb->m()
                        ->where('user_id', $user['user_id'])
                        ->whereIn('type', [UserAb::TYPE_MAN_INVITE_TEST_A, UserAb::TYPE_MAN_INVITE_TEST_B])
                        ->first();
                    if ($userAbInfo) {
                        $type = $userAbInfo->type;
                        mongodb('pay_user')->where('user_id', $user['user_id'])->update(['type' => $type]);
                    }
                }
                $types = mongodb('ab')->where('type', '>', 0)->get();
                foreach ($types as $type) {
                    $count = mongodb('pay_user')->where('type', $type['type'])->count();
                    mongodb('ab')->where('type', $type['type'])->update(['invite_recharge_user_count' => $count]);
                    $percent = round($count / $type['invite_count'], 2);
                    mongodb('ab')->where('type', $type['type'])->update(['invite_recharge_percent' => $percent]);
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
            case 'general_google_secret':
                $secret = pocket()->google->getGoogleSecret();
                dd($secret);
                break;
        }
    }
}
