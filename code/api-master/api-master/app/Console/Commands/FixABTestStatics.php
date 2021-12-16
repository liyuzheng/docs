<?php


namespace App\Console\Commands;


use Illuminate\Console\Command;
use App\Jobs\ABTestJob;

class FixABTestStatics extends Command
{
    protected $signature   = 'xiaoquan:fix_ab_test_statics {created_at} {limit}';
    protected $description = '修复ab统计用户';

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $args      = $this->arguments();
        $createdAt = $args['created_at'];
        $limit     = $args['limit'];
        $action    = $this->choice('请选择动作类型', [
            'init_ab'         => '初始化ab表',
            //            'delete_mongo'    => '删除mongo数据',
            'import_register' => '导入注册',
            'import_recharge' => '导入充值',
            'init_mongo'      => '初始化mongo',

        ]);
        switch ($action) {
            case 'delete_mongo':
                mongodb('ab')->delete();
                mongodb('ab_detail')->delete();
                mongodb('invite_user')->delete();
                mongodb('pay_user')->delete();
                mongodb('ab_detail_error')->delete();
                break;
            case 'import_register':
                $users = rep()->user->m()->where('created_at', '>=', $createdAt)->get();
                foreach ($users as $user) {
                    $job = (new ABTestJob('register', $user->id))
                        ->onQueue('ab_test_statics');
                    dispatch($job);
                }
                break;
            case 'import_recharge':
                $trades = rep()->tradePay->m()->where('created_at', '>=', $createdAt)
                    ->where('done_at', '!=', 0)
//                    ->limit($limit)
                    ->get();
                foreach ($trades as $trade) {
                    $job = (new ABTestJob('recharge', $trade->user_id, ['trade_pay_id' => $trade->id]))
                        ->onQueue('ab_test_statics');
                    dispatch($job);
                }
                break;
            case 'init_ab':
                mongodb('ab')->delete();
                mongodb('ab')->insert([
                    'type'                          => 201,
                    'user_count'                    => 0,//注册人数
                    'invited_count'                 => 0,//被邀请人数
                    'invite_count'                  => 0,//发起邀请人数
                    'user_recharge'                 => 0,//充值金额
                    'recharge_user_count'           => 0,//付费人数
                    'recharge_user_percent'         => 0,//付费率
                    'repurchase_user_count'         => 0,//复购人数
                    'repurchase_percent'            => 0,//复购率
                    'invited_recharge'              => 0,//被邀请人数充值金额
                    'invited_recharge_user_count'   => 0,//被邀请用户付费人数
                    'invited_recharge_user_percent' => 0,//被邀请用户付费率
                    'invite_recharge'               => 0,//发起邀请人充值金额
                    'invite_recharge_user_count'    => 0,//发起邀请用户付费人数
                    'invite_recharge_percent'       => 0,//发起邀请用户付费人数付费率
                ]);
                mongodb('ab')->insert([
                    'type'                          => 202,
                    'user_count'                    => 0,//注册人数
                    'invited_count'                 => 0,//被邀请人数
                    'invite_count'                  => 0,//发起邀请人数
                    'user_recharge'                 => 0,//充值金额
                    'recharge_user_count'           => 0,//付费人数
                    'recharge_user_percent'         => 0,//付费率
                    'repurchase_user_count'         => 0,//复购人数
                    'repurchase_percent'            => 0,//复购率
                    'invited_recharge'              => 0,//被邀请人数充值金额
                    'invited_recharge_user_count'   => 0,//被邀请用户付费人数
                    'invited_recharge_user_percent' => 0,//被邀请用户付费率
                    'invite_recharge'               => 0,//发起邀请人充值金额
                    'invite_recharge_user_count'    => 0,//发起邀请用户付费人数
                    'invite_recharge_percent'       => 0,//发起邀请用户付费人数付费率
                ]);
                break;
            case 'init_mongo':
                $dataArrA = $dataArrB = [];
                $dataA    = [
                    ['type' => 201, 'card_level' => 100, 'day_type' => 0, 'discount' => 5, 'count' => 0],
                    ['type' => 201, 'card_level' => 100, 'day_type' => 0, 'discount' => 10, 'count' => 0],
                    ['type' => 201, 'card_level' => 100, 'day_type' => 0, 'discount' => 15, 'count' => 0],
                    ['type' => 201, 'card_level' => 100, 'day_type' => 0, 'discount' => 20, 'count' => 0],
                    ['type' => 201, 'card_level' => 100, 'day_type' => 0, 'discount' => 25, 'count' => 0],
                    ['type' => 201, 'card_level' => 100, 'day_type' => 0, 'discount' => 30, 'count' => 0],
                    ['type' => 201, 'card_level' => 100, 'day_type' => 0, 'discount' => 35, 'count' => 0],
                    ['type' => 201, 'card_level' => 100, 'day_type' => 0, 'discount' => 40, 'count' => 0],
                    ['type' => 201, 'card_level' => 100, 'day_type' => 0, 'discount' => 45, 'count' => 0],
                    ['type' => 201, 'card_level' => 100, 'day_type' => 0, 'discount' => 50, 'count' => 0],
                ];
                foreach ($dataA as $row) {
                    for ($i = 1; $i <= 7; $i++) {
                        $dataArrA[] = [
                            'type' => 201, 'card_level' => $row['card_level'] * $i, 'day_type' => 0, 'discount' => $row['discount'], 'count' => 0
                        ];
                        $dataArrA[] = [
                            'type' => 202, 'card_level' => $row['card_level'] * $i, 'day_type' => 0, 'discount' => $row['discount'], 'count' => 0
                        ];
                    }
                }

                $dataB = [
                    ['type' => 201, 'card_level' => 100, 'day_type' => 1, 'discount' => 0, 'count' => 0],
                    ['type' => 201, 'card_level' => 100, 'day_type' => 2, 'discount' => 0, 'count' => 0],
                    ['type' => 201, 'card_level' => 100, 'day_type' => 3, 'discount' => 0, 'count' => 0],
                    ['type' => 201, 'card_level' => 100, 'day_type' => 4, 'discount' => 0, 'count' => 0],
                    ['type' => 201, 'card_level' => 100, 'day_type' => 5, 'discount' => 0, 'count' => 0],
                    ['type' => 201, 'card_level' => 100, 'day_type' => -1, 'discount' => 0, 'count' => 0],
                ];
                foreach ($dataB as $row) {
                    for ($i = 1; $i <= 7; $i++) {
                        $dataArrB[] = [
                            ['type' => 201, 'card_level' => $row['card_level'] * $i, 'day_type' => $row['day_type'], 'discount' => 0, 'count' => 0],
                        ];
                        $dataArrB[] = [
                            ['type' => 202, 'card_level' => $row['card_level'] * $i, 'day_type' => $row['day_type'], 'discount' => 0, 'count' => 0],
                        ];
                    }
                }
                $data = array_merge($dataArrB, $dataArrA);

                foreach ($data as $row) {
                    mongodb('ab_detail')->insert($row);
                }


                break;
        }

    }
}
