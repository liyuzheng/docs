<?php

namespace App\Console\Commands;


use App\Models\MemberRecord;
use Illuminate\Console\Command;
use App\Models\UserReview;
use App\Models\Wechat;
use Illuminate\Support\Str;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use App\Models\Card;
use App\Models\Resource;

class TestInvitedUserCommand extends Command
{
    protected $signature = 'xiaoquan:invite_user';
    protected $description = '命令集合';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $userId       = $this->ask('请输入用户ID');
        $targetUserId = $this->ask('请输入对方ID');

        $user       = rep()->user->getById($userId);
        $targetUser = rep()->user->getById($targetUserId);
        if (!$user || !$targetUser) {
            $this->error('用户ID不正确');

            return;
        }
        $action = $this->choice('请选择动作类型', [
            'reg_by_man_or_woman'    => '用户通过邀请连接注册',
            'member_by_man_or_woman' => '用户通过邀请连接成为会员',
            'clean_user_data'        => '清除邀请数据',
            'clean_all_data'         => '删除邀请所有数据',
            'get_invite_qrcode'      => '获得邀请码',
            'fix_invite_qrcode'      => '补全用户邀请码'

        ]);
        switch ($action) {
            case 'reg_by_man_or_woman':
                $response = pocket()->task->postTaskInviteRegister($userId, $targetUserId);
                break;
            case 'member_by_man_or_woman':
                $response = pocket()->task->postTaskInviteMember($userId, $targetUserId);
                break;
            case 'clean_user_data':
                $inviteRecord = rep()->inviteRecord->m()->where('target_user_id', $targetUserId)->get();
                if (!$inviteRecord) {
                    $this->error(get_command_output_date() . ' 没有 inviteRecord 数据');

                    return;
                }
                $inviteRecordIds = $inviteRecord->pluck('id')->toArray();
                $this->info(get_command_output_date() . ' inviteRecord ID:' . json_encode($inviteRecordIds));
                $this->info(get_command_output_date() . ' 删除 inviteRecord 数据');
                rep()->inviteRecord->m()->whereIn('id', $inviteRecordIds)->delete();

                $this->info(get_command_output_date() . ' 删除 task 数据');
                $taskId = rep()->task->m()->whereIn('related_id', $inviteRecordIds)->pluck('id')->toArray();
                rep()->task->m()->whereIn('related_id', $inviteRecordIds)->delete();
                rep()->taskPrize->m()->whereIn('task_id', $taskId)->delete();

                $this->info(get_command_output_date() . ' 清空 inviter');
                rep()->userDetail->m()->where('user_id', $targetUserId)->update(['inviter' => 0]);

                $this->info(get_command_output_date() . ' 清空 wallet');
                rep()->wallet->m()->where('user_id', $targetUser)->update([
                    'income'         => 0,
                    'income_total'   => 0,
                    'free_vip'       => 0,
                    'free_vip_total' => 0
                ]);

                if ($this->confirm('是否要删除会员信息')) {
                    $this->info(get_command_output_date() . ' 开始删除会员信息');
                    rep()->memberRecord->m()->where('user_id', $targetUserId)->delete();
                    rep()->member->m()->where('user_id', $targetUser)->delete();
                    $this->info(get_command_output_date() . ' 开始删除会员信息');
                }

                return;
                break;
            case 'clean_all_data':
                $this->info('----------- 删除 task 表');
                DB::table('task')->delete();

                $this->info('----------- 删除 task_prize 表');
                DB::table('task_prize')->delete();

                $this->info('----------- 删除 invite_record 表');
                DB::table('invite_record')->delete();

                $this->info('----------- 删除 member_record 表');
                DB::table('member_record')->where('type', MemberRecord::TYPE_INVITE_USER)->delete();

                $this->info('----------- 删除 member 表');
                $card = rep()->card->getFreeMemberCard();
                DB::table('member')->where('card_id', $card->id)->delete();


                $this->info('----------- 重置 userDetail 表');
                DB::table('user_detail')->update([
                    'inviter'      => 0,
                    'invite_count' => 0,
                ]);


                $this->info('----------- 重置 wallet 表');
                DB::table('wallet')->update([
                    'income_invite'       => 0,
                    'income_invite_total' => 0,
                    'free_vip'            => 0,
                    'free_vip_total'      => 0,
                ]);

                return;
                break;
            case 'get_invite_qrcode':
                $response = pocket()->resource->postUserInviteCodeQrCode($targetUserId);
                break;
            case 'fix_invite_qrcode':
                $usersId = rep()->user->m()->pluck('id')->toArray();
                foreach ($usersId as $item) {
                    $this->info('---------------------- user_id: ' . $item);
                    usleep(200000);
                    pocket()->common->commonQueueMoreByPocketJob(
                        pocket()->resource,
                        'postUserInviteCodeQrCode',
                        [$item]
                    );
                }

                return;
                break;


        }
        if (!$response->getStatus()) {
            $this->error($response->getMessage());

            return;
        }
        dd($response->getData());
    }
}
