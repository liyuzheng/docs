<?php
/**
 * Created by PhpStorm.
 * User: ailuoy
 * Date: 2019/3/5
 * Time: 上午9:54
 */

namespace App\Foundation\Modules\Pocket;

use App\Pockets\AliYunPocket;
use App\Pockets\AuthPocket;
use App\Pockets\CommonPocket;
use App\Pockets\ConfigJPushPocket;
use App\Pockets\ConfigPocket;
use App\Pockets\DiscountPocket;
use App\Pockets\GoodPocket;
use App\Pockets\InviteRecordPocket;
use App\Pockets\NeteasePocket;
use App\Pockets\NotifyPocket;
use App\Pockets\MemberPocket;
use App\Pockets\PayDataPocket;
use App\Pockets\PrizePocket;
use App\Pockets\PushPocket;
use App\Pockets\SmsAdPocket;
use App\Pockets\SmsPocket;
use App\Pockets\CurrencyPocket;
use App\Pockets\StatDailyActivePocket;
use App\Pockets\StatDailyAppNamePocket;
use App\Pockets\StatDailyConsumePocket;
use App\Pockets\StatDailyInvitePocket;
use App\Pockets\StatDailyMemberPocket;
use App\Pockets\StatDailyNewUserPocket;
use App\Pockets\StatDailyRechargePocket;
use App\Pockets\StatDailyTradePocket;
use App\Pockets\StatPocket;
use App\Pockets\SpamWordPocket;
use App\Pockets\StatSmsRecallPocket;
use App\Pockets\StatUserPocket;
use App\Pockets\SwitchPocket;
use App\Pockets\TaskPocket;
use App\Pockets\TaskPrizePocket;
use App\Pockets\ToolsPocket;
use App\Pockets\UserAuthPocket;
use App\Pockets\UserDestroyPocket;
use App\Pockets\UserPhotoPocket;
use App\Pockets\UserFollowOfficePocket;
use App\Pockets\UserRelationPocket;
use App\Pockets\UserSwitchPocket;
use App\Pockets\UtilPocket;
use App\Pockets\UserPocket;
use App\Pockets\RolePocket;
use App\Pockets\TradePocket;
use App\Pockets\VersionPocket;
use App\Pockets\WalletPocket;
use App\Pockets\UserRolePocket;
use App\Pockets\UserTagPocket;
use App\Pockets\ResourcePocket;
use App\Pockets\UserJobPocket;
use App\Pockets\TradeBuyPocket;
use App\Pockets\TradePayPocket;
use App\Pockets\UserDetailPocket;
use App\Pockets\UserFollowPocket;
use App\Pockets\UserResourcePocket;
use App\Pockets\TradeIncomePocket;
use App\Pockets\UserContactPocket;
use App\Pockets\TradeWithdrawPocket;
use App\Pockets\TradeBalancePocket;
use App\Pockets\BlacklistPocket;
use App\Pockets\CardPocket;
use App\Pockets\AccountPocket;
use App\Exceptions\ServiceException;
use App\Pockets\AdminPocket;
use App\Pockets\TengYuPocket;
use App\Pockets\MongodbPocket;
use App\Pockets\EsImChatPocket;
use App\Pockets\EsPocket;
use App\Pockets\FengKongPocket;
use App\Pockets\EsUserPocket;
use App\Pockets\NeteaseDunPocket;
use App\Pockets\TopicPocket;
use App\Pockets\MomentPocket;
use App\Pockets\EsMomentPocket;
use App\Pockets\DingTalkPocket;
use App\Pockets\AliGreenPocket;
use App\Pockets\WechatTemplateMsgPocket;
use App\Pockets\WgcYunPayPocket;
use App\Pockets\WechatPocket;
use App\Pockets\GreetPocket;
use App\Pockets\YuanZhiPocket;
use App\Pockets\GIOPocket;

