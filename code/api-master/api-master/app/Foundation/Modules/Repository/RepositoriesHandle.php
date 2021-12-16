<?php

namespace App\Foundation\Modules\Repository;

use App\Repositories\DiscountRepository;
use App\Repositories\GoodRepository;
use App\Repositories\InviteBuildRecordRepository;
use App\Repositories\InviteRecordRepository;
use App\Repositories\MemberRecordRepository;
use App\Repositories\MemberRepository;
use App\Repositories\PayChannelRepository;
use App\Repositories\PayDataRepository;
use App\Repositories\PrizeRepository;
use App\Repositories\ResourceCheckRepository;
use App\Repositories\SfaceRecordRepository;
use App\Repositories\SmsAdRepository;
use App\Repositories\SmsRepository;
use App\Repositories\StatDailyActiveRepository;
use App\Repositories\StatDailyAppNameRepository;
use App\Repositories\StatDailyConsumeRepository;
use App\Repositories\StatDailyInviteRepository;
use App\Repositories\StatDailyMemberRepository;
use App\Repositories\StatDailyNewUserRepository;
use App\Repositories\StatDailyRechargeRepository;
use App\Repositories\StatDailyTradeRepository;
use App\Repositories\SpamWordRepository;
use App\Repositories\StatSmsRecallRepository;
use App\Repositories\StatUserRepository;
use App\Repositories\TaskPrizeRepository;
use App\Repositories\TaskRepository;
use App\Repositories\UnlockPreOrderRepository;
use App\Repositories\UserAbRepository;
use App\Repositories\UserAuthRepository;
use App\Repositories\UserDestroyRepository;
use App\Repositories\UserFollowOfficeRepository;
use App\Repositories\UserPhotoRepository;
use App\Repositories\UserPowerRepository;
use App\Repositories\UserRelationRepository;
use App\Repositories\UserResourceRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserDetailRepository;
use App\Repositories\RoleRepository;
use App\Repositories\UserRoleRepository;
use App\Repositories\UserTagRepository;
use App\Repositories\UserContactRepository;
use App\Repositories\ResourceRepository;
use App\Repositories\UserJobRepository;
use App\Repositories\UserFollowRepository;
use App\Repositories\CurrencyRepository;
use App\Repositories\TradeBalanceRepository;
use App\Repositories\TradeBuyRepository;
use App\Repositories\TradeIncomeRepository;
use App\Repositories\TradePayRepository;
use App\Repositories\TradeRepository;
use App\Repositories\TradeWithdrawRepository;
use App\Repositories\VersionRepository;
use App\Repositories\WalletRepository;
use App\Repositories\BlacklistRepository;
use App\Repositories\MobileBookRepository;
use App\Repositories\PowerRepository;
use App\Repositories\SwitchModelRepository;
use App\Repositories\TagRepository;
use App\Repositories\UserSwitchRepository;
use App\Repositories\ConfigRepository;
use App\Repositories\JobRepository;
use App\Repositories\ReportRepository;
use App\Repositories\UserEvaluateRepository;
use App\Repositories\CardRepository;
use App\Repositories\RegionRepository;
use App\Repositories\WechatRepository;
use App\Repositories\UserReviewRepository;
use App\Repositories\FaceRecordRepository;
use App\Repositories\UserAttrAuditRepository;
use App\Repositories\AdminRepository;
use App\Repositories\DailyRecordRepository;
use App\Repositories\AreaRepository;
use App\Repositories\StatRemainLoginLogRepository;
use App\Repositories\ConfigJpushRepository;
use App\Repositories\UserPhotoChangeLogRepository;
use App\Repositories\UserLookOverRepository;
use App\Repositories\FengkongCheckPicRepository;
use App\Repositories\RepayRepository;
use App\Repositories\RepayDataRepository;
use App\Repositories\MomentRepository;
use App\Repositories\TopicRepository;
use App\Repositories\UserLikeRepository;
use App\Repositories\BannerRepository;
use App\Repositories\ReportFeedbackRepository;
use App\Repositories\OptionRepository;
use App\Repositories\AuthorityRepository;
use App\Repositories\AdminRoleRepository;
use App\Repositories\FacePicRepository;
use App\Repositories\AdminSendNeteaseRepository;
use App\Repositories\WechatTemplateMsgRepository;
use App\Repositories\UserVisitRepository;
use App\Repositories\UserDetailExtraRepository;
use App\Repositories\GreetRepository;
use App\Repositories\MemberPunishmentRepository;
use App\Repositories\TranslateRepository;
use App\Repositories\LoginFaceRecordRepository;

