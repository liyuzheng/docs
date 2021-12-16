<?php


namespace App\Jobs;

/**
 * 云账户请求
 *
 * Class GiveMembersJob
 * @package App\Jobs
 */
class WgcJob extends Job
{
    private $tradeWithdrawId;

    /**
     * WgcJob constructor.
     *
     * @param $tradeWithdrawId
     */
    public function __construct($tradeWithdrawId)
    {
        $this->tradeWithdrawId = $tradeWithdrawId;
    }


    public function handle()
    {
        $result = pocket()->wgcYunPay->bankcard($this->tradeWithdrawId);
    }
}
