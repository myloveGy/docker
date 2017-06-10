#!/bin/bash

# 接收参数(日志和配置目录)
STRPATH="$1"
STRLOG="$2"

# 判断是否传递路径
if [ "$STRPATH" = "" ]; then
	STRPATH="/var/www/docker"
fi

if [ "$STRLOG" = "" ]; then
	STRLOG="/var/www/docker/logs"
fi

if [ ! -d "$STRLOG" ]; then
	mkdir "$STRLOG"
fi

# 判断目录是否存在
if [ ! -d "$STRPATH" ]; then
	echo "您输入的目录不存在(The directory does not exist): $STRPATH"
	exit
fi

STRLOG=${STRLOG}"/start.log"
DATE=$(date '+%Y-%m-%d %T')
echo "[$DATE] start" >> ${STRLOG}
echo "	The main configuration directory is: $STRPATH" >> ${STRLOG}
echo "	The log directory is: $STRLOG" >> ${STRLOG}

# 处理输入了/
STRSUFFIX=${STRPATH:0-1:1}

if [ "$STRSUFFIX" = "/" ]; then
	STRPATH=${STRPATH%/*}
fi

# 判断 nginx 是否启动
nginx=`ps aux | grep nginx | grep -v "grep"`
if [ "$nginx" = "" ]; then
	# 启动nginx
	STRNGINX=$(/usr/local/nginx/sbin/nginx -c ${STRPATH}/nginx/nginx.conf)
	if [ "$STRNGINX" = "" ]; then
		STRNGINX="	nginx start up..."
	else
		STRNGINX="	error: "${STRNGINX}
	fi
else
	STRNGINX="	nginx has started" 
fi

echo "$STRNGINX" >> ${STRLOG}

# 判断 mysql 是否启动
mysql=`ps aux | grep mysqld | grep -v "grep"`
if [ "$mysql" = "" ]; then
	# 启动 mysql
	$(rm -rf /etc/my.cnf)
	$(cp ${STRPATH}/my.cnf /etc/my.cnf)
	STRMYSQL=$(/etc/init.d/mysqld start)
	if [ "$STRMYSQL" = ""]; then
		STRMYSQL="	mysql start up..."
	else
		STRMYSQL="	error: "${STRMYSQL}
	fi
else
	STRMYSQL="	mysql has started"
fi

echo "$STRMYSQL" >> ${STRLOG}

# 判断 php-fpm 是否启动
php=`ps aux | grep php-fpm | grep -v "grep"`
if [ "$php" = "" ]; then
	# 启动 php-fpm
	STRPHP=$(/usr/local/php/sbin/php-fpm -c ${STRPATH}/php/php.ini -y ${STRPATH}/php/php-fpm.conf)
	if [ "$STRPHP" = "" ]; then
		STRPHP="	php start up..."
	else
		STRPHP="	error: "${STRPHP}
	fi
else
	STRPHP="	php-fpm has started" 
fi

echo "$STRPHP" >> ${STRLOG}

# 判断 redis 是否启动
redis=`ps aux | grep redis | grep -v "grep"`
if [ "$redis" = "" ]; then
	# 启动 redis
	STRREDIS=$(/usr/local/redis/bin/redis-server ${STRPATH}/redis.conf)
	if [ "$STRREDIS" = "" ]; then
		STRREDIS="	redis start up..."
	else
		STRREDIS=" error:"${STRREDIS}
	fi
else
	STRREDIS="	redis has started" 
fi

echo "$STRREDIS" >> ${STRLOG}
DATE=$(date '+%Y-%m-%d %T')
echo "[$DATE] end" >> ${STRLOG}
