#!/bin/bash
# fendServe command
set -e

binDir=`dirname $0`

# Print help information to console
printHelpMsg() {
    cat <<-EOF
  Commands:
    build-api-test        构建test分支 latest镜像
    build-api-prod        构建master分支 指定版本线上镜像
EOF
    exit 0;
}

if [ -z "$*" ]; then
    printHelpMsg
fi

case "$1" in
        build-api-test)
            printf "\033[33;49;1m 更新代码 \033[39;49;0m \n"
            git checkout . && git clean -fd && git checkout test && git pull

            printf "\033[33;49;1m build Docker镜像 \033[39;49;0m \n"
            docker build -t xiaoquan/api:latest --no-cache .
            docker tag xiaoquan/api:latest docker-hub.chqaz.com/xiaoquan/api:latest

            printf "\033[33;49;1m push \033[39;49;0m \n"
            docker push docker-hub.chqaz.com/xiaoquan/api:latest
        ;;

        build-api-prod)
            printf "\033[33;49;1m 更新代码 \033[39;49;0m \n"
            git checkout . && git clean -fd && git checkout master && git pull

            printf "\033[33;49;1m build Docker镜像 \033[39;49;0m \n"
            docker build -t xiaoquan/api:1.0.0 --no-cache .
            docker tag xiaoquan/api:1.0.0 docker-hub.chqaz.com/xiaoquan/api:1.0.0

            printf "\033[33;49;1m push \033[39;49;0m \n"
            docker push docker-hub.chqaz.com/xiaoquan/api:1.0.0
        ;;
    *)
            printHelpMsg
        ;;
esac
