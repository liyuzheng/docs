<?php


namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MsgStaticCommand extends Command
{
    //php artisan xiaoquan:msg_static --queue=0  --delete_mongo=1 0
    //php artisan xiaoquan:msg_static --queue=1 --delete_mongo=0  0
    protected $signature   = 'xiaoquan:msg_static {--queue=1}  {--delete_mongo=1} {user_ids}';
    protected $description = '消息统计';
    //
    protected $startTime = 1617120000;
    protected $endTime   = 1617638400;

    protected $startUnixTime = 1617120000000;
    protected $endUnixTime   = 1617638400000;


    protected $msg = [];

    public function __construct()
    {
        $this->msg = config('custom.greet.msg');
        parent::__construct();
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $options     = $this->options('queue');
        $queue       = $options['queue'];
        $deleteMongo = $options['delete_mongo'];
        if ($deleteMongo == 1) {
            mongodb('msg_static')->delete();
            mongodb('msg_static_detail')->delete();
        }
        $userIds   = [];
        $userIdStr = $this->argument('user_ids');
        if ($userIdStr) {
            $userIds = explode(',', $userIdStr);
        }
        $countUserIds = count($userIds);
        $query        = rep()->user->m()
            ->where('active_at', '>=', $this->startTime)
            //            ->where('active_at', '<=', $this->endTime)
            ->where('gender', User::GENDER_WOMEN);
        if ((bool)$countUserIds) {
            $users = $query->whereIn('id', $userIds)->get();
        } else {
            $users = $query->when($queue == 1, function ($q) {
                $q->whereRaw('MOD(id,2)', 0);
            }, function ($q) {
                $q->whereRaw('id=(id>>1)<<1');
            })->get();
        }

        $countNum = $users->count();
        $this->line("总共： " . $countNum . ' 条');
        foreach ($users as $user) {
            // 1、获取和该女生聊天的所有男生
            $chatIds = $this->getChatMans($user->id);
            $countUserIds && $this->line(json_encode($chatIds));
            foreach ($chatIds as $chatId) {
                $this->getMsgStatic($user->id);
                // 2、查找两个人之间的聊天内容，符合条件的统计入库
                $results = $this->count($user->id, $chatId);
                $countUserIds && $this->line(json_encode($results));
                if ($results['active']) {
                    mongodb('msg_static')->where('_id', $user->id)->increment('active');
                }
                if ($results['has_wechat']) {
                    mongodb('msg_static')->where('_id', $user->id)->increment('has_wechat');
                }
            }
            $countNum--;
            $this->line("已处理 user_id：" . $user->id . ' 剩余：' . $countNum);
        }
        $this->line("success");
    }

    protected function getChatMans($sendId)
    {
        $ranges    = [];
        $filters[] = ['send_id' => ['query' => $sendId]];
        $ranges[]  = ['send_at' => ['gte' => $this->startUnixTime]];
        $ranges[]  = ['send_at' => ['lte' => $this->endUnixTime]];
        $response  = pocket()->esImChat->getImChatGroupBy($filters, $ranges, 'receive_id');

        return $response->getData()['data'] ?? [];
    }

    /**
     * 统计数据
     *
     * @param $womenId
     * @param $manId
     *
     * @return false[]
     */
    protected function count($womenId, $manId)
    {
        $returnField = [
            'active'     => false,//第一条是否是主动
            'has_wechat' => false,//第一条是否携带微信信息
        ];
        $fileds      = ['send_id', 'receive_id', 'content', 'send_at', 'type'];
        $response    = pocket()->esImChat->searchImChat(
            $womenId, $manId, $this->startUnixTime,
            $this->endUnixTime, "", $fileds, 1, 1
        );
        if (!$response->getStatus()) {
            return $returnField;
        }
        $data     = $response->getData()['data'];
        $firstMsg = collect($data)->sortBy('send_at')->first();
        $content  = $firstMsg['content'] ?? "";
        if (in_array($content, $this->msg, true)) {
            return $returnField;
        }
        $sendId = $firstMsg['send_id'] ?? 0;
        if ($sendId == $womenId) {
            $returnField['active'] = true;
        }
        $strContain = str_starts_with($content, 'uploads/');
        if ($strContain) {
            return $returnField;
        }
        $wchat = '/[a-zA-Z0-9\-\_]{6,16}/isu';
        preg_match($wchat, $content, $matches);
        if (count($matches) > 0) {
            mongodb('msg_static')
                ->where('_id', $womenId)
                ->where('msg', "")
                ->update([
                    'msg' => $content
                ]);
            mongodb('msg_static_detail')->insert([
                'user_id'        => $womenId,
                'target_user_id' => $manId,
                'content'        => $content
            ]);
            $returnField['has_wechat'] = count($data);
        }

        return $returnField;
    }

    /**
     * 获取用户消息统计
     *
     * @param $userId
     *
     * @return \Illuminate\Database\Eloquent\Model|\Jenssegers\Mongodb\Eloquent\Builder|object|null
     */
    public function getMsgStatic($userId)
    {
        $static = mongodb('msg_static')->where('_id', $userId)->first();
        if (!$static) {
            $user = rep()->user->m()->where('id', $userId)->first();
            mongodb('msg_static')->insert([
                '_id'        => $userId,
                'has_wechat' => 0,
                'active'     => 0,
                'is_member'  => $user->isMember() == true ? 1 : 0,
                'msg'        => '',
            ]);
            $static = mongodb('msg_static')->where('_id', $userId)->first();
        }

        return $static;
    }
}
