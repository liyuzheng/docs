<?php


namespace App\Jobs;


use App\Foundation\Modules\Pocket\BasePocket;

class SendWeChatTemplateMsgJob extends Job
{
    protected $pocket;
    protected $method;
    protected $args;

    public function __construct(BasePocket $pocket, string $method, array $args)
    {
        $this->pocket = $pocket;
        $this->method = $method;
        $this->args   = $args;
    }

    public function handle()
    {
        call_user_func_array([$this->pocket, $this->method], $this->args);
    }
}
