<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 填充审核用户信息
 * Class FixAuditUserInfoCommand
 * @package App\Console\Commands
 */
class FixDataCommand extends Command
{
    protected $signature   = 'xiaoquan:fix_data';
    protected $description = '修复数据';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $action = $this->choice('请选择动作类型', [
            'fix_invite_data'   => '修复邀请数据',
            'move_message_spam' => '移动message_spam数据',
            'wechat_parse'      => '微信内容解析',
        ]);

        switch ($action) {
            case 'move_message_spam':
                $startDate = $this->ask('请输入开始日期');
                $endDate   = $this->ask('请输入结束日期');
                $startTime = Carbon::createFromDate($startDate);
                $endTime   = Carbon::createFromDate($endDate);
                $diffDate  = $endTime->diffInDays($startTime);
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
            case 'fix_invite_data':
                $startDate = $this->ask('请输入开始日期');
                $endDate   = $this->ask('请输入结束日期');
                $startTime = Carbon::createFromDate($startDate);
                $endTime   = Carbon::createFromDate($endDate);
                $diffDate  = $endTime->diffInDays($startTime);
                $dateArr   = [];
                for ($i = 0; $i <= $diffDate; $i++) {
                    $dateArr[] = [
                        'date' => date('Y-m-d', $startTime->timestamp),
                        'st'   => $startTime->timestamp,
                        'et'   => $startTime->addDays(1)->timestamp - 1,
                    ];
                }
                foreach ($dateArr as $item) {
                    $this->info(get_command_output_date() . '------------------' . $item['date']);
                    $iosManCount = rep()->user->m()
                        ->join('user_detail', 'user.id', '=', 'user_detail.user_id')
                        ->whereBetween('user.created_at', [$item['st'], $item['et']])
                        ->where('user_detail.os', 'ios')
                        ->where('user_detail.inviter', '>', 0)
                        ->where('gender', User::GENDER_MAN)
                        ->count();

                    $androidManCount = rep()->user->m()
                        ->join('user_detail', 'user.id', '=', 'user_detail.user_id')
                        ->whereBetween('user.created_at', [$item['st'], $item['et']])
                        ->where('user_detail.os', 'android')
                        ->where('user_detail.inviter', '>', 0)
                        ->where('gender', User::GENDER_MAN)
                        ->count();

                    $inviteData = rep()->userDetail->m()
                        ->select('os', 'user_id')
                        ->whereBetween('user_detail.created_at', [$item['st'], $item['et']])
                        ->where('inviter', '>', 0)
                        ->get();
                    if ($inviteData->count()) {
                        $allCount = $inviteData->count();
                        $iosCount = $inviteData->where('os', 'ios')->count();
                        //ios
                        $this->info(get_command_output_date() . 'os-: ios current_user_count: ' . $iosCount . ' current_man_count:' . $iosManCount);
                        $this->update(
                            $item['date'],
                            'ios',
                            $iosCount,
                            $iosManCount
                        );

                        //android
                        $this->info(get_command_output_date() . 'os-: android current_user_count: ' . $inviteData->where('os',
                                'android')->count() . ' current_man_count:' . $androidManCount);
                        $this->update(
                            $item['date'],
                            'android',
                            $inviteData->where('os', 'android')->count(),
                            $androidManCount
                        );

                        //all
                        $this->info(get_command_output_date() . 'os-: all current_user_count: ' . $allCount . ' current_man_count:' .
                            ($iosManCount + $androidManCount));
                        $this->update(
                            $item['date'],
                            'all',
                            $allCount,
                            $iosManCount + $androidManCount
                        );
                    } else {
                        $this->info(get_command_output_date() . '无数据');
                    }
                    echo PHP_EOL;
                    echo PHP_EOL;
                }
                break;
            case 'wechat_parse':
                for ($i = 1; $i++; $i <= 102705) {
                    $wechat = rep()->wechat->getById($i);
                    if ($wechat) {
                        $this->info(get_command_output_date() . 'id: ' . $i);
                        pocket()->common->commonQueueMoreByPocketJob(
                            pocket()->wechat,
                            'postParseWeChat',
                            [$wechat]
                        );
                    }
                }
                break;
        }
    }

    public function update($date, $os, $currentUserCount, $currentManCount)
    {
        if (rep()->statDailyInvite->m()->where('date', $date)->where('os', $os)->count()) {
            rep()->statDailyInvite->m()->where('date', $date)
                ->where('os', $os)
                ->update([
                        'current_user_count' => $currentUserCount,
                        'current_man_count'  => $currentManCount
                    ]
                );
        } else {
            rep()->statDailyInvite->m()
                ->create([
                        'date'               => $date,
                        'os'                 => $os,
                        'current_user_count' => $currentUserCount,
                        'current_man_count'  => $currentManCount
                    ]
                );
        }
    }
}