/**
 * Class PocketHandle
 * @package App\Foundation\Modules\BasePocket
 *
 * @property CurrencyPocket          currency
 * @property TradeBalancePocket      tradeBalance
 * @property TradeBuyPocket          tradeBuy
 * @property TradeIncomePocket       tradeIncome
 * @property TradePayPocket          tradePay
 * @property TradePocket             trade
 * @property TradeWithdrawPocket     tradeWithdraw
 * @property WalletPocket            wallet
 * @property PayDataPocket           payData
 * @property UtilPocket              util
 * @property MemberPocket            member
 * @property NotifyPocket            notify
 * @property SmsPocket               sms
 * @property UserPocket              user
 * @property UserDetailPocket        userDetail
 * @property RolePocket              role
 * @property UserRolePocket          userRole
 * @property UserTagPocket           userTag
 * @property UserContactPocket       userContact
 * @property ResourcePocket          resource
 * @property UserResourcePocket      userResource
 * @property UserJobPocket           userJob
 * @property UserFollowPocket        userFollow
 * @property AuthPocket              auth
 * @property NeteasePocket           netease
 * @property UserRelationPocket      userRelation
 * @property UserAuthPocket          userAuth
 * @property GoodPocket              good
 * @property BlacklistPocket         blacklist
 * @property CardPocket              card
 * @property CommonPocket            common
 * @property AccountPocket           account
 * @property AliYunPocket            aliYun
 * @property AdminPocket             admin
 * @property TengYuPocket            tengYu
 * @property MongodbPocket           mongodb
 * @property EsImChatPocket          esImChat
 * @property EsPocket                es
 * @property PushPocket              push
 * @property PrizePocket             prize
 * @property TaskPocket              task
 * @property InviteRecordPocket      inviteRecord
 * @property TaskPrizePocket         taskPrize
 * @property ConfigPocket            config
 * @property FengKongPocket          fengkong
 * @property VersionPocket           version
 * @property EsUserPocket            esUser
 * @property NeteaseDunPocket        neteaseDun
 * @property MomentPocket            moment
 * @property TopicPocket             topic
 * @property EsMomentPocket          esMoment
 * @property DingTalkPocket          dingTalk
 * @property SpamWordPocket          spamWord
 * @property AliGreenPocket          aliGreen
 * @property ConfigJPushPocket       configJPush
 * @property StatDailyActivePocket   statDailyActive
 * @property StatDailyConsumePocket  statDailyConsume
 * @property StatDailyInvitePocket   statDailyInvite
 * @property StatDailyMemberPocket   statDailyMember
 * @property StatDailyNewUserPocket  statDailyNewUser
 * @property StatDailyRechargePocket statDailyRecharge
 * @property StatDailyTradePocket    statDailyTrade
 * @property StatPocket              stat
 * @property UserDestroyPocket       userDestroy
 * @property WgcYunPayPocket         wgcYunPay
 * @property UserPhotoPocket         userPhoto
 * @property WechatPocket            wechat
 * @property UserFollowOfficePocket  userFollowOffice
 * @property StatUserPocket          statUser
 * @property StatDailyAppNamePocket  statDailyAppName
 * @property WechatTemplateMsgPocket wechatTemplateMsg
 * @property StatSmsRecallPocket     statSmsRecall
 * @property GreetPocket             greet
 * @property YuanZhiPocket           yuanZhi
 * @property SwitchPocket            switch
 * @property UserSwitchPocket        userSwitch
 * @property DiscountPocket          discount
 * @property ToolsPocket             tools
 * @property GIOPocket               gio
 */
