<?php


namespace App\Tools;


class SeaTranslation
{
    public function getHomeStrings()
    {
        return [
            'home_tab_active' => '活跃',
            'home_tab_new'    => '新入'
        ];
    }

    public function getMainStrings()
    {
        return [
            'bottom_tab_main'    => '主页',
            'bottom_tab_dynamic' => '动态',
            'bottom_tab_message' => '消息',
            'bottom_tab_mine'    => '我的',
        ];
    }

    public function getStrings()
    {
        return [
            "app_name"        => "小圈",
            "transname"       => "小圈",
            "permission_desc" => "小圈需要获得以下权限才能为您提供服务",
            "contact_desc"    => "开启屏蔽后手机联系人无法在小圈里查看到你",
            "contact_desc1"   => "开启屏蔽后手机联系人无法在小圈里查看到你",
            "feed_back_desc"  => "请尽量描述完整，我们将认真核实您的举报，共同维护小圈APP的良好环境。",
            "remark_desc"     => "添加时备注“小圈App”会加速通过呦",
            "snap_desc"       => "小圈app",


            "about_us" => "关于",


            "account_settings"                                          => "账号设置",
            "hand_lock"                                                 => "手势锁",
            "logoff_account"                                            => "注销账号",
            "prompt"                                                    => "提示",
            "asa_logoff_erase_confirm"                                  => "注销后账户所有信息将会抹除，确认注销？",
            "think_again"                                               => "再想想",
            "yes"                                                       => "是",
            "asa_14_restore"                                            => "即日起您有14天撤销操作时间，14天内再次登录小圈APP即认为撤销  确认注销？",
            "close_notification"                                        => "关闭通知",
            "asa_no_notification_will_be_received_when_it_is_turned_on" => "打开后将接收不到通知",


            "aid_active_sort_more_active"                  => "女生列表按照活跃优先排序\n多多活跃会有更多男生看到你",
            "i_know"                                       => "知道了",
            "aid_welcome_comeback_open_app_more_exposure"  => "欢迎回来，多打开小圈活跃\n才可以获得更多曝光哦~",
            "ok"                                           => "好的",
            "aid_if_more_people_feedback_cannot_search_wx" => "多人反馈你的微信搜索不到、添加未成\n功，管理员隐藏了你的微信，前往\n“我的”→“设置”页面取消隐藏",
            "aid_stealth_mode_charm_vip"                   => "魅力VIP才可隐身模式\n还有更多专属特权等你使用～",
            "about_charm_vip"                              => "了解魅力VIP",
            "become_charm_vip"                             => "成为魅力VIP享特权",
            "aid_vip_stealth_mode"                         => "VIP用户才可设置隐身模式\n还有更多专属特权等你使用～",
            "about_vip"                                    => "了解VIP",
            "become_vip"                                   => "成为VIP享特权",
            "charm_vip_filter_vip_boys"                    => "魅力VIP才可筛选VIP男生\n还有更多专属特权等你使用～",
            "charm_vip_filter_vip_boys_2"                  => "魅力VIP才可筛选VIP男生",
            "protected_privacy_private_chat_count"         => "为保护用户隐私\n您今日还剩余%1\$d次私聊用户的权限",
            "get_it_continue_to_view"                      => "知道了，继续查看",
            "private_chat_count_become_charm_vip"          => "您每天只可私聊%1\$d位用户\n成为魅力VIP每天可私聊%2\$d位哦～",
            "up_to_three_updates_per_day"                  => "每日最多发布三条动态哦~",
            "dynamic_once_become_to_vip"                   => "您每天只可发布一条动态\n成为魅力VIP每天可发布三条哦~",
            "up_to_one_news_post_per_day"                  => "每日最多发布一条动态哦~",
            "become_top_vip_send_dynamic"                  => "成为VIP即可发布动态\n获得更多曝光",
            "charm_vip_locked_vip_boys_more_power"         => "魅力VIP才可查看VIP男生\n还有更多专属特权等你使用~",
            "vip_locked_newer_girl_more_power"             => "VIP才可查看新入女生\n还有更多专属特权等你使用",
            "only_charm_vip_locked_me_more_permission"     => "魅力VIP才可查看谁看过我\n还有更多专属特权等你使用",
            "vip_look_who_looked_me"                       => "VIP才可查看谁看过我\n还有更多专属特权等你使用~",


            "select_country_region" => "选择国家/地区",


            "not_reviewed"                            => "未过审",
            "face_auth_failed_because_photo_question" => "照片因：五官不清晰或未露全脸没过审",
            "try_changing_a_picture"                  => "换一张照片试试呢？",
            "cancel"                                  => "取消",
            "upload_again"                            => "重新上传",


            "wait_for_auth"                        => "等待审核",
            "failed_reason"                        => "失败原因",
            "re_certification"                     => "重新认证",
            "please_wait_patiently_during"         => "审核中，请耐心等待\n我们将于24小时内给您出示结果",
            "auth_failed_please_submit_info_again" => "认证失败，请重新提交审核资料",


            "blacklist" => "黑名单",


            "glamour_certification" => "魅力认证",
            "next_step"             => "下一步",


            "charming_girl_certification"                                                                         => "魅力女生认证",
            "we_will_protect_your_privacy"                                                                        => "我们会保护你的隐私，联系方式只展示给审核人员和付费后的vip男生。",
            "input_your_we_chat_number"                                                                           => "请输入您的微信号",
            "please_upload_the_we_chat_qr_code_matching_with_the_above_we_chat_for_customer_service_verification" => "请上传与上方微信匹配的微信二维码以便客服核实\n匹配不通过将不会通过哦~",
            "add_contact_account"                                                                                 => "添加联系方式",
            "picture_upload_failed_please_try_again"                                                              => "图片上传失败，请重试",
            "current_we_chat_number"                                                                              => "当前：",
            "modify_we_chat_number"                                                                               => "修改微信",
            "picture_selection_failed_please_try_again"                                                           => "图片选择失败，请重试",
            "incomplete_information"                                                                              => "信息未填写完整",
            "no_storage_permission_is_given_to_select_pictures"                                                   => "没有赋予存储权限，无法选择图片。",


            "complete_details_required"             => "完善详细资料（必填）",
            "fill_in_carefully_and_show_your_charm" => "认真填写，展现你的魅力~",
            "choose_height"                         => "选择身高",
            "choose_weight"                         => "选择体重",
            "select_industry"                       => "选择行业",
            "enter_your_profile"                    => "输入您的个人简介",
            "please_complete_the_information"       => "请填写完整的信息",


            "network_exception_click_retry"          => "网络异常，点击重试",
            "more_information_required"              => "更多资料（必填）",
            "fill_in_all_attract_more_men"           => "全部填写，吸引更多男士～",
            "choose_your_body"                       => "选择身材",
            "choose_emotional_state"                 => "选择感情状态",
            "choose_whether_to_have_children_or_not" => "选择有无孩子",
            "choose_education_background"            => "选择学历",
            "choose_annual_income"                   => "选择年收入",
            "choose_whether_to_smoke_or_not"         => "选择是否抽烟",
            "choose_whether_to_drink_or_not"         => "选择是否饮酒",


            "please_upload_two_five_most_beautiful_photos"            => "请上传2-5张最美的照片",
            "please_upload_my_real_photos_more_easily_favored_by_men" => "请上传本人真实照片，更容易受到男士青睐。",
            "network_exception_please_try_again"                      => "网络异常，请重试",
            "network_exception_please_try_again_dian"                 => "网络异常，请重试。",
            "is_delete_this_picture"                                  => "是否删除该张图片",
            "please_upload_two_five_photo_albums"                     => "请上传2-5张相册照片",
            "please_replace_the_failed_photos_in_the_album"           => "请更换相册中未通过的照片",


            "official_account"                                                    => "关注公众号",
            "the_result_of_the_audit_will_be_informed_by_the_official_account"    => "(审核结果将会在公众号告知)",
            "use_we_chat_to_scan_the_qr_code"                                     => "使用微信扫描下方二维码，并关注公众号后，返回这里点击验证，即可提交认证申请",
            "verification"                                                        => "验证",
            "save_qr_code"                                                        => "保存二维码",
            "qr_code_loading_failed_please_close_the_pop_up_window_and_try_again" => "二维码加载失败，请关闭弹窗重试",


            "vip"                         => "VIP",
            "charm_vip"                   => "魅力VIP",
            "congratulations_on_becoming" => "恭喜成为",


            "male"                                 => "男",
            "female"                               => "女",
            "age"                                  => "岁",
            "he_is_mysterious_left_nothing_behind" => "他很神秘，什么都没有留下~",


            "member_to_time"            => "会员至：",
            "renewal_member"            => "续费会员",
            "enjoy_vip_free_privileges" => "尊享VIP免费特权",
            "stealth_dynamic_and_more"  => "隐身、动态等更多功能",
            "activate_now"              => "立即开通",


            "become_free_to_unlock_wechat"                                => "成为免费解锁微信",
            "unlocking"                                                   => "解锁",
            "edit"                                                        => "修改",
            "under_review"                                                => "审核中",
            "modification_of_we_chat_needs_customer_service_verification" => "修改微信需客服核实",
            "after_authentication_we_chat_can_be_set_up"                  => "认证后即可设置微信",
            "successfully_unlock_we_chat"                                 => "成功解锁微信",
            "become"                                                      => "成为",
            "unlock_we_chat"                                              => "解锁微信",
            "see"                                                         => "查看",


            "mail_list"                                               => "通讯录",
            "when_the_mobile_phone_opens_the_access_to_read_contacts" => "手机开启读取联系人权限，您就可以选择屏蔽手机联系人了",
            "open_permission"                                         => "开启权限",


            "privacy_settings"                                  => "隐私设置",
            "block_mobile_contacts"                             => "屏蔽手机联系人",
            "start_browsing"                                    => "开始浏览",
            "failed_to_grant_permission_unable_to_use_function" => "赋予权限失败，无法使用功能。",


            "detail" => "详情",


            "you_don_not_had_send_dynamic" => "还没有发布过动态哦～",


            "cannot_get_nearby_dynamics_without_opening_location_permission" => "没有开启位置权限，无法获取附近动态。",


            "click_to_try_again" => "点击重试",


            "only_girls"   => "只看女生",
            "only_boys"    => "只看男生",
            "all_dynamics" => "全部动态",
            "boy"          => "男生",
            "girl"         => "女生",
            "all"          => "全部",


            "edit_profile"                                 => "修改资料",
            "save"                                         => "保存",
            "completion"                                   => "完成度",
            "nickname"                                     => "昵称",
            "please_fill_in"                               => "请填写",
            "personal_profile"                             => "个人简介",
            "he_is_very_mysterious_did_not_write_anything" => "他很神秘，什么都没有写～",
            "more_about"                                   => "更多介绍",
            "hobby_and_interest"                           => "兴趣爱好",
            "album"                                        => "相册",
            "birthday"                                     => "生日",
            "city"                                         => "城市",
            "height"                                       => "身高",
            "weight"                                       => "体重",
            "engaged_in_occupation"                        => "从事职业",
            "please_select"                                => "请选择",
            "please_fill_in_the_nickname"                  => "请填写昵称",
            "everything_is_not_changed"                    => "未修改内容",
            "photo_is_not_auth"                            => "检测到您的头像不符合真人认证，确认修改后将失去认证标识",
            "confirm_modification"                         => "确认修改",
            "figure"                                       => "身材",
            "emotional_state"                              => "感情状态",
            "children"                                     => "孩子",
            "education"                                    => "学历",
            "annual_income"                                => "年收入",
            "smoke"                                        => "抽烟",
            "drink"                                        => "饮酒",
            "add_add_interest"                             => "+添加兴趣",


            "evaluate"                           => "评价",
            "anonymous_evaluation"               => "匿名评价",
            "please_report_serious_problems"     => "严重问题请举报",
            "my_comments"                        => "我的评价",
            "comprehensive_score"                => "综合评分",
            "you_have_not_received_a_review_yet" => "你还没有收到评价",
            "evaluation_not_selected"            => "未点选评价",
            "evaluation_submitted"               => "评价已提交",


            "submit_feedback"                                  => "提交反馈",
            "ah_failed_to_upload_the_picture_please_try_again" => "啊哦 图片上传失败，请重试",
            "feedback"                                         => "意见反馈",
            "problem_description"                              => "问题描述",
            "please_describe_your_questions_and_comments"      => "请描述您的问题和意见",
            "picture_optional"                                 => "图片（选填）",
            "anonymous_report"                                 => "匿名举报",
            "reasons_for_reporting"                            => "举报理由",
            "picture"                                          => "图片",
            "report_trends"                                    => "举报动态",
            "please_describe_your_reasons_for_reporting"       => "请描述您的举报理由",
            "something_the_matter_ask_immediately"             => "有问题？立即询问",
            "online_service"                                   => "在线客服",
            "please_input_the_content_of_complaint"            => "请输入要投诉的内容",
            "please_upload_at_least_one_picture"               => "请上传至少一张图片",
            "the_problem_has_been_fed_back"                    => "问题已反馈",


            "basic_information"                                                         => "基本资料",
            "upload_clear_facial_features_let_more_users_find_you"                      => "上传清晰的五官头像，让更多用户发现你",
            "the_avatar_has_not_passed_the_human_authentication_please_upload_it_again" => "头像未通过真人认证，请重新上传",
            "make_sure_i_have_a_picture_before_i_pass_the_audit"                        => "确保本人头像，才能通过审核",
            "location_permissions"                                                      => "位置权限",
            "please_close_the_page_and_try_again"                                       => "资料填写有误，请关闭页面重试",
            "update_avatar_successfully"                                                => "更新头像成功",
            "you_are_currently_in"                                                      => "您当前在：",
            "getting"                                                                   => "获取中...",
            "please_open_location_permission"                                           => "请开启位置权限",
            "please_click_application_permission_and_open_it"                           => "请点击 【应用权限】 并将其开启",
            "unable_to_upload_image_without_storage_permission"                         => "没有赋予存储权限，无法上传图片。",


            "the_list_of_female_students" => "女生列表按照活跃排序，越活越约靠前~",


            "online"                  => "在线",
            "the_distance_is_unknown" => "距离未知",


            "forget_the_password"                               => "忘记密码",
            "please_enter_the_verification_code"                => "请输入验证码",
            "please_enter_a_new_password"                       => "请输入新密码",
            "please_confirm_the_new_password_again"             => "请再次确认新密码",
            "confirm_and_login"                                 => "确认并登录",
            "send_verification_code"                            => "发送验证码",
            "resend_captcha"                                    => "重新发送验证码",
            "the_verification_code_has_been_sent_to"            => "验证码已通过短信发送至",
            "the_password_needs_six_twelve_digits_or_english"   => "。密码需要6-12个数字或英文",
            "resend_in_seconds"                                 => "秒后可重新发送",
            "reset_password_successfully"                       => "重置密码成功",
            "verification_code_error"                           => "验证码错误",
            "please_input_a_password"                           => "请输入密码",
            "please_set_a_password_greater_than_six_characters" => "请设置大于6位字符的密码",
            "the_two_passwords_are_inconsistent"                => "两次输入密码不一致",


            "delete"             => "删除",
            "burn_after_reading" => "阅后即焚(查看",
            "burn_in_seconds"    => "秒后焚毁)",
            "red_envelope_video" => "红包视频",
            "diamond_unlock"     => "钻石解锁",
            "confirm_deletion"   => "确认删除",
            "video"              => "视频",
            "photo_deleted"      => "照片已删除",


            "burn_photos_immediately_after_reading"  => "阅后即焚照片",
            "press_and_hold_the_screen_to_view"      => "按住屏幕查看",
            "the_photo_was_burned"                   => "照片已焚毁",
            "failed_to_load_photos_please_try_again" => "加载照片失败，请重试",


            "please_choose_your_gender"                       => "请选择你的性别",
            "the_gender_cannot_be_changed_after_confirmation" => "确认性别后不可更改",
            "confirm_selection"                               => "确认选择",
            "i_want_to_change_it"                             => "我要更改",
            "wrong_gender_selected_please_try_again"          => "选择的性别有误，请重试",
            "operation_successful"                            => "操作成功",
            "please_select_gender"                            => "请选择性别",


            "secret_script_of_small_circle"                                                                          => "小圈秘籍",
            "open_more_small_circles_and_stay_active_to_increase_exposure_to_more_men"                               => "多打开小圈，保持活跃可以增加曝光\n让更多男士看到你～",
            "how_to_increase_exposure"                                                                               => "如何增加曝光？",
            "when_you_need_to_hide_we_chat_from_boys_or_hide_it_from_boys_you_can_go_to_my_settings_page_to_operate" => "需要对男生隐藏微信或对男生隐身时，\n可前往“我的”→“设置”页面操作~",
            "how_to_hide_we_chat_or_stealth"                                                                         => "如何隐藏微信或隐身？",
            "if_the_small_circle_is_not_opened_for_a_long_time_it_will_reduce_the_sort_and_exposure"                 => "若长时间未打开小圈活跃，将会\n降低排序，减少曝光。",
            "this_will_reduce_the_exposure"                                                                          => "这样会减少曝光！",
            "if_you_are_repeatedly_reported_by_users"                                                                => "被用户多次举报微信搜索不到、\n添加不通过、消息不回复，将会\n取消魅力女生身份！",
            "this_will_be_punished"                                                                                  => "这样会受到惩罚！",
            "read_the_above_tips_carefully_and_click_know_to_finish"                                                 => "认真阅读以上提示内容并点击“知道了”才能点击完成",
            "complete"                                                                                               => "完成",


            "the_mobile_phone_is_not_registered" => "该手机号未注册小圈APP快去注册吧～",
            "register_now"                       => "立即注册",


            "choose_your_hobbies"        => "选择你的兴趣爱好",
            "not_selected"               => "未选择",
            "choose_up_to_six_interests" => "最多选择六个兴趣爱好",


            "you_are_using_stealth_mode" => "您正在使用隐身模式，",
            "click_here"                 => "点击这里",
            "close_now"                  => "立即关闭",


            "more_boys_will_like_you" => "个人资料完成80%以上会有更多男生喜欢你哦～",
            "later"                   => "稍后",
            "improve_information"     => "完善资料",


            "improve_your_information" => "完善你的资料",
            "your_nickname"            => "你的昵称",
            "your_birthday"            => "你的生日",
            "invitation_code_optional" => "邀请码（选填）",


            "height_and_weight" => "身高/体重",


            "failed_to_load_click_to_try_again" => "加载失败，点击重试",


            "become_free_to_unlock_we_chat" => "成为免费解锁微信",


            "invite_benefits"           => "邀请福利",
            "receive"                   => "领取",
            "exclusive_invitation_code" => "专属邀请码",
            "click_copy"                => "点击复制",
            "reward_rules"              => "奖励规则",
            "invite_users"              => "邀请用户",
            "become_a_member"           => "是否成为会员",
            "invitation_time"           => "邀请时间",


            "choosing_a_career"                  => "选择职业",
            "you_can_only_choose_one_occupation" => "只能选择一种职业哦～",


            "i_like"  => "我喜欢",
            "like_me" => "喜欢我",


            "cancel_hand_lock" => "取消手势锁",
            "reset_hand_lock"  => "重置手势锁",
            "forget_hand_lock" => "忘记手势",


            "please_input_mobile_phone_number"            => "请输入手机号",
            "login_with_password"                         => "使用密码登录",
            "login"                                       => "登录",
            "sms_verification_code_login_or_registration" => "短信验证码登录/注册",
            "the_verification_code_has_been_sent_by_sms"  => "验证码已通过短信发送",


            "start_exploring"                         => "开始探索",
            "professional_dating_software_for_single" => "为单身男女所打造的\n专业交友软件",


            "elite_men_is_certification"                          => "精英男士认证",
            "certification_now"                                   => "立即认证",
            "upload_my_avatar_girls_are_more_assured_to_meet_you" => "上传本人头像，女生更放心与你见面~",
            "please_make_sure_your_head_is_clear"                 => "请确保头像清晰，五官无遮挡",
            "abandon_the_real_person_certification"               => "放弃真人认证",
            "in_order_to_protect_your_privacy"                    => "为保护您的隐私，您的信息只有认证过的\n女生可以看到，请放心认证。",


            "private_letter" => "私信",


            "mysterious_planet" => "神秘星球",


            "free_8_times_to_say_hello" => "免费赠送8次打招呼，点击下方按钮，一键发送",
            "one_click_greeting"        => "一键打招呼",


            "message" => "消息",


            "my"                                     => "我的",
            "wallet"                                 => "钱包",
            "my_albums_and_videos"                   => "我的相册与视频",
            "upload_pictures_or_video"               => "上传相册 / 视频",
            "there_are_no_photos_for_the_time_being" => "暂无照片\n快传最美的照片、视频，吸引男士的青睐吧！",
            "invite_gifts"                           => "邀请福利",
            "invite_users_to_get_members"            => "邀请用户领会员",
            "view_benefits"                          => "查看福利",


            "my_dynamic"   => "我的动态",
            "release_news" => "发布动态",


            "participate_in_the_topic" => "参与话题",


            "burn_after_reading_click_and_look" => "阅后即焚\n\n点击查看",


            "allow_access_to_notification_rights"          => "允许访问“通知权限”？",
            "you_don_not_have_permission_to_open_push_yet" => "你还没有开推送权限，开启后\n即可收到精彩",
            "talk_later"                                   => "以后再说",
            "open"                                         => "开启",


            "this_software_is_strictly_prohibited" => "本软件严禁利用本软件进行淫秽色情、诈骗、赌博、传销等违法违规以及违反注册协议、隐私政策的行为，否则将依法处理。如有利用本软件以约谈、送礼等理由进行色诱、私下不当交易等行为，请谨慎判断，以防人身或财产损失甚至违法违规。本软件声明，本软件仅为用户提供内容储存空间，不对用户发布的任何信息或者利用本软件账号从事的任何行为承担法律责任。用户的一切行为应当自行承担法律后果。本软件无法且不会对因用户行为而导致的任何损失或损害承担责任。",
            "password_login"                       => "密码登录",


            "choose_the_payment_method" => "请选择支付方式",


            "your_album_has_not_been_uploaded_yet"       => "你的相册还未上传",
            "rich_photo_album_can_get_more_men_is_favor" => "丰富相册可获得更多男士的青睐~",
            "not_now"                                    => "暂不",
            "set_up_now"                                 => "立即设置",


            "report" => "举报",


            "privacy_and_blacklist" => "隐私和黑名单",


            "user_privacy_protocol" => "用户隐私协议",
            "agree_and_continue"    => "同意并继续",


            "user_agreement_and_privacy_policy" => "用户协议和隐私政策",
            "confirm"                           => "确定",


            "you_have_bound_the_small_circle_official_account" => "你已绑定过小圈公众号，若不想继续同步消息，可点击下方按钮解除绑定～",
            "unbinding"                                        => "解绑",
            "not_for_the_time_being"                           => "暂不处理",
            "publish"                                          => "发布",
            "please_don_not_publish_pornographic"              => "记录此刻心情\n请勿发布色情，涉政等违反国家法律规定，以及侵害他人合法利益的内容",


            "successful_purchase_of_diamonds" => "钻石购买成功",


            "recharge" => "充值",
            "balance"  => "余额：",


            "what_kind_of_relationship_are_you_looking_for"                  => "你要寻求什么关系？",
            "fill_in_supplementary_reasons_optional"                         => "填写补充理由(选填)",
            "picture_required"                                               => "图片(必填)",
            "please_provide_effective_screenshot_for_customer_service_audit" => "请提供有效截图，方便客服审核",


            "please_enter_the_content_of_the_report" => "请输入举报内容",
            "successful_report"                      => "举报成功",


            "only_twenty_four_hours_to_unlock_we_chat" => "解锁微信只展示24小时",


            "set_your_password"                             => "设置你的密码",
            "the_password_is_six_twelve_english_or_numbers" => "密码为6 - 12个英文或数字",
            "please_set_your_password"                      => "请设置你的密码",


            "setting"                            => "设置",
            "logout"                             => "退出登录",
            "user_agreement_and_privacy_policy2" => "《用户协议》和《隐私政策》",


            "edit2"                    => "编辑",
            "please_input_the_content" => "请输入内容",


            "he_praised_me" => "ta赞了我",
            "i_like_it"     => "我赞了ta",


            "vip_can_unlock_310_times_per_month"               => "VIP每月最高解锁310次",
            "this_charming_girl_has_set_up_privacy_protection" => "这位魅力女生设置了隐私保护请先私聊沟通后再添加联系方式",


            "go_to_see" => "去看看",


            "view_we_chat"     => "查看微信",
            "understanding_ta" => "了解Ta",
            "evaluate_ta"      => "评价Ta",


            "immediate_evaluation" => "立即评价",


            "profile" => "资料",
            "dynamic" => "动态",


            "upload_video"                        => "上传视频",
            "use"                                 => "使用",
            "red_envelope_video_5_yuan_to_unlock" => "红包视频(5元解锁)",


            "remove" => "移除",


            "official_real_person_authentication" => "官方真人认证",


            "like" => "喜欢",


            "self" => "本人",


            "money_flag"              => "¥",
            "discount_for_renewal_20" => "续费限时八折",


            "buy_at_most" => "最多购买",


            "xiao_quan_vip"          => "小圈VIP",
            "vip_for_charming_girls" => "魅力女生专属VIP",
            "record"                 => "记录",


            "get_vip_privileges" => "获取VIP特权",
            "get_membership"     => "获取会员",


            "the_offer_will_be_available_at" => "优惠将在",
            "due_after"                      => "后到期",


            "my_income_yuan"   => "我的收益(元)",
            "withdrawal"       => "提现",
            "balance_diamonds" => "余额(钻)",


            "people_who_have_seen_me" => "看过我的人",


            "using_we_chat_scanning_the_qr_code" => "使用微信扫描二维码，并关注公众号后，不会错过小圈消息哦～",


            "the_girl_has_passed_the_official_real_person_certification" => "该女生已通过官方真人认证，照片真实可放心交友。",
            "the_app_is_out_of_date"                                     => "App过期。",
            "system_prompt"                                              => "系统提示",
            "sorry_your_credit_is_running_low"                           => "余额不足",
            "oh_no_data"                                                 => "阿哦，没有数据～",
            "at_present_there_is_no_city_for_the_time_being"             => "当前城市暂时没有",
            "you_can_not_praise_yourself"                                => "不能给自己点赞～",
            "please_upload_your_avatar"                                  => "请上传头像",
            "please_choose_a_birthday"                                   => "请选择生日",
            "there_is_no_invitation_record"                              => "还没有邀请记录\n快去邀请好友得VIP吧",
            "go_and_invite_members_for_cash"                             => "还没有邀请记录\n快去邀请会员得现金吧",
            "save_invitation_qr_code"                                    => "保存邀请二维码",
            "copied"                                                     => "已复制",
            "there_is_no_claim"                                          => "没有可领取的",
            "successfully_received"                                      => "领取成功",
            "invitation_code_generating"                                 => "邀请码生成中，请稍后尝试。",
            "please_open_the_storage_permission"                         => "请开启存储权限后再保存图片",

            "saved_to_phone"                                                 => "已保存至手机 =>",
            "no"                                                             => "否",
            "wrong_password"                                                 => "密码错误",
            "please_set_a_six_twelve_character_password"                     => "请设置6 - 12位字符的密码",
            "enter_your_verification_code"                                   => "输入你的验证码",
            "please_enter_the_correct_mobile_phone_number"                   => "请输入正确的手机号码",
            "incorrect_input_of_verification_code"                           => "验证码输入有误",
            "real_person_authentication_passed"                              => "真人认证通过",
            "failed_to_authenticate_click_replace"                           => "认证失败点击更换",
            "please_allow_permission_to_use_the_function_normally"           => "请允许权限以正常使用功能",
            "authentication_failed_please_try_again"                         => "认证失败，请重试",
            "the_current_app_version_is_too_low"                             => "当前App版本过低。",
            "update_now"                                                     => "立即更新",
            "free_of_charge"                                                 => "免费赠送",
            "to_say_hello_click_the_button_below_and_send_it_with_one_click" => "次打招呼，点击下方按钮，一键发送",
            "in_order_to_avoid_disturbing_too_much"                          => "为避免打扰过多，对方回复后才可以继续沟通~",
            "alipay_payment"                                                 => "支付宝支付",
            "we_chat_payment"                                                => "微信支付",
            "you_need_to_pay"                                                => "您需要支付",
            "please_fully_read_and_understand"                               => "请充分阅读并理解《用户协议》和《隐私政策》",
            "thank_you_for_using"                                            => "感谢您使用",
            "more_relevant_laws_and_regulations_require"                     => "更具相关法律规定要求我司制定了",
            "individual"                                                     => "个",
            "cancellation_of_payment"                                        => "取消支付",
            "payment_failed"                                                 => "支付失败",
            "failed_to_get_recharge_information"                             => "获取充值信息失败，请关闭重试",
            "order_information_may_be_delayed_refresh"                       => "订单信息可能有延迟，是否刷新？",
            "payment_successful"                                             => "支付成功",
            "order_payment_failed"                                           => "订单支付失败！",
            "you_have_successfully_purchased"                                => "您已成功购买",
            "diamonds"                                                       => "钻石",
            "charming_girl_exclusive_customer_service"                       => "魅力女生专属客服请勿泄露",
            "please_note_when_adding"                                        => "添加时请备注",
            "charming_girl"                                                  => "魅力女生”呦",
            "copy_we_chat_successfully"                                      => "复制微信成功",
            "no_micro_signal_can_be_found"                                   => "搜索不到微信号？",
            "report_immediately"                                             => "立即举报",
            "cover"                                                          => "被",
            "every_time"                                                     => "每次",
            "after_unlocking_you_can_report_once"                            => "解锁后才可举报一次哦~",
            "in_order_to_avoid_the"                                          => "为避免女生离您过远，体验不佳请打开位置权限",
            "open_now"                                                       => "立即打开",
            "vip_can_be_set_to_stealth_mode"                                 => "魅力VIP才可设置隐身模式",
            "to_protect_the_privacy"                                         => "为保护魅力女生隐私\n您今日还剩余3次私聊用户的权限",
            "i_see_keep_checking"                                            => "知道了，继续查看",
            "you_can_only_chat_with_twenty"                                  => "您每天只可以私聊20个用户\n成为魅力VIP每天不限人数哦~",
            "you_can_only_post_one"                                          => "您每天只可发布一条动态\n成为魅力VIP每天可发布三条哦~",
            "become_a_vip_to_release_news"                                   => "成为VIP即可发布动态\n获得更多曝光~",
            "please_find_it_in_the"                                          => "请在打开的页面中找到",
            "permission"                                                     => "权限",
            "location_permission"                                            => "位置权限",
            "turn_it_on"                                                     => "将其打开",
            "consume"                                                        => "消耗",
            "chat_now"                                                       => "立即聊天",
            "get_her_at_once"                                                => "立刻撩她",
            "diamond_unlock_private_chat"                                    => "钻石解锁私聊",
            "if_the_girl_does_not_reply_within"                              => "24小时内未得到女生回复，钻石将退回你的钱包",
            "free_unlocking_with_vip_privileges"                             => "使用VIP特权免费解锁",
            "private_chat_unlocked_successfully"                             => "私聊解锁成功",
            "private_chat_unlocking_is"                                      => "您和女生距离超过100km，为保证体验请和女生商议过后再添加联系方式。",
            "check_now"                                                      => "立刻查看",
            "we_chat_unlocked_successfully"                                  => "微信解锁成功",
            "you_have_successfully_unlocked"                                 => "您已经成功解锁",
            "it_wechat"                                                      => "的微信",
            "show_sincerity_to_the_other_party"                              => "向对方表达诚意\n解锁私聊需要消耗",
            "network_exception_please_click_to_try_again"                    => "网络异常，请点击重试",
            "to_protect_girls_from_harassment"                               => "为保护女生不被骚扰，需消耗钻石解锁",
            "diamond_unlock_we_chat"                                         => "钻石解锁微信",
            "view_now"                                                       => "立即查看",
            "we_chat_or_private_chat_unlocked_successfully"                  => "微信 / 私聊解锁成功",
            "free_unlock_privilege"                                          => "免费解锁特权",
            "the_number_of_unlocks_has"                                      => "今日解锁次数已耗尽。",
            "vip_privilege_used_today"                                       => "今日VIP特权已使用",
            "second"                                                         => "次",
            "please_try_again"                                               => "请重试",
            "invited"                                                        => "已邀请",
            "people_reduce"                                                  => "人，立减",
            "the_discount_time_is_up"                                        => "折扣优惠时间已到，活动结束！",
            "privilege"                                                      => "特权",
            "hour"                                                           => "小时",
            "minute"                                                         => "分",
            "second2"                                                        => "秒",
            "want_to_add_her_personal_we_chat"                               => "想添加她的私人微信？获取VIP立即查看她的微信",
            "unlimited_chat"                                                 => "无限畅聊",
            "get_vip_to_chat_with_her"                                       => "获取VIP可和她无限畅聊！",
            "view_information"                                               => "查看资料",
            "want_to_see_more_about_girls"                                   => "想要查看更多女生资料？获取VIP特权即可无限查看",
            "new_girl"                                                       => "新入女生",
            "today_many_new_high"                                            => "今日新入好多优质小姐姐，获取VIP特权立即查看～",
            "who_has_seen_me"                                                => "谁看过我",
            "which_girls_are_peeking_at_me"                                  => "哪些女生在偷偷看我？获取VIP特权马上知道！",
            "daily_news"                                                     => "每日动态",
            "want_to_attract_girls_attention"                                => "想要吸引女生注意？每日发布动态，扩大交友圈子～",
            "burn_after_reading_2"                                           => "阅后即焚",
            "double_the_viewing_time"                                        => "阅后即焚图片查看时长翻倍！",
            "stealth_mode"                                                   => "隐身模式",
            "when_inconvenient"                                              => "不方便的时候，打开隐身模式，防止打扰",
            "noble_status"                                                   => "尊贵身份",
            "exclusive_vip_logo_display"                                     => "专属VIP标识展示，彰显尊贵身份～",
            "view_vip"                                                       => "查看VIP",
            "accurate_screening_of_vip"                                      => "精准筛选VIP用户，结识大方爽快的男士～",
            "not_enough_chats"                                               => "聊天次数不够？获取VIP每天可与",
            "a_man_chatting"                                                 => "位男士畅聊",
            "increase_exposure"                                              => "增加曝光",
            "exposure_increases"                                             => "曝光度增加，吸引更多男士注意，和你搭讪～",
            "get_charm_vip_can_post_three"                                   => "获取魅力VIP每天可发布3条动态，吸引他的注意！",
            "who_is_peeking_at_me_h_q_vip"                                   => "谁在偷偷看我？获取VIP特权马上知道！",
            "get_the_charm_vip_and_modify"                                   => "获取魅力VIP每天都可以修改一次资料哦～",
            "exclusive_charming"                                             => "专属魅力VIP标识展示，彰显尊贵身份～",
            "end_of_activity_time"                                           => "活动时间结束",
            "picture_selection_failed"                                       => "图片选择失败",
            "please_input_the_text"                                          => "请输入正文内容",
            "your_submission_has_been"                                       => "您提交的动态已提交审核",
            "delete_this_picture"                                            => "删除该图片？",
            "storage_permission_is_not"                                      => "存储权限未授权，无法选择图片",
            "acquisition_failed"                                             => "获取失败",
            "getting2"                                                       => "正在获取...",
            "click_to_select_topic"                                          => "点击选择话题",
            "thumbs"                                                         => "点赞",
            "say_hi"                                                         => "打招呼",
            "just_released"                                                  => "刚刚发布",
            "do_you_want_to_delete_this_dynamic"                             => "是否要删除该动态？",
            "you_can_not_praise_yourself_2"                                  => "不能给自己点赞～",
            "unknown"                                                        => "未知",
            "you_do_not_have_permission"                                     => "没有开启位置权限，无法获取附近的人。",
            "your_data_is_being_audited"                                     => "您的资料正在审核中，审核成功后才能进行此操作",
            "nearby"                                                         => "附近",
            "please_select_simulation_location"                              => "请选择模拟位置",
            "the_list_will_be_refreshed"                                     => "修改定位成功,列表将自动刷新。",
            "modification_failed"                                            => "修改失败，请重试。",
            "clear_gesture_lock_succeeded"                                   => "清除手势锁成功",
            "please_set_gesture_lock_greater_than_three_steps"               => "请设置大于3步的手势锁",
            "please_input_gesture_lock"                                      => "请输入手势锁",
            "too_many_errors"                                                => "错误次数过多，请重新登录",
            "input_error"                                                    => "输入错误请重试",
            "link_at_least_four_points"                                      => "最少链接4个点，请重新输入",
            "please_input_gesture_lock_again"                                => "请再次输入手势锁",
            "set_successfully"                                               => "设置成功",
            "inconsistent_with_last_gesture"                                 => "与上次手势不一致请重新输入",
            "are_you_sure_to_delete_this_chat"                               => "确认删除该聊天吗？",
            "you_give"                                                       => "你给",
            "etc"                                                            => "等",
            "someone_said_hello"                                             => "个人打过招呼",
            "unlock_to_see_who_is_peeking_at_you"                            => "解锁看看谁偷偷看了你",
            "system"                                                         => "系统",
            "the_data_can_be_modified"                                       => "通过魅力女生认证后才可修改资料",
            "charm_vip_can_only_modify"                                      => "魅力vip每天只能修改一次资料~",
            "invite_users_to_receive"                                        => "邀请用户领红包",
            "modification_of_we_chat"                                        => "修改微信需客服核实",
            "you_do_not_have_album_rights"                                   => "未拥有相册权限，请授权",
            "upload_photos"                                                  => "上传照片",
            "up_to_seven_albums_can"                                         => "相册最多上传七张，请删除后再次上传",
            "at_most_one_video"                                              => "视频最多上传一个，请删除后再次上传",
            "successful_purchase"                                            => "购买成功",
            "in_the_video_audit"                                             => "视频审核中，审核通过后用户即可看到~",
            "after_the_certification"                                        => "认证后 女生更放心和你见面",
            "your_contact_information"                                       => "您的联系方式正在审核中，请稍后查看",
            "already_auth"                                                   => "已经认证",
            "tianjiashibeizhu"                                               => "添加时请备注",
            "nv_shen"                                                        => "女神",
            "yudaowent"                                                      => "遇到问题点击这里",
            "bangdinggzh"                                                    => "绑定公众号",
            "huoquzuixin"                                                    => "获取最新下载地址和同步消息到微信",
            "bangdinghou"                                                    => "绑定后私信消息可同步到微信",
            "shurushoujih"                                                   => "请输入手机号码",
            "qingshuru"                                                      => "请输入用户UID",
            "lianxifangs"                                                    => "联系方式",
            "nskf"                                                           => "女神客服",
            "kfzs"                                                           => "客服助手",
            "rzzx"                                                           => "认证中心",
            "wtfk"                                                           => "问题反馈",
            "lxkf"                                                           => "联系客服",
            "czyh"                                                           => "查找用户",
            "yshhmddgd"                                                      => "隐私和黑名单等更多",
            "ycwxdgd"                                                        => "隐藏微信等更多",
            "shipxzsb"                                                       => "视频选择失败，请重试",
            "tpbcz"                                                          => "图片不存在",
            "tpxzsb"                                                         => "图片下载失败",
            "wlycfstpsb"                                                     => "网络异常，发送图片失败",
            "flyztp"                                                         => "发来一张照片",
            "yjlhyh"                                                         => "已经拉黑用户",
            "jtzddzhcs"                                                      => "今天主动打招呼次数已经达到上限拉~",
            "wbhdfys"                                                        => "为保护对方隐私，你不可以查看资料~",
            "myfytxlqx"                                                      => "没有赋予通讯录权限，无法使用功能。",
            "qingzhishaoxuanzeyizhong"                                       => "请至少选择一种理由",
            "qingzhishaoshanghcuanyizhang"                                   => "请至少上传一张图片",
            "fuyuquanxianshibai"                                             => "赋予权限失败，无法使用功能",
            "zhaqsz"                                                         => "账号安全设置",
            "qdytcndzh"                                                      => "确定要退出您的账户？",
            "yszc"                                                           => "隐私政策",
            "yhxy"                                                           => "用户协议",
            "ys"                                                             => "隐身",
            "dkhyhwfzsylb"                                                   => "打开后用户将无法在首页列表中看到你",
            "ycwx"                                                           => "隐藏微信",
            "dkhyhjwf"                                                       => "打开后用户将无法查看你的联系方式",
            "yycwx"                                                          => "已隐藏微信",
            "yhjwfck"                                                        => "用户将无法查看你的微信，只能发起私聊，请及时查看私聊哦~",
            "jzmnqdl"                                                        => "禁止模拟器登录",
            "wjsgd"                                                          => "我解锁过的",
            "jsgwd"                                                          => "解锁过我的",
            "jssj"                                                           => "解锁时间：",
            "nmtzkck10"                                                      => "您每天只可查看10次魅力女生资料\r\n成为VIP可不限次数哦",
            "wbhmlnsys"                                                      => "为保护魅力女生隐私\r\n您今日还剩余",
            "cck"                                                            => "次查看",
            "mtbxckcs"                                                       => "每天不限查看次数",
            "bkyjszj"                                                        => "不可以解锁自己的联系方式哦~",
            "cztkl"                                                          => "操作太快啦~",
            "czwc"                                                           => "操作完成",
            "yxh"                                                            => "已喜欢",
            "zwpl"                                                           => "暂无评论",
            "zhpf"                                                           => "综合评分",
            "qxxh"                                                           => "取消喜欢",
            "qxlh"                                                           => "取消拉黑",
            "lh"                                                             => "拉黑",
            "znjbjsgndr"                                                     => "只能举报解锁过您的人",
            "siliao"                                                         => "私聊",
            "bkyslzjo"                                                       => "不可以私聊自己哦~",
            "mcjshck"                                                        => "每次解锁后才可举报一次哦～",
            "mcbjshc"                                                        => "每次被解锁后才可举报一次哦～",
            "znjbnjsgdr"                                                     => "只能举报您解锁过的人",
            "hbsp"                                                           => "红包视频",
            "zsjs"                                                           => "钻石解锁",
            "zgspszl"                                                        => "这个视频设置了红包视频 需要消耗",
            "zsjssp"                                                         => "钻石解锁视频",
            "jscg"                                                           => "解锁成功",
            "mt"                                                             => "每天",
            "tiao"                                                           => "条",
            "bhys"                                                           => "保护隐私",
            "zsbs"                                                           => "专属标识",
            "sxns"                                                           => "筛选男生",
            "zkvip"                                                          => "只看VIP",
            "sjgc"                                                           => "时间更长",
            "grsf"                                                           => "个人身份",
            "aqbm"                                                           => "安全保密",
            "gdtq"                                                           => "更多特权",
            "jqqd"                                                           => "敬请期待",
            "ljkt"                                                           => "立即开通",
            "xf"                                                             => "续费",
            "hyyxqz"                                                         => "会员有效期至：",
            "yywlyysxndvip"                                                  => "由于网络原因，刷新您的VIP信息失败，是否重试？",
            "day"                                                            => "天",
            "qxzczxx"                                                        => "请选择充值选项",
            "bctpsb"                                                         => "保存图片失败",
            "bctpcg"                                                         => "保存图片成功",
            "myfyccqx"                                                       => "没有赋予存储权限，无法保存图片",
            "pndf_xieyi"                                                     => "请务必审慎阅读、充分理解“用户协议”和“隐私政策”各条款式，包括但不限于：为了向您提供及时通讯、资料查看等务，我们需要收集您的设备信息、操作日志等个人信息。您可以在设置中查看、变更、删除个人信息并管理你的授权。可阅读",
            "yhxy2"                                                          => "《用户协议》",
            "he"                                                             => "和",
            "yszc2"                                                          => "《隐私政策》",
            "ljxxxx"                                                         => "了解详细信息。如您同意，请点击“同意”开始接受我们的服务。",
            "qgxtyhzdl"                                                      => "请勾选同意后再登录",
            "djdljdbty"                                                      => "点击登录即代表同意",
            "xzjdbty"                                                        => "选中即代表同意",
            "btysq"                                                          => "并同意授权",
            "bjhmyjdl"                                                       => "本机号码一键登录",
            "have"                                                           => "有",
            "sqw"                                                            => "上千位",
            "tcnstszx"                                                       => "同城女生同时在线",
            "dlhkj"                                                          => "登录后可见",
            "qtsjhmdl"                                                       => "其他手机号码登录",
            "nzzzxzh"                                                        => "您正在注销账号，确认登录将认为取消注销操作",
            "qrdl"                                                           => "确认登录",
            "dfhfnl"                                                         => "对方回复你啦！请畅快聊天吧~",
            "kgwdr"                                                          => "看过我的人",
            "tpyxh"                                                          => "图片已销毁",
            "xxyxh"                                                          => "消息已销毁。",
            "xxwfswc"                                                        => "消息未发送完成，请稍后重试",
            "zhyblh"                                                         => "账号已被拉黑，请联系管理员",
            "zhcxyc"                                                         => "账号出现异常，请联系管理员",
            "zhzqtsbdl"                                                      => "账号已在其他设备登录",
            "tup"                                                            => "图片",
            "ship"                                                           => "视频",
            "yyxx"                                                           => "语音消息",
            "wz"                                                             => "位置",
            "wj"                                                             => "文件",
            "tztx"                                                           => "通知提醒",
            "jqrxx"                                                          => "机器人消息",
            "rzxx"                                                           => "认证消息",
            "jsxx"                                                           => "解锁消息",
            "wzxx"                                                           => "未知消息",
            "qtxx"                                                           => "其他消息",
            "dfhfnl2"                                                        => "对方回复你啦！请畅快聊天吧~",
            "wbmdrgd"                                                        => "为避免打扰过多，对方回复后才可以继续沟通~",
            "mn"                                                             => "美女",
            "kqtstz"                                                         => "开启推送通知，不错过",
            "dxxo"                                                           => "的消息哦~",
            "qkq"                                                            => "去开启",
            "ddzfsb"                                                         => "订单支付失败，请重试。",
            "wzczdm"                                                         => "未知充值代码。",
            "wjcdsjllq"                                                      => "未检测到手机浏览器，无法打开网页",
            "ndzlzzshz"                                                      => "您的资料正在审核中，审核成功后才能进行此操作",
            "wbmnslngy"                                                      => "为避免女生离您过远，体验不佳请打开位置权限",
            "bcsbqsh"                                                        => "保存失败，请稍后重试",
            "ybc"                                                            => "已保存",
            "xydkxtdwkg"                                                     => "需要打开系统定位开关",
            "yyhqjqddwfw"                                                    => "用于获取精确的定位服务来为您提供附近的人等功能",
            "yhjfck3m"                                                       => "阅后即焚(查看3秒后焚毁)",
            "cclqcs"                                                         => "出错了，请重试",
            "wzcwqcs"                                                        => "未知错误，请重试",
            "bzcdff"                                                         => "不支持的方法，请升级到最新版本。",
            "yuan"                                                           => "元",
        ];
    }

