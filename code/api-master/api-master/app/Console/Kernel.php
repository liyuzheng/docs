<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\DevelopCommand::class,
        \App\Console\Commands\ValidationUserRenewCommand::class,
        \App\Console\Commands\FixUserRoleCommand::class,
        \App\Console\Commands\FillCoordinateCommand::class,
        \App\Console\Commands\FixUserWaterMarkCommand::class,
        \App\Console\Commands\DailyStatisticsCommand::class,
        \App\Console\Commands\FixPositionCommand::class,
        \App\Console\Commands\FixCharmGrilCommand::class,
        \App\Console\Commands\TimingPushToManUserCommand::class,
        \App\Console\Commands\CityCommand::class,
        \App\Console\Commands\EsToolsCommand::class,
        \App\Console\Commands\UpdateDownloadURLCommand::class,
        \App\Console\Commands\SendIosAllManUsersNewTopUpCommand::class,
        \App\Console\Commands\TestInvitedUserCommand::class,
        \App\Console\Commands\UpdateActiveToMongoCommand::class,
        \App\Console\Commands\FixDataCommand::class,
        \App\Console\Commands\DiscountMessageCommand::class,
        \App\Console\Commands\UpdateUserMapCommand::class,
        \App\Console\Commands\FixUserPhotoCommand::class,
        \App\Console\Commands\FillCityIdToEsCommand::class,
        \App\Console\Commands\FixEsMemberCommand::class,
        \App\Console\Commands\UpdateEsMemberCommand::class,
        \App\Console\Commands\SendFestivalMessageCommand::class,
        \App\Console\Commands\PingxxOrderCompensateCommand::class,
        \App\Console\Commands\StatCommand::class,
        \App\Console\Commands\StatExpireMemberCommand::class,
        \App\Console\Commands\FillLocToMysqlCommand::class,
        \App\Console\Commands\UpdateUserDestroyCommand::class,
        \App\Console\Commands\CarexuanCommand::class,
        \App\Console\Commands\FixEsUserCreatedAtCommand::class,
        \App\Console\Commands\UpdateCharmGirlDoneAtCommand::class,
        \App\Console\Commands\SendCharmActiveRemindCommand::class,
        \App\Console\Commands\CollectCommand::class,
        \App\Console\Commands\UpdateBlackListDestroyCommand::class,
        \App\Console\Commands\UpdateStatUserFirstTopUpSecondsCommand::class,
        \App\Console\Commands\StatCreateCommand::class,
        \App\Console\Commands\WechatCommand::class,
        \App\Console\Commands\TimingPushCharmGirlCommand::class,
        \App\Console\Commands\VersionPublishCommand::class,
        \App\Console\Commands\AddVisitedToNewUserCommand::class,
        \App\Console\Commands\CheckPreOrderCommand::class,
        \App\Console\Commands\SetEsGreetCountZeroCommand::class,
        \App\Console\Commands\UpdateEsGreetCountCommand::class,
        \App\Console\Commands\SendActiveRemindCommand::class,
        \App\Console\Commands\DiscountNoticeCommand::class,
        \App\Console\Commands\QueueListenCommand::class,
        \App\Console\Commands\RefreshBlackUserCommand::class,
        \App\Console\Commands\FixABTestStatics::class,
        \App\Console\Commands\ListenSmsCount::class,
        \App\Console\Commands\MsgStaticCommand::class,
        \App\Console\Commands\ClearSmsCommand::class,
        \App\Console\Commands\RouteMapCommand::class,
        \App\Console\Commands\SendErrorUserMsgCommand::class,
        \App\Console\Commands\JsyCommand::class,
        \App\Console\Commands\SendSmsCommand::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //
    }
}