class PocketHandle
{
    protected static $pockets;
    protected static $instance;
    protected static $registerList = array(
        'currency'          => CurrencyPocket::class,
        'tradeBalance'      => TradeBalancePocket::class,
        'tradeBuy'          => TradeBuyPocket::class,
        'tradeIncome'       => TradeIncomePocket::class,
        'tradePay'          => TradePayPocket::class,
        'trade'             => TradePocket::class,
        'tradeWithdraw'     => TradeWithdrawPocket::class,
        'wallet'            => WalletPocket::class,
        'user'              => UserPocket::class,
        'userDetail'        => UserDetailPocket::class,
        'role'              => RolePocket::class,
        'userRole'          => UserRolePocket::class,
        'userTag'           => UserTagPocket::class,
        'userContact'       => UserContactPocket::class,
        'resource'          => ResourcePocket::class,
        'userResource'      => UserResourcePocket::class,
        'userJob'           => UserJobPocket::class,
        'userFollow'        => UserFollowPocket::class,
        'member'            => MemberPocket::class,
        'userRelation'      => UserRelationPocket::class,
        'notify'            => NotifyPocket::class,
        'util'              => UtilPocket::class,
        'sms'               => SmsPocket::class,
        'auth'              => AuthPocket::class,
        'netease'           => NeteasePocket::class,
        'userAuth'          => UserAuthPocket::class,
        'good'              => GoodPocket::class,
        'blacklist'         => BlacklistPocket::class,
        'card'              => CardPocket::class,
        'common'            => CommonPocket::class,
        'account'           => AccountPocket::class,
        'aliYun'            => AliYunPocket::class,
        'payData'           => PayDataPocket::class,
        'admin'             => AdminPocket::class,
        'tengYu'            => TengYuPocket::class,
        'mongodb'           => MongodbPocket::class,
        'esImChat'          => EsImChatPocket::class,
        'es'                => EsPocket::class,
        'push'              => PushPocket::class,
        'prize'             => PrizePocket::class,
        'task'              => TaskPocket::class,
        'taskPrize'         => TaskPrizePocket::class,
        'inviteRecord'      => InviteRecordPocket::class,
        'config'            => ConfigPocket::class,
        'fengkong'          => FengKongPocket::class,
        'version'           => VersionPocket::class,
        'esUser'            => EsUserPocket::class,
        'neteaseDun'        => NeteaseDunPocket::class,
        'moment'            => MomentPocket::class,
        'topic'             => TopicPocket::class,
        'esMoment'          => EsMomentPocket::class,
        'dingTalk'          => DingTalkPocket::class,
        'spamWord'          => SpamWordPocket::class,
        'configJPush'       => ConfigJPushPocket::class,
        'statDailyActive'   => StatDailyActivePocket::class,
        'statDailyConsume'  => StatDailyConsumePocket::class,
        'statDailyInvite'   => StatDailyInvitePocket::class,
        'statDailyMember'   => StatDailyMemberPocket::class,
        'statDailyNewUser'  => StatDailyNewUserPocket::class,
        'statDailyRecharge' => StatDailyRechargePocket::class,
        'statDailyTrade'    => StatDailyTradePocket::class,
        'stat'              => StatPocket::class,
        'userDestroy'       => UserDestroyPocket::class,
        'aliGreen'          => AliGreenPocket::class,
        'wgcYunPay'         => WgcYunPayPocket::class,
        'userPhoto'         => UserPhotoPocket::class,
        'wechat'            => WechatPocket::class,
        'userFollowOffice'  => UserFollowOfficePocket::class,
        'statUser'          => StatUserPocket::class,
        'statDailyAppName'  => StatDailyAppNamePocket::class,
        'wechatTemplateMsg' => WechatTemplateMsgPocket::class,
        'smsAd'             => SmsAdPocket::class,
        'statSmsRecall'     => StatSmsRecallPocket::class,
        'greet'             => GreetPocket::class,
        'yuanZhi'           => YuanZhiPocket::class,
        'switch'            => SwitchPocket::class,
        'userSwitch'        => UserSwitchPocket::class,
        'discount'          => DiscountPocket::class,
        'tools'             => ToolsPocket::class,
        'gio'               => GIOPocket::class,
    );

    protected function __construct()
    {
    }

    protected function __clone()
    {
    }

    /**
     * get singleton
     *
     * @return mixed
     */
    public static function instance()
    {
        if (!static::$instance) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    /**
     * register all
     */
    public static function registerAll()
    {
        foreach (self::$registerList as $name => $class) {
            self::$pockets[$name] = app($class);
        }
    }

    /**
     * register someone
     *
     * @param $name
     */
    public static function register($name)
    {
        self::$pockets[$name] = app(self::$registerList[$name]);
    }

    /**
     * @param $name
     *
     * @return mixed
     * @throws ServiceException
     */
    public function __get($name)
    {
        if (isset(self::$registerList[$name]) && !isset(self::$pockets[$name])) {
            self::register($name);
        } elseif (!isset(self::$pockets[$name])) {
            throw new ServiceException($name . ' Unregistered please add to registerList');
        }

        return self::$pockets[$name];
    }
}