    public function getStringsPtr()
    {
        return [
            'app_name'    => 'Library',
            'loading'     => '正在加载中...',
            'load_failed' => '加载失败，请点我重试',
            'load_end'    => '没有更多数据',
        ];
    }

    public function getStringsUikit()
    {
        return [

            "empty"                    => "",
            "ok"                       => "确定",
            "cancel"                   => "取消",
            "clear_empty"              => "清空",
            "iknow"                    => "知道了",
            "search"                   => "搜索",
            "remove"                   => "移除",
            "add"                      => "添加",
            "create"                   => "新建",
            "without_content"          => "暂无",
            "network_is_not_available" => "网络连接失败，请检查你的网络设置",
            "open"                     => "开启",
            "close"                    => "关闭",
            "menu"                     => "菜单",
            "now_allow_space"          => "不能含有空格",
            "readed"                   => "已读",
            "picture"                  => "图片",
            "succeed"                  => "成功",
            "fail"                     => "失败",
            "error"                    => "错误",


            "input_panel_photo"                 => "图 片",
            "input_panel_video"                 => "视 频",
            "input_panel_location"              => "位 置",
            "input_panel_take"                  => "拍 摄",
            "im_choose_pic"                     => "请选择JPG PNG BMP GIF格式的图片文件",
            "repeat_download_message"           => "重新下载?",
            "repeat_send_has_blank"             => "重 发",
            "repeat_send_message"               => "重发消息?",
            "reply_has_blank"                   => "回 复",
            "reply_with_message"                => "回复 %s: %s",
            "reply_with_amount"                 => "%s 条回复",
            "copy_has_blank"                    => "复 制",
            "delete_has_blank"                  => "删 除",
            "voice_to_text"                     => "转文字",
            "forward"                           => "转发",
            "forward_to_person"                 => "转发到个人",
            "forward_to_team"                   => "转发到群组",
            "withdrawn_msg"                     => "撤 回",
            "withdrawn_msg_count_notify"        => "撤回(通知计入未读数)",
            "withdrawn_msg_not_count_notify"    => "撤回(通知不计入未读数)",
            "multiple_selection"                => "多 选",
            "delete_msg_self"                   => "单向删除消息",
            "delete_msg_self_success"           => "单向删除成功",
            "delete_msg_self_failed"            => "单向删除失败",
            "save"                              => "保存",
            "main_msg_list_delete_chatting"     => "删除该聊天",
            "main_msg_list_sticky_on_top"       => "置顶该聊天",
            "main_msg_list_clear_sticky_on_top" => "取消置顶",
            "save_to_device"                    => "保存到手机",
            "picture_save_fail"                 => "图片保存失败",
            "picture_save_to"                   => "图片已保存到手机",
            "search_join_team"                  => "搜索加入群组",
            "file_transfer_state_downloaded"    => "已下载",
            "file_transfer_state_undownload"    => "未下载",
            "trans_voice_failed"                => "语音转化失败",
            "team_invalid_tip"                  => "您已退出该群",
            "normal_team_invalid_tip"           => "您已退出该讨论组",
            "team_send_message_not_allow"       => "您已不在该群，不能发送消息",
            "send"                              => "发送",
            "unsupport_title"                   => "无法显示该内容",
            "unsupport_desc"                    => "客户端版本不支持该内容",
            "revoke_failed"                     => "发送时间超过2分钟的消息，不能被撤回",
            "pic_and_video"                     => "图片和视频",
            "black_list_send_tip"               => "消息已发送，但对方拒收",
            "confirm_forwarded_to"              => "确认转发给",
            "confirm_forwarded"                 => "确认转发",


            "record_audio"           => "按住 说话",
            "record_audio_end"       => "松开  结束",
            "sdcard_not_exist_error" => "请插入SD卡",
            "recording_error"        => "录音失败，请重试",
            "recording_init_failed"  => "初始化录音失败",
            "recording_cancel"       => "手指上滑，取消发送",
            "timer_default"          => "00:00",
            "recording_cancel_tip"   => "松开手指，取消发送",
            "recording_max_time"     => "录音达到最大时间，是否发送？",
            "play_ready"             => "准备播放",
            "play_complete"          => "播放完成",
            "playing"                => "播放中",


            "gallery_invalid"                     => "你的手机没有图库程序",
            "sdcard_not_enough_head_error"        => "SD卡被拔出或存储空间不足，无法保存头像",
            "sdcard_not_enough_error"             => "存储空间不足，无法保存此次多媒体消息",
            "download_video"                      => "正在下载视频",
            "download_video_fail"                 => "视频下载失败,请重试",
            "look_video_fail"                     => "无法播放该视频",
            "look_video_fail_try_again"           => "暂时无法播放视频，请重试",
            "surface_has_not_been_created"        => "Surface尚未创建完成",
            "surface_created"                     => "Surface创建完成",
            "video_play"                          => "视频播放",
            "video_record"                        => "视频录制",
            "video_record_symbol"                 => "REC",
            "video_record_begin"                  => "开始录制",
            "connect_vedio_device_fail"           => "无法连接视频设备 ，请稍候再试",
            "capture_video_size_in_kb"            => "视频文件大小为: %1\$dKB,",
            "capture_video_size_in_mb"            => "视频文件大小为: %1$.2fMB,",
            "is_send_video"                       => "是否发送该视频？",
            "start_camera_to_record_failed"       => "启动摄像头录制视频失败",
            "stop_fail_maybe_stopped"             => "停止失败，可能已经停止",
            "video_exception"                     => "视频文件异常",
            "im_choose_video_file_size_too_large" => "视频文件过大，系统限制为20MB",
            "im_choose_video"                     => "请选择3GP MP4格式的视频文件",
            "video_record_short"                  => "录制视频太短",
            "download_progress_description"       => "%1\$s（%2\$s/%3\$s）",


            "contact_selector" => "联系人选择器",


            "team_need_authentication"     => "需要身份验证",
            "team_allow_anyone_join"       => "允许任何人加入",
            "team_not_allow_anyone_join"   => "不允许任何人申请加入",
            "team_admin_invite"            => "管理员邀请",
            "team_everyone_invite"         => "所有人邀请",
            "team_admin_update"            => "管理员修改",
            "team_everyone_update"         => "所有人修改",
            "team_invitee_need_authen"     => "需要验证",
            "team_invitee_not_need_authen" => "不需要验证",
            "team_notify_mute"             => "不提醒任何消息",
            "team_notify_all"              => "提醒所有消息",
            "team_notify_manager"          => "只提醒管理员消息",
            "team_nickname"                => "群昵称",
            "member_invitor"               => "邀请人",
            "team_nickname_none"           => "未填写",
            "no_invitor"                   => "主动入群",
            "team_identity"                => "身份",
            "remove_member"                => "移出本群",
            "team_member_info"             => "成员信息",
            "team_admin"                   => "管理员",
            "team_creator"                 => "群主",
            "team_member"                  => "群成员",
            "update_success"               => "保存成功",
            "update_failed"                => "保存失败, code:%d",
            "no_permission"                => "没有权限",
            "set_team_admin"               => "设为管理员",
            "cancel_team_admin"            => "取消管理员",
            "team_member_remove_confirm"   => "确定要将其移出群组么?",
            "team_annourcement"            => "群公告",
            "invite_member"                => "邀请成员",
            "team_name"                    => "群名称",
            "team_introduce"               => "群介绍",
            "team_extension"               => "扩展示例",
            "click_set"                    => "点击设置",
            "create_advanced_team"         => "创建高级群",
            "reach_team_member_capacity"   => "成员数量不能超过%1$\d",
            "team_authentication"          => "身份验证",
            "team_invite"                  => "邀请他人权限",
            "team_info_update"             => "群资料修改权限",
            "team_invitee_authentication"  => "被邀请人身份验证",
            "create_team"                  => "创建群",
            "over_team_member_capacity"    => "邀请失败，成员人数上限为%1\$d人",
            "over_team_capacity"           => "创建失败，创建群数量达到限制",
            "create_team_failed"           => "创建失败",
            "create_team_success"          => "创建成功",
            "team_announce_title"          => "标题",
            "team_announce_content"        => "公告内容",
            "team_announce_notice"         => "请输入群公告标题",
            "my_team_card"                 => "我的群名片",
            "team_introduce_hint"          => "点击填写群介绍",
            "team_extension_hint"          => "点击修改扩展内容",
            "team_announce_hint"           => "点击填写群公告",
            "team_notification_config"     => "消息提醒",
            "team_transfer_without_member" => "没有可转移的群成员",
            "team_invite_members_success"  => "群成员邀请已发出",
            "team_invite_members_failed"   => "群成员邀请失败",
            "team_transfer_success"        => "群转移成功",
            "team_transfer_failed"         => "群转移失败",
            "quit_team_success"            => "您已退群",
            "quit_team_failed"             => "退群失败",
            "quit_normal_team_success"     => "您已经退出讨论组",
            "quit_normal_team_failed"      => "退出讨论组失败",
            "dismiss_team_success"         => "群已解散",
            "dismiss_team_failed"          => "解散群失败",
            "dismiss_team"                 => "解散本群",
            "transfer_team"                => "转让群",
            "quit_team"                    => "退出群",
            "quit_normal_team"             => "退出讨论组",
            "team_apply_to_join"           => "申请加入",
            "advanced_team"                => "高级群",
            "normal_team"                  => "讨论组",
            "team_name_toast"              => "群名称不能为空",
            "not_allow_empty"              => "不能为空",
            "chat_setting"                 => "聊天信息",
            "team_settings_name"           => "设置名称",
            "normal_team_name"             => "讨论组名称",
            "remove_member_success"        => "移除成员成功",
            "remove_member_failed"         => "移除成员失败",
            "invite_member_success"        => "邀请成员成功",
            "invite_member_failed"         => "邀请成员失败",
            "team_create_notice"           => "请添加群成员",
            "team_settings_set_name"       => "取个名字吧",
            "team_not_exist"               => "该群不存在",
            "normal_team_not_exist"        => "该讨论组不存在",
            "mute_msg"                     => "设置禁言",
            "set_head_image"               => "设置头像",
            "team_update_cancel"           => "取消更新",
            "team_update_failed"           => "群头像设置失败",
            "reach_capacity"               => "人数已达上限",

            "message_search_no_result" => "无结果",
            "message_search_title"     => "查看聊天内容",


            "picker_image_preview"                 => "预览",
            "picker_image_send"                    => "发送",
            "picker_image_folder"                  => "相册",
            "picker_image_album_loading"           => "相册加载中…",
            "picker_image_album_empty"             => "相册中没有图片！",
            "picker_image_folder_info"             => "共%d张",
            "picker_image_exceed_max_image_select" => "最多选择%d张图片！",
            "picker_image_send_select"             => "完成（%d）",
            "picker_image_preview_original"        => "发送原图",
            "picker_image_preview_original_select" => "发送原图（%s）",
            "picker_image_error"                   => "获取图片出错",
            "image_compressed_size"                => "该原始图片大小为: %1\$s,",
            "unknow_size"                          => "未知大小",
            "is_send_image"                        => "是否发送该原始图片？",
            "multi_image_compressed_size"          => "原始图片总大小为: %1\$s,",
            "is_send_multi_image"                  => "是否发送这些原始图片？",
            "waitfor_image_local"                  => "正在打开相册，请稍候\u2026",
            "recapture"                            => "重拍",
            "image_show_error"                     => "图片显示异常",
            "memory_out"                           => "内存不足",
            "choose_from_photo_album"              => "从手机相册选择",
            "choose"                               => "选取",
            "edit"                                 => "编辑",
            "crop"                                 => "裁剪",


            "msg_type_image"         => "图片",
            "msg_type_audio"         => "语音",
            "msg_type_multi_retweet" => "[聊天记录]",


            "download_picture_fail"         => "图片下载失败",
            "look_up_original_photo_format" => "查看原图（%s）",

            "fts_enable"       => "全文检索(FTS4)",
            "fts_prefix_hit"   => "前缀匹配高亮检索结果",
            "msg_search"       => "全文检索",
            "msg_search_limit" => "全文检索显示条数",

            "pick_image"                    => "选择图片",
            "pick_video"                    => "选择视频",
            "pick_album"                    => "相册",
            "all_images"                    => "全部图片",
            "folder_image_count"            => "(%1\$d)",
            "preview_image_count"           => "%1\$d/%2\$d",
            "complete"                      => "完成",
            "preview_count"                 => "预览(%1\$s)",
            "photo_crop"                    => "图片裁剪",
            "origin"                        => "原图",
            "origin_size"                   => "原图(%1\$s)",
            "choose_all"                    => "全选",
            "choose_max_num"                => "最多选择%d张",
            "choose_min_num"                => "至少选择%d张",
            "choose_max_num_video"          => "最多选择%d个视频",
            "choose_video_photo"            => "图片和视频无法同时选择",
            "choose_video_duration_max_tip" => "只能选择%d秒内的视频，请编辑好后再选择",
            "choose_video_duration_min_tip" => "不可导入小于3秒的视频",
            "back"                          => "返回",
            "network_unavailable"           => "网络连接失败",
            "send_d"                        => "发送(%d)",
            "delete"                        => "删除",
            "video_network_not_good"        => "网络不给力，稍后重试",
            "permission_request"            => "需要语音和摄像头权限",
            "sure"                          => "确定",
            "super_team_impl_by_self"       => "超大群开发者按需实现",
            "delete_chat_only_server"       => "删除该聊天（仅服务器）",
            "message_history"               => "聊天记录",
            "load_more"                     => "加载更多",
            "no_more_session"               => "没有更多会话了",

            "wjcdsjllq"         => "未检测到手机浏览器，无法打开网页",
            "ljcw"              => "路径错误",
            "chytxx"            => "撤回一条消息",
            "glxbzczf"          => "该类型不支持转发",
            "gxxwffs"           => "该消息无法发送",
            "nybdflh"           => "你已被对方拉黑。",
            "hwck"              => "还未查看",
            "rwd"               => "人未读",
            "drc_image"         => "[图片]",
            "drc_video"         => "[视频]",
            "drc_voice"         => "[语音消息]",
            "drc_location"      => "[位置]",
            "drc_file"          => "[文件]",
            "drc_notification"  => "[通知提醒]",
            "drc_robot"         => "[机器人消息]",
            "drc_other_message" => "[其他消息]",
            "srxx"              => "输入消息",
        ];
    }
}
