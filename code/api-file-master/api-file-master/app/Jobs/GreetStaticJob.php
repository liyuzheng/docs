<?php


namespace App\Jobs;


class GreetStaticJob extends Job
{
    protected $userId;//被打招呼的用户id|支付的用户id
    protected $type;// greet | recharge

    /**
     * GreetStaticJob constructor.
     *
     * @param $type
     * @param $userId
     */
    public function __construct($type, $userId)
    {
        $this->userId = $userId;
        $this->type   = $type;
    }

    public function handle()
    {
        $greetPay = mongodb('greet_pay')->where('_id', $this->userId)->first();
        switch ($this->type) {
            case 'greet':
                !$greetPay && $this->createGreetPay();
                mongodb('greet_pay')->where('_id', $this->userId)->increment('greet_times');//打招呼次数
                break;
            case 'recharge':
                if (!$greetPay) {
                    return true;
                }
                mongodb('greet_pay')->where('_id', $this->userId)->increment('times_pay');//支付次数
                mongodb('greet_pay')->where('_id', $this->userId)->update([
                    'greet_day_pay' => $greetPay['greet_times']
                ]);

                break;
            default:
                break;
        }

        return true;
    }

    public function createGreetPay()
    {
        mongodb('greet_pay')->insert([
            '_id'           => $this->userId,
            'greet_day_pay' => 0,
            'times_pay'     => 0,
            'greet_times'   => 0,
        ]);
    }
}
