<?php


namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class JsyCommand extends Command
{
    protected $signature   = 'jsy';
    protected $description = '贾思远执行脚本';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $action = $this->choice('请选择功能', [
            'test'                   => '通用测试方法',
            'array' => '数组测试方法',
            'sync' => '同步魅力女生'
        ]);
        switch ($action) {
            case 'test':
                break;

            case 'array':
                $arr = [1, 2, 3, 4, 5, 6];
                dd(array_random($arr, 3));
                break;

            case 'sync':
                $this->get_charm_girl_arr();
                break;
        }
    }
    // 获取城市数组
    public function get_region_arr($sql)
    {
        $data = DB::select($sql);
        $region_arr = [];
        foreach ($data as $key => $value) {
            $region_arr[] = $value->region;
        }
        return $region_arr;
    }
    // 获取魅力女生
    public function get_user_by_region($region_arr)
    {
        $user_arr = [];
        foreach ($region_arr as $key => $value) {
            $sql = "SELECT user_detail.user_id FROM `user` JOIN user_detail ON `user`.id = user_detail.user_id WHERE `user`.role = 'auth_user,charm_girl,user' and user_detail.region='$value'";
            echo $sql."\n\r";
            $user_arr_by_region = DB::select($sql);
            $user_arr[$value] = [];
            foreach ($user_arr_by_region as $k => $v) {
                $user_arr[$value][] = $v->user_id;
            }
            // dd($user_arr);
        }
        
        return $user_arr;
    }
    // 查出需要同步魅力女生的信息
    public function get_charm_girl_arr()
    {
        $start_time = time();
        // 第一种情况
        // 魅力女生人数小于100的城市  全部同步
        $region_sql = "SELECT user_detail.region,count(*) FROM `user` JOIN user_detail ON `user`.id = user_detail.user_id WHERE `user`.role = 'auth_user,charm_girl,user' GROUP BY user_detail.region HAVING COUNT(*) <=100 ORDER BY count(*) desc";
        // 获取城市数组
        $region_arr = $this->get_region_arr($region_sql);
        // dd($region_arr);
        // 获取城市对应的魅力女生
        $user_arr = $this->get_user_by_region($region_arr);
        // var_dump($user_arr);
        
        // // 第二种情况
        // // 魅力女生人数大于100的城市
        // // 活跃度最近30天 人数小于100的
        // $region_sql = "SELECT user_detail.region ,count(*) FROM `user` JOIN user_detail ON `user`.id = user_detail.user_id WHERE `user`.role = 'auth_user,charm_girl,user' and `user`.active_at > UNIX_TIMESTAMP(now())-30*86400 GROUP BY user_detail.region HAVING count(*) < 100 ORDER BY count(*) desc";
        // $region_arr = DB::select($region_sql);
        // // 按照活跃度倒排的方式取够100
        // $sql = "SELECT user.id,user_detail.region FROM `user` JOIN user_detail ON `user`.id = user_detail.user_id WHERE `user`.role = 'auth_user,charm_girl,user' and `user`.active_at > UNIX_TIMESTAMP(now())-30*86400 where user_detail.region=$";
        $endtime = time();
        echo (($endtime-$start_time)/60).'分';
    }
}
