<?php


namespace App\Mail;


use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    private $code;

    /**
     * VerifyCodeMail constructor.
     *
     * @param $code
     */
    public function __construct($code)
    {
        $this->code = $code;
    }

    public function build()
    {
        $this->from(config('mail.from.address'), '哈哈哈哈')->view('emails.code');
    }
}
