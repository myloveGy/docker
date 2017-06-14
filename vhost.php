<?php
/**
 * file: vhost.php
 * desc: 配置虚拟目录脚本
 * author: liujx
 * date: 2017-06-11
 * params desc:
 * -h 配置IP 例如 -h 192.168.99.100 默认 127.0.0.1
 * -d 配置域名 例如 -d test.com
 * -v 配置虚拟目录 例如 -v  d:/html
 * -t 配置服务器类型(nginx apache) 例如 -t apache 默认： apache
 * -r 配置需要移除的域名和配置 例如 -r test.com
 */
$strPhp = array_shift($argv);

// window hosts 文件位置
define('WINDOW_HOST', 'C:/Windows/System32/drivers/etc/hosts');
// apache2.4 服务名称
define('APACHE_NAME', 'apache2.4');
// apache2.4 虚拟目录配置文件目录
define('APACHE_VHOST_PATH', 'D:/server/vhost/');
// docker nginx 虚拟目录配置文件目录
define('NGINX_VHOST_PATH', 'D:/html/docker/vhost/');

// 判断是否输入参数
if (empty($argv)) {
	echo 'Please enter the domain name and project directory'."\n";
	echo 'Params desc:
    -h Configure IP For example -h 192.168.99.100 default 127.0.0.1
    -d Configure domain name such as -d test.com
    -v Configure virtual directories such as -v d:/html
    -t configuration server type (nginx apache) For example -t apache default: apache
    -r Configure the domain name and configuration to be removed, for example, -r test.com';
	exit;
} 

/**
 * 给数组设置默认值
 * @param array  &$array       处理数组
 * @param string $key          数组的键
 * @param mixed  $defaultValue 默认值
 */
function setDefault(array &$array, $key, $defaultValue) {
	if (!isset($array[$key]) || empty($array[$key])) {
		$array[$key] = $defaultValue;
	}
}

// 处理参数
$params = array_chunk($argv, 2);
$argument = [];
if ($params) {
	foreach ($params as $value) {
		$argument[$value[0]] = $value[1];
	}
}

// 判断是否设置域名(或者删除域名)
$isDomain = isset($argument['-d']) && !empty($argument['-d']);
$isRemove = isset($argument['-r']) && !empty($argument['-r']);
if (!$isDomain && !$isRemove) {
    exit('You need to configure a domain name such as -d localhost');
}

// 设置域名
setDefault($argument, '-h', '127.0.0.1');
// 设置服务器方式
setDefault($argument, '-t', 'apache');

// 处理存在
$all = file(WINDOW_HOST);
$exists = false;
$arrTmp = [];
foreach ($all as $value) {
    $value = trim($value, PHP_EOL);
    if (!empty($value)) {
        // 处理修改
        if ($isDomain && strpos($value, $argument['-d'])) {
            $value = $argument['-h'].' '.$argument['-d'];
            $exists = true;
        }

        // 处理删除
        if ($isRemove && strpos($value, $argument['-r'])) {
            continue;
        }

        $arrTmp[] = $value;
    }
}

// 新增 host 解析
if (false === $exists && $isDomain) {
    $arrTmp[] = $argument['-h'].' '.$argument['-d'];
}

// 开始处理 host 设置
$resource = fopen(WINDOW_HOST, 'w+');
if ($resource) {
    // 写入数据
    foreach ($arrTmp as $value) {
        fwrite($resource, $value.PHP_EOL);
    }

    fclose($resource);

    echo 'The current host file configuration:', "\n";
    print_r($arrTmp);
} else {
	echo 'Open system host file failed', "\n";
}

// 判断删除虚拟目录配置
if ($isRemove) {
    switch ($argument['-t']) {
        case 'apache':
            $strPath = APACHE_VHOST_PATH;
            break;
        case 'nginx':
            $strPath = NGINX_VHOST_PATH;
            break;
    }

    $strPath .= $argument['-r'].'.conf';
    if (file_exists($strPath)) {
        unlink($strPath);
    }
}

// 处理虚拟目录
if ($isDomain && isset($argument['-v']) && !empty($argument['-v'])) {
    $strFile = '';
    $strPath = '';
    switch ($argument['-t']) {
        case 'apache':
            if (file_exists($argument['-v'])) {
                $strFile = <<<FILE
<VirtualHost *:80>
    ServerAdmin 821901008@qq.com
    DocumentRoot "{{_PATH_}}"
    ServerName {{_DOMAIN_}}
    ErrorLog "logs/{{_DOMAIN_}}-error.log"
    CustomLog "logs/{{_DOMAIN_}}-access.log" common
    <Directory "{{_PATH_}}">
        Options Indexes FollowSymLinks
        AllowOverride None
        Require all granted
    </Directory>
</VirtualHost>
FILE;
            } else {
                echo 'error: The configuration directory does not exist', "\n";
            }

            $strPath = APACHE_VHOST_PATH;

            break;
        case 'nginx':
            $strFile = <<<FILE
server {
    listen 80;
    server_name {{_DOMAIN_}};
    access_log  off;
    index index.php index.html;
    root {{_PATH_}};

    ### 响应PHP
    include /var/www/docker/nginx/php.conf;

    ### 响应图片资源
    location ~ .*\.(gif|jpg|jpeg|png|bmp|swf|flv|ico)$ {
        expires 30d;
        access_log off;
    }

    ### 响应静态资源
    location ~ .*\.(js|css)?$ {
        expires 7d;
        access_log off;
    }
}
FILE;
            $strPath = NGINX_VHOST_PATH;
            break;
    }

    // 判断文件和路径信息
    if ($strFile && $strPath) {
        file_put_contents($strPath.$argument['-d'].'.conf', str_replace(
            [
                '{{_DOMAIN_}}',
                '{{_PATH_}}'
            ],
            [
                $argument['-d'],
                $argument['-v']
            ],
            $strFile));
    }

    if ($argument['-t'] === 'apache') {
        exec('NET STOP ' . APACHE_NAME);
        exec('NET START' . APACHE_NAME);
    }
}
