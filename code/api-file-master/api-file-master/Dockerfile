FROM xiaoquan-docker-registry.cn-beijing.cr.aliyuncs.com/base/php-openresty:2.3.5
MAINTAINER ailuoy <ailuoy@gmail.com>
ENV WORKING_DIR /www
COPY . $WORKING_DIR

RUN cd $WORKING_DIR  \
    && cp .env.test .env \
    && rm -rf vendor \
    && composer install \
     && composer config  repos.packagist composer https://mirrors.aliyun.com/composer/ \
    && composer dump-autoload \
    && chmod 777 storage/logs/ bootstrap/ -R