/**
 * Class RepositoriesHandle
 * @package App\Foundation\Services\BaseRepository
 *
 * @property UserRepository               user
 * @property CurrencyRepository           currency
 * @property TradeRepository              trade
 * @property TradeBalanceRepository       tradeBalance
 * @property TradeBuyRepository           tradeBuy
 * @property TradeIncomeRepository        tradeIncome
 * @property TradePayRepository           tradePay
 * @property TradeWithdrawRepository      tradeWithdraw
 * @property WalletRepository             wallet
 * @property BlacklistRepository          blacklist
 * @property MobileBookRepository         mobileBook
 * @property PowerRepository              power
 * @property SwitchModelRepository        switchModel
 * @property TagRepository                tag
 * @property CardRepository               card
 * @property UserFollowRepository         userFollow
 * @property UserSwitchRepository         userSwitch
 * @property UserTagRepository            userTag
 * @property ConfigRepository             config
 * @property ConfigJpushRepository        configJpush
 * @property JobRepository                job
 * @property PayDataRepository            payData
 * @property GoodRepository               good
 * @property UserDetailRepository         userDetail
 * @property MemberRepository             member
 * @property UserRelationRepository       userRelation
 * @property UserJobRepository            userJob
 * @property UserResourceRepository       userResource
 * @property ResourceRepository           resource
 * @property SmsRepository                sms
 * @property UserRoleRepository           userRole
 * @property RoleRepository               role
 * @property UserAuthRepository           userAuth
 * @property UserContactRepository        userContact
 * @property ReportRepository             report
 * @property UserEvaluateRepository       userEvaluate
 * @property RegionRepository             region
 * @property WechatRepository             wechat
 * @property UserReviewRepository         userReview
 * @property FaceRecordRepository         faceRecord
 * @property VersionRepository            version
 * @property UserAttrAuditRepository      userAttrAudit
 * @property AdminRepository              admin
 * @property MemberRecordRepository       memberRecord
 * @property DailyRecordRepository        dailyRecord
 * @property AreaRepository               area
 * @property PrizeRepository              prize
 * @property TaskRepository               task
 * @property InviteRecordRepository       inviteRecord
 * @property TaskPrizeRepository          taskPrize
 * @property ResourceCheckRepository      resourceCheck
 * @property StatRemainLoginLogRepository statRemainLoginLog
 * @property UserPhotoRepository          userPhoto
 * @property UserPhotoChangeLogRepository userPhotoChangeLog
 * @property UserLookOverRepository       userLookOver
 * @property FengkongCheckPicRepository   fengkongCheckPic
 * @property StatDailyActiveRepository    statDailyActive
 * @property StatDailyConsumeRepository   statDailyConsume
 * @property StatDailyInviteRepository    statDailyInvite
 * @property StatDailyMemberRepository    statDailyMember
 * @property StatDailyNewUserRepository   statDailyNewUser
 * @property StatDailyRechargeRepository  statDailyRecharge
 * @property StatDailyTradeRepository     statDailyTrade
 * @property RepayRepository              repay
 * @property RepayDataRepository          repayData
 * @property MomentRepository             moment
 * @property TopicRepository              topic
 * @property UserLikeRepository           userLike
 * @property BannerRepository             banner
 * @property ReportFeedbackRepository     reportFeedback
 * @property OptionRepository             option
 * @property AuthorityRepository          authority
 * @property AdminRoleRepository          adminRole
 * @property InviteBuildRecordRepository  inviteBuildRecord
 * @property SpamWordRepository           spamWord
 * @property FacePicRepository            facePic
 * @property SfaceRecordRepository        sfaceRecord
 * @property DiscountRepository           discount
 * @property UserDestroyRepository        userDestroy
 * @property UserPowerRepository          userPower
 * @property AdminSendNeteaseRepository   adminSendNetease
 * @property UserFollowOfficeRepository   userFollowOffice
 * @property StatUserRepository           statUser
 * @property StatDailyAppNameRepository   statDailyAppName
 * @property WechatTemplateMsgRepository  wechatTemplate
 * @property SmsAdRepository              smsAd
 * @property StatSmsRecallRepository      statSmsRecall
 * @property UserVisitRepository          userVisit
 * @property UserDetailExtraRepository    userDetailExtra
 * @property UnlockPreOrderRepository     unlockPreOrder
 * @property GreetRepository              greet
 * @property UserAbRepository             userAb
 * @property MemberPunishmentRepository   memberPunishment
 * @property TranslateRepository          translate
 * @property PayChannelRepository         payChannel
 * @property LoginFaceRecordRepository    loginFaceRecord
 */
