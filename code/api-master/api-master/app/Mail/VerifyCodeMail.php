<?php


namespace App\Mail;


use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    private $code;
    private $option;

    /**
     * VerifyCodeMail constructor.
     *
     * @param $code
     * @param $option
     */
    public function __construct($code, $option)
    {
        $this->code   = $code;
        $this->option = $option;
    }

    public function build()
    {
        return $this->subject('小圈验证码')->view('emails.code',
            ['code' => $this->code, 'option' => $this->option]);
    }
}
