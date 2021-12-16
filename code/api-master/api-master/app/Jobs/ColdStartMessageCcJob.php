<?php


namespace App\Jobs;


use App\Foundation\Handlers\Tools;
use App\Models\Resource;
use App\Models\Role;
use App\Models\UserAuth;
use App\Models\UserPhoto;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ColdStartMessageCcJob extends Job
{
    private $callbackData;

    /**
     * AppleIpaValidationJob constructor.
     *
     * @param $callbackData
     */
    public function __construct($callbackData)
    {
        $this->callbackData = $callbackData;
    }

    public function handle()
    {
        $fromUserUUID = $this->callbackData['fromAccount'];
        $toUserUUID   = $this->callbackData['to'];
        $users        = rep()->user->write()->select('id', 'uuid')
            ->whereIn('uuid', [$fromUserUUID, $toUserUUID])->get();
        $fromUser     = $users->where('uuid', $fromUserUUID)->first();
        $toUser       = $users->where('uuid', $toUserUUID)->first();
        if (!$fromUser) {
            dispatch(new GetColdStartUserJob($fromUserUUID, $this->callbackData))
                ->onQueue('get_cold_start_user');
        } elseif (isset($this->callbackData['msgType'])) {
            switch ($this->callbackData['msgType']) {
                case 'TEXT':
                    pocket()->netease->msgSendMsg(
                        $fromUserUUID,
                        $toUserUUID,
                        $this->callbackData['body'],
                        ['option' => ['roam' => true]]
                    );
                    break;
                case 'AUDIO':
                    pocket()->netease->msgSendPointToPoint(
                        $fromUserUUID,
                        $toUserUUID,
                        $this->callbackData['attach'],
                        ['option' => ['roam' => true]],
                        2
                    );
                    break;
                case 'CUSTOM':
                    $attach = json_decode($this->callbackData['attach']);
                    pocket()->netease->msgSendCustomMsg(
                        $fromUserUUID,
                        $toUserUUID,
                        $attach,
                        ['option' => ['roam' => true]]
                    );
                default:
            }
        }
    }
}
