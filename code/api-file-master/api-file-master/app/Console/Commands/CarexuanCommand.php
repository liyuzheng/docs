<?php


namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Foundation\Handlers\Gio\GrowingIO;
use App\Constant\NeteaseCustomCode;
use App\Models\AdminSendNetease;
use App\Models\User;
use App\Jobs\UpdateUserInfoToMongoJob;
use PHPUnit\Util\Exception;

class CarexuanCommand extends Command
{
    protected $signature   = 'carexuan:test {action}';
    protected $description = '刘懿萱测试脚本';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $action = $this->argument('action');
        switch ($action) {
            case 'test':
                $content = $this->read('https://cdn-test.okacea.com/uploads/user/avatar/girls_id1.csv');
                foreach ($content as $item) {
                    $item = intval(trim($item));
                    mongodb('mark_charm_girl')->updateOrInsert(['user_id' => $item]);
                    echo $item . '补充mongo成功' . PHP_EOL;
                }
                break;
            case 'recall_test':
                $userId = $this->ask('请输入要撤回的用户ID');
                pocket()->user->recallUserMsg($userId);
                break;
            case 'send_jpush_msg_to_user':
                $userId = $this->ask('请输入要发送的用户ID');
                $user   = rep()->user->getById($userId);
                pocket()->push->pushToUser($user, 'test');
                break;
        }
    }

    function read($path)
    {
        $file = fopen($path, "r");
        $user = array();
        $i    = 0;
        //输出文本中所有的行，直到文件结束为止。
        while (!feof($file)) {
            $user[$i] = fgets($file);//fgets()函数从文件指针中读取一行
            $i++;
        }
        fclose($file);
        $user = array_filter($user);

        return $user;
    }
}