class RepositoriesHandle
{
    protected static $registerList = [
        'userDetail'         => UserDetailRepository::class,
        'role'               => RoleRepository::class,
        'userRole'           => UserRoleRepository::class,
        'userTag'            => UserTagRepository::class,
        'userContact'        => UserContactRepository::class,
        'resource'           => ResourceRepository::class,
        'userResource'       => UserResourceRepository::class,
        'userJob'            => UserJobRepository::class,
        'userFollow'         => UserFollowRepository::class,
        'user'               => UserRepository::class,
        'currency'           => CurrencyRepository::class,
        'trade'              => TradeRepository::class,
        'tradeBalance'       => TradeBalanceRepository::class,
        'tradeBuy'           => TradeBuyRepository::class,
        'tradeIncome'        => TradeIncomeRepository::class,
        'tradePay'           => TradePayRepository::class,
        'tradeWithdraw'      => TradeWithdrawRepository::class,
        'wallet'             => WalletRepository::class,
        'blacklist'          => BlacklistRepository::class,
        'mobileBook'         => MobileBookRepository::class,
        'power'              => PowerRepository::class,
        'switchModel'        => SwitchModelRepository::class,
        'tag'                => TagRepository::class,
        'card'               => CardRepository::class,
        'userSwitch'         => UserSwitchRepository::class,
        'config'             => ConfigRepository::class,
        'configJpush'        => ConfigJpushRepository::class,
        'job'                => JobRepository::class,
        'payData'            => PayDataRepository::class,
        'good'               => GoodRepository::class,
        'member'             => MemberRepository::class,
        'userRelation'       => UserRelationRepository::class,
        'sms'                => SmsRepository::class,
        'userAuth'           => UserAuthRepository::class,
        'report'             => ReportRepository::class,
        'userEvaluate'       => UserEvaluateRepository::class,
        'region'             => RegionRepository::class,
        'wechat'             => WechatRepository::class,
        'userReview'         => UserReviewRepository::class,
        'faceRecord'         => FaceRecordRepository::class,
        'version'            => VersionRepository::class,
        'userAttrAudit'      => UserAttrAuditRepository::class,
        'admin'              => AdminRepository::class,
        'memberRecord'       => MemberRecordRepository::class,
        'dailyRecord'        => DailyRecordRepository::class,
        'area'               => AreaRepository::class,
        'resourceCheck'      => ResourceCheckRepository::class,
        'prize'              => PrizeRepository::class,
        'task'               => TaskRepository::class,
        'taskPrize'          => TaskPrizeRepository::class,
        'inviteRecord'       => InviteRecordRepository::class,
        'statRemainLoginLog' => StatRemainLoginLogRepository::class,
        'userPhoto'          => UserPhotoRepository::class,
        'userPhotoChangeLog' => UserPhotoChangeLogRepository::class,
        'userLookOver'       => UserLookOverRepository::class,
        'fengkongCheckPic'   => FengkongCheckPicRepository::class,
        'statDailyActive'    => StatDailyActiveRepository::class,
        'statDailyConsume'   => StatDailyConsumeRepository::class,
        'statDailyInvite'    => StatDailyInviteRepository::class,
        'statDailyMember'    => StatDailyMemberRepository::class,
        'statDailyNewUser'   => StatDailyNewUserRepository::class,
        'statDailyRecharge'  => StatDailyRechargeRepository::class,
        'statDailyTrade'     => StatDailyTradeRepository::class,
        'repay'              => RepayRepository::class,
        'repayData'          => RepayDataRepository::class,
        'moment'             => MomentRepository::class,
        'topic'              => TopicRepository::class,
        'userLike'           => UserLikeRepository::class,
        'banner'             => BannerRepository::class,
        'reportFeedback'     => ReportFeedbackRepository::class,
        'option'             => OptionRepository::class,
        'authority'          => AuthorityRepository::class,
        'adminRole'          => AdminRoleRepository::class,
        'inviteBuildRecord'  => InviteBuildRecordRepository::class,
        'spamWord'           => SpamWordRepository::class,
        'facePic'            => FacePicRepository::class,
        'sfaceRecord'        => SfaceRecordRepository::class,
        'discount'           => DiscountRepository::class,
        'userDestroy'        => UserDestroyRepository::class,
        'userPower'          => UserPowerRepository::class,
        'adminSendNetease'   => AdminSendNeteaseRepository::class,
        'userFollowOffice'   => UserFollowOfficeRepository::class,
        'statUser'           => StatUserRepository::class,
        'statDailyAppName'   => StatDailyAppNameRepository::class,
        'wechatTemplate'     => WechatTemplateMsgRepository::class,
        'smsAd'              => SmsAdRepository::class,
        'statSmsRecall'      => StatSmsRecallRepository::class,
        'userVisit'          => UserVisitRepository::class,
        'userDetailExtra'    => UserDetailExtraRepository::class,
        'unlockPreOrder'     => UnlockPreOrderRepository::class,
        'greet'              => GreetRepository::class,
        'userAb'             => UserAbRepository::class,
        'memberPunishment'   => MemberPunishmentRepository::class,
        'translate'          => TranslateRepository::class,
        'payChannel'         => PayChannelRepository::class,
        'loginFaceRecord'    => LoginFaceRecordRepository::class,
    ];
    /**
     * Example of the current class
     * @var $this ;
     */
    protected static $instance;
    /**
     * All repository sets
     * @var array
     */
    protected static $repositories = [];

    /**
     * The protected constructor prohibits the creation of an
     * instance of the current class
     *
     * ContextHandler constructor.
     */
    protected function __construct()
    {
    }

    /**
     * The protected clone magic method forbids the current class
     * from being cloned
     */
    protected function __clone()
    {
    }

    /**
     * Returns an instance of the current class
     *
     * @return $this;
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
            self::$repositories[$name] = app($class);
        }
    }

    /**
     * register someone
     *
     * @param $name
     */
    public static function register($name)
    {
        self::$repositories[$name] = app(self::$registerList[$name]);
    }

    /**
     * Get the corresponding repository object according to different repository names
     *
     * @param $name
     *
     * @return mixed
     * @throws ServiceException
     */
    public function __get($name)
    {
        if (!isset(self::$repositories[$name])) {
            self::register($name);
        } elseif (!isset(self::$registerList[$name])) {
            throw new ServiceException($name . ' Unregistered please add to registerList');
        }

        return self::$repositories[$name];
    }
}
