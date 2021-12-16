# 每小时检查一下用户账号注销情况
0 * * * * /usr/bin/php /data/www/api-version/artisan xiaoquan:update_user_destroy > /dev/null 2>&1

# 每天凌晨 1:00 备份message_spam数据
0 1 * * * /usr/bin/php /data/www/api/artisan xiaoquan:collect backup_yesterday_message_spam > /dev/null 2>&1
