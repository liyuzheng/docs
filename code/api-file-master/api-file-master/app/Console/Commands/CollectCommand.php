<?php

namespace App\Console\Commands;


use App\Models\Moment;
use App\Models\Translate;
use App\Models\User;
use App\Tools\SeaTranslation;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CollectCommand extends Command
{
    protected $signature   = 'xiaoquan:collect {action}';
    protected $description = '命令集合';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $action = $this->argument('action');
        switch ($action) {
            case 'general_office_access_token':
                $accessToken = pocket()->wechat->getOfficeAccessToken();
                $this->info(get_command_output_date() . ' action: ' . $action . 'access_token: ' . $accessToken);
                break;
            case 'backup_yesterday_message_spam':
                $startTime = Carbon::today()->subDays(6);
                $diffDate  = 0;
                $dateArr   = [];
                for ($i = 0; $i <= $diffDate; $i++) {
                    $dateArr[] = [
                        'date' => date('Y-m-d', $startTime->timestamp),
                        'st'   => $startTime->timestamp,
                        'et'   => $startTime->addDays(1)->timestamp - 1,
                    ];
                }
                foreach ($dateArr as $item) {
                    $this->info(get_command_output_date() . 'date: ', $item['date']);
                    echo PHP_EOL;
                    $mongs = mongodb('message_spam')
                        ->whereBetween('created_at', [$item['st'], $item['et']])
                        ->get();
                    foreach ($mongs as $mong) {
                        $mong['expired_at'] = new \MongoDB\BSON\UTCDateTime(
                            new \DateTime(date('Y-m-d H:i:s', $mong['created_at']))
                        );
                        $idObj              = $mong['_id'];
                        $id                 = $idObj->jsonSerialize()['$oid'];
                        if (mongodb('message_spam_backup')->insert($mong)) {
                            $this->info(get_command_output_date() . '-' . date('Y-m-d H:i:s',
                                    $mong['created_at']) . 'ID: ' . $id);
                            mongodb('message_spam')->where('_id', $idObj)->delete();
                        }
                    }
                }
                break;
            case 'read_xml':
                $seaTransaction = (new SeaTranslation());
                $transaction    = array_merge(
                    $seaTransaction->getHomeStrings(),
                    $seaTransaction->getMainStrings(),
                    $seaTransaction->getStrings(),
                    $seaTransaction->getStringsPtr(),
                    $seaTransaction->getStringsUikit()
                );
                foreach ($transaction as $key => $item) {
                    $tran = rep()->translate->m()->create([
                        'os'             => Translate::OS_COMMON,
                        'key'            => $key,
                        'chinese'        => $item,
                        'tw_traditional' => '',
                        'xg_traditional' => '',
                        'english'        => '',
                    ]);
                    $this->info('key: ' . $key . ' value: ' . $item);
                }
                break;
            case 'server_translate':

                $file_path = storage_path('file/server_translate.txt');
                if (file_exists($file_path)) {
                    $file_arr = file($file_path);
                    $arrs     = [];
                    foreach ($file_arr as $item) {
                        $arrs [] = str_replace("\n", '', $item);
                    }
                    foreach ($arrs as $arr) {
                        $tran = rep()->translate->m()->create([
                            'os'             => Translate::OS_SERVER,
                            'key'            => 'server_key',
                            'chinese'        => $arr,
                            'tw_traditional' => '',
                            'xg_traditional' => '',
                            'english'        => '',
                        ]);
                        $this->info('key: server_key' . $arr . ' value: ' . $arr);
                    }
                }
                break;
            case 'fix_user_charm_girl_at':
                $charmRoleId = rep()->role->m()->where('key', User::ROLE_CHARM_GIRL)->value('id');
                $userRoles   = rep()->userRole->m()->where('role_id', $charmRoleId)->where('deleted_at', 0)->get();
                foreach ($userRoles as $userRole) {
                    rep()->user->m()->where('id', $userRole->user_id)
                        ->update(['charm_girl_at' => $userRole->created_at->timestamp]);
                    $this->info('user_id: ' . $userRole->user_id . ' created_at: ' . $userRole->created_at->timestamp);
                }
                break;
            case 'update_moment_sort':
                for ($i = 1; $i <= 160000; $i++) {
                    $moment = DB::table('moment')->where('id', $i)->first();
                    if ($moment) {
                        $response = pocket()->esMoment->updateMomentFieldToEs($moment->id,
                            [
                                'sort'       => $moment->sort,
                                'like_count' => $moment->like_count,
                                'deleted_at' => $moment->deleted_at
                            ]
                        );
                        $this->info('moment_id: ' . $moment->id . ' user_id: ' . $moment->user_id . ' sort: ' . $moment->sort . ' like_count: ' . $moment->like_count . ' deleted_at: ' . $moment->deleted_at . ' status: ' . $response->getStatus());
                    }
                }

                break;
            case 'del_unused_file':
                $startTime = Carbon::today()->subDays(144);
                $diffDate  = 144;
                $dateArr   = [];
                for ($i = 0; $i <= $diffDate; $i++) {
                    $dateArr[] = [
                        'date' => date('Y-m-d', $startTime->timestamp),
                        'st'   => $startTime->timestamp,
                        'et'   => $startTime->addDays(1)->timestamp - 1,
                    ];
                }
                foreach ($dateArr as $item) {
                    $files = mongodb('upload_file_record')
                        ->whereBetween('created_at', [$item['st'], $item['et']])
                        ->whereIn('type', ['user_photo', 'user_avatar'])
                        ->get();
                    foreach ($files as $file) {
                        $path          = $file['path'];
                        $resource      = DB::table('resource')
                            ->where('resource', $file['path'])
                            ->first();
                        $resourceCheck = DB::table('resource_check')
                            ->where('resource', $path)
                            ->first();
                        if (!$resource && !$resourceCheck) {
                            $idObj = $file['_id'];
                            if (file_exists(public_path($path))) {
                                if (unlink(public_path($path))) {
                                    mongodb('upload_file_record')->where('_id', $idObj)->delete();
                                    $this->info(get_command_output_date() . ' ' . $item['date'] . ' resource: ' . $path);
                                }
                            }
                        }
                    }
                }
                break;
            case 'update_hack_config':
                $configs = [
                    'file_domain'         => 'https://file.wqdhz.com/',
                    'user_protocol'       => 'https://web.wqdhz.com/user_protocol',
                    'api_domain'          => 'https://api.wqdhz.com/',
                    'user_privacy'        => 'https://web.wqdhz.com/user_privacy',
                    'withdraw_url'        => 'https://web.wqdhz.com/ali_withdraw',
                    'member_renew_url'    => 'https://web.wqdhz.com/member_renew',
                    'member_service_url'  => 'https://web.wqdhz.com/member_service',
                    'gem_url'             => 'https://web-pay.wqdhz.com/client/payment',
                    'member_url'          => 'https://web-pay.wqdhz.com/become_a_member',
                    'withdraw_invite_url' => 'https://web.wqdhz.com/ali_withdraw_invite',
                    'trades_records'      => 'https://web-pay.wqdhz.com/records',
                    'invite_web_url'      => 'https://web.wqdhz.com/invite',
                    'pay_arouse_url'      => 'https://web-pay.wqdhz.com/pay/arouse'
                ];
                foreach ($configs as $key => $value) {
                    rep()->config->m()->where('key', $key)->update(['value' => $value]);
                    $this->info(get_command_output_date() . ' key: ' . $key . ' value: ' . $value);
                }
                echo PHP_EOL;
                break;
            case 'general_crypt_password':
                $password = $this->ask('请输入密码');
                dd(bcrypt($password));
                break;
            case 'insert_fix_url_error_user':
                $array = [
                    'space'  => [
                        '16-17'    => [
                            'path'     => storage_path('file/url_error/space/16-17.txt'),
                            'event_st' => strtotime('2021-05-04 16:00:00'),
                            'event_et' => strtotime('2021-05-04 17:00:00')
                        ],
                        '17-17.30' => [
                            'path'     => storage_path('file/url_error/space/17-17.30.txt'),
                            'event_st' => strtotime('2021-05-04 17:00:00'),
                            'event_et' => strtotime('2021-05-04 17:30:00')
                        ],
                        '17.30-18' => [
                            'path'     => storage_path('file/url_error/space/17.30-18.txt'),
                            'event_st' => strtotime('2021-05-04 17:30:00'),
                            'event_et' => strtotime('2021-05-04 18:00:00')
                        ],
                        '18-18.30' => [
                            'path'     => storage_path('file/url_error/space/18-18.30.txt'),
                            'event_st' => strtotime('2021-05-04 18:00:00'),
                            'event_et' => strtotime('2021-05-04 18:30:00')
                        ],
                        '18.30-19' => [
                            'path'     => storage_path('file/url_error/space/18.30-19.txt'),
                            'event_st' => strtotime('2021-05-04 18:30:00'),
                            'event_et' => strtotime('2021-05-04 19:00:00')
                        ],
                        '19-19.30' => [
                            'path'     => storage_path('file/url_error/space/19-19.30.txt'),
                            'event_st' => strtotime('2021-05-04 19:00:00'),
                            'event_et' => strtotime('2021-05-04 19:30:00')
                        ],
                        '19.30-20' => [
                            'path'     => storage_path('file/url_error/space/19.30-20.txt'),
                            'event_st' => strtotime('2021-05-04 19:30:00'),
                            'event_et' => strtotime('2021-05-04 20:00:00')
                        ],
                        '20-20.30' => [
                            'path'     => storage_path('file/url_error/space/20-20.30.txt'),
                            'event_st' => strtotime('2021-05-04 20:00:00'),
                            'event_et' => strtotime('2021-05-04 20:30:00')
                        ],
                        '20.30-21' => [
                            'path'     => storage_path('file/url_error/space/20.30-21.txt'),
                            'event_st' => strtotime('2021-05-04 20:30:00'),
                            'event_et' => strtotime('2021-05-04 21:00:00')
                        ],
                    ],
                    'u03bfm' => [
                        '20.30-21' => [
                            'path'     => storage_path('file/url_error/u03bfm/20.30-21.txt'),
                            'event_st' => strtotime('2021-05-04 20:30:00'),
                            'event_et' => strtotime('2021-05-04 21:00:00')
                        ],
                        '21-21.30' => [
                            'path'     => storage_path('file/url_error/u03bfm/21-21.30.txt'),
                            'event_st' => strtotime('2021-05-04 21:00:00'),
                            'event_et' => strtotime('2021-05-04 21:30:00')
                        ],
                        '21.30-22' => [
                            'path'     => storage_path('file/url_error/u03bfm/21.30-22.txt'),
                            'event_st' => strtotime('2021-05-04 21:30:00'),
                            'event_et' => strtotime('2021-05-04 22:00:00')
                        ],
                        '23-23.30' => [
                            'path'     => storage_path('file/url_error/u03bfm/23-23.30.txt'),
                            'event_st' => strtotime('2021-05-04 23:00:00'),
                            'event_et' => strtotime('2021-05-04 23:30:00')
                        ],
                        '23.30-00' => [
                            'path'     => storage_path('file/url_error/u03bfm/23.30-00.txt'),
                            'event_st' => strtotime('2021-05-04 23:30:00'),
                            'event_et' => strtotime('2021-05-05 00:00:00')
                        ],
                    ],
                ];
                foreach ($array as $errorType => $item) {
                    foreach ($item as $eventTime => $value) {
                        $fileUrl = $value['path'];
                        $this->info(get_command_output_date() . 'path :' . $fileUrl);
                        $isss = file_exists($fileUrl) or exit("There is no file");
                        $file = fopen($fileUrl, "r");
                        $user = array();
                        $i    = 0;
                        while (!feof($file)) {
                            $user[$i] = fgets($file);
                            $i++;
                        }
                        fclose($file);
                        $tmpUser = array_filter($user);
                        foreach ($tmpUser as $item) {
                            $midUser[] = explode(',', str_replace('\n', '', $item))[0];
                        }
                        foreach ($midUser as $item) {
                            $user = rep()->user->getByUUid($item);
                            if ($user) {
                                $userDetail = rep()->userDetail->getByUserId($user->id);
                                $insertArr  = [
                                    'uuid'             => $user->uuid,
                                    'user_id'          => $user->id,
                                    'error_type'       => $errorType,
                                    'event_time'       => $eventTime,
                                    'mobile'           => $user->mobile,
                                    'os'               => $userDetail->os,
                                    'reg_version'      => $userDetail->reg_version,
                                    'run_version'      => $userDetail->run_version,
                                    'event_st'         => $value['event_st'],
                                    'event_et'         => $value['event_et'],
                                    'latest_active_at' => $user->active_at,
                                    'created_at'       => time(),
                                    'updated_at'       => time()
                                ];
                                $exists     = DB::table('fix_url_error_user')
                                    ->where('user_id', $user->id)
                                    ->first();
                                if (!$exists) {
                                    DB::table('fix_url_error_user')->insert($insertArr);
                                    $this->info('user_id: ' . $user->id);
                                }
                            }
                        }
                    }
                    echo PHP_EOL;
                    echo PHP_EOL;
                    echo PHP_EOL;
                }
                break;
            case 'insert_spam_word':
                $words = [
                    '约',
                    '约炮',
                    '高质量',
                    '质量高',
                    '价格',
                    '低价',
                    '带价',
                    '收费',
                    '有偿',
                    '定金',
                    '兼职',
                    '报价',
                    '一对一',
                    '1v1',
                    '处男',
                    '处女',
                    '上门',
                    '线上',
                    '空降',
                    '少妇',
                    '做爱',
                    'sm',
                    '喷水',
                    '情趣',
                    '野战',
                    '车震',
                    '毒龙',
                    '毒long',
                    'du龙',
                    '包养',
                    '打飞机',
                    '打蝴蝶',
                    '口暴',
                    '陪睡',
                    '喷水',
                    '白虎',
                    '一线天',
                    '蜜桃臀',
                    '丰臀',
                    '大胸',
                    '屌丝',
                    '傻逼',
                    '傻屌',
                    '招长期',
                    '招个长期',
                    '找长期',
                    '找个长期',
                    'yue',
                    '打炮',
                    '次',
                    '夜',
                    '讲价',
                    '房费',
                    '一口价',
                    '打折',
                    '打个折',
                    '￥',
                    '性价比',
                    '喷泉',
                    '卖处',
                    '开房',
                    '开个房',
                    '有地方',
                    '工作室',
                    '上门',
                    '快餐',
                    '外卖',
                    '一晚',
                    '过夜',
                    '一夜',
                    '包夜',
                    '可视',
                    '高端男士服务',
                    '海选',
                    '不限次数',
                    '按摩',
                    '全套',
                    '套餐',
                    '做ai',
                    'zuo爱',
                    '莞式',
                    '萝莉',
                    '熟女',
                    '淘餐',
                    '全桃',
                    '激情',
                    '舌吻',
                    '🐍吻',
                    '可做',
                    '爱爱',
                    '制服诱惑',
                    '腿精',
                    '口吹',
                    '一起洗澡',
                    '鸳鸯浴',
                    '蜻蜓点水',
                    '报价',
                    '报个价',
                    '啵推',
                    '臀推',
                    '3p',
                    '有工作室',
                    '轻m',
                    '喷',
                    '胸推',
                    '臀腿',
                    '漫游',
                    '刮痧',
                    '可以口',
                    '特熟服雾',
                    '一条龙',
                    '服务',
                    '调情',
                    '情趣',
                    '亲大腿两侧',
                    '深喉',
                    '诱惑',
                    '秒射',
                    '荃套',
                    '开防',
                    '自带地方',
                    '老汉推车',
                    '后入',
                    '走后门',
                    '三通',
                    '臀',
                    '口活',
                    '冰火',
                    '包月️',
                    '胸C',
                    '白色老虎',
                    '大奶牛',
                    '黑丝',
                    '丝袜',
                    '跳蛋',
                    '小恶魔',
                    '玩具',
                    '无套',
                    '无T',
                    '制服',
                    '裸聊',
                    '果聊',
                    '给口',
                    '给我口',
                    '费用',
                    '内射',
                    '撸射',
                    '可喷',
                    '包喷',
                    '包射',
                    '射了',
                    '听指挥',
                    '曹尼玛',
                    '草拟吗',
                    '操你妈',
                    '草泥马',
                    '艹你妈',
                    '艹',
                    '操',
                    '肏',
                    '尼玛的',
                    '你妈的',
                    '操你爹',
                    '草你爹',
                    '操你大爷',
                    '草你大爷',
                    'Kou爆',
                    '口bao',
                    'Kong降',
                    '尚门',
                    '赋物',
                    '口霍好',
                    '上men',
                    '车zhen'
                ];
                foreach ($words as $word) {
                    rep()->spamWord->m()->create(['version' => 2, 'word' => $word]);
                    $this->info(get_command_output_date() . 'word: ' . $word);
                }
                break;
            case 'update_hide_usersid':
                $users    = rep()->user->m()->where('hide', '<>', User::SHOW)->get()->pluck('id')->toArray();
                $redisKey = config('redis_keys.hide_users.key');
                foreach ($users as $user) {
                    if (redis()->client()->sAdd($redisKey, $user)) {
                        $this->info(get_command_output_date() . ' users: ' . $user);
                    }
                }
                break;
        }
    }
}
