<?php


namespace App\Mail;


use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Class InviteWarnMail
 * @package App\Mail
 */
class InviteWarnMail extends Mailable
{
    use Queueable, SerializesModels;

    private $uuid;
    private $count;

    /**
     * InviteWarnMail constructor.
     *
     * @param $uuid
     * @param $count
     */
    public function __construct($uuid, $count)
    {
        $this->uuid  = $uuid;
        $this->count = $count;
    }

    /**
     * @return InviteWarnMail
     */
    public function build()
    {
        $notice = sprintf('用户ID: %d, 今日邀请用户大于等于5人, 当前邀请人数%d人, 请及时处理',
            $this->uuid, $this->count);
        return $this->subject('小圈邀请警报')->view('emails.plaintext', ['txt' => $notice]);
    }
}
