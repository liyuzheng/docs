#!/bin/bash
printf "\033[33;49;1m ------------------- 更新代码 \033[39;49;0m \n"
git checkout . && git checkout master && git clean -fd && git pull origin master

set -e

binDir=`dirname $0`

printHelpMsg() {
    cat <<-EOF
  Commands:
    api-test          部署测试环境api
    api-prod          部署线上环境api
EOF
    exit 0;
}

if [ -z "$*" ]; then
    printHelpMsg
fi

case "$1" in

        api-test)
            printf "\033[33;49;1m ------------------- 更新镜像 \033[39;49;0m \n"
            docker-compose -f docker-compose-test.yml pull

            printf "\033[33;49;1m ------------------- 关闭容器 \033[39;49;0m \n"
            docker-compose -f docker-compose-test.yml down

            printf "\033[33;49;1m ------------------- 拉起容器 \033[39;49;0m \n"
            docker-compose -f docker-compose-test.yml up -d

            printf "\033[33;49;1m ------------------- 设置logs 777 权限 \033[39;49;0m \n"
            sudo chmod 777 logs -R
        ;;
        api-prod)
            printf "\033[33;49;1m ------------------- 更新镜像 \033[39;49;0m \n"
            docker-compose -f docker-compose-prod.yml pull

            printf "\033[33;49;1m ------------------- 关闭容器 \033[39;49;0m \n"
            docker-compose -f docker-compose-prod.yml down

            printf "\033[33;49;1m ------------------- 拉起容器 \033[39;49;0m \n"
            docker-compose -f docker-compose-prod.yml up -d

            printf "\033[33;49;1m ------------------- 设置logs 777 权限 \033[39;49;0m \n"
            sudo chmod 777 logs -R
        ;;
    *)
            printHelpMsg
        ;;
esac
