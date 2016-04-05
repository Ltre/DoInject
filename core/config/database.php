<?php
/**
 * 配置数据库基本信息
 * 【！！强烈建议！！】
 * 不要将本文件中的外网数据库信息提交
 */
$hostname = substr( ($h = $_SERVER['HTTP_HOST']), 0, (false !== ($pos = strpos($h, ':')) ? $pos : strlen($h)) );
if (in_array($hostname, array(
    '127.0.0.1',
    'localhost',
    'doinject.me',//绑定本地HOSTS
))){
	
    class DIDBConfig {
        static $driver = 'DIMySQL';//驱动类
        static $host = '127.0.0.1';
        static $port = 3306;
        static $db = 'doinject';
        static $user = 'root';
        static $pwd = 'ltre';
        static $table_prefix = 'di_';//表前缀
    }
    class DIMMCConfig {
        static $domain = 'doinject';
        static $host = '127.0.0.1';
        static $port = 11211;
    }
    
} elseif (in_array($hostname, array(
	'di.webdev.duowan.com'
))) {
    
    class DIDBConfig {
        static $driver = 'DIMySQL';//驱动类
        static $host = '172.26.42.222';
        static $port = 3306;
        static $db = 'doinject';
        static $user = 'doinject';
        static $pwd = 'doinject';
        static $table_prefix = 'di_';//表前缀
    }
    class DIMMCConfig {
        static $domain = 'doinject';
        static $host;
        static $port;
    }
    
} elseif (in_array($hostname, array(
	'ltre.xyz'
))) {
    
    class DIDBConfig {
        static $driver;//驱动类
        static $host;
        static $port;
        static $db;
        static $user;
        static $pwd;
        static $table_prefix;//表前缀
    }
    class DIMMCConfig {
        static $domain = 'doinject';
        static $host;
        static $port;
    }
    
} elseif (in_array($hostname, array(
    'doinject.sinaapp.com',
))) {
    
    class DIDBConfig {
        static $driver;//驱动类
        static $host;
        static $port;
        static $db;
        static $user;
        static $pwd;
        static $table_prefix;//表前缀
    }
    class DIMMCConfig {
        static $domain = 'doinject';
        static $host;
        static $port;
    }
    
} elseif (in_array($hostname, array(
    'doinject.duapp.com',
))) {
    
    class DIDBConfig {
        static $driver;//驱动类
        static $host;
        static $port;
        static $db;
        static $user;
        static $pwd;
        static $table_prefix;//表前缀
    }
    class DIMMCConfig {
        static $domain = 'doinject';
        static $host;
        static $port;
    }
    
} elseif (in_array($hostname, array(
    'www.ltre.cc',
    'ltre.cc',
    'www.xmiku.cc',
    'xmiku.cc',
    'www.larele.com',
    'larele.com',
))) {
    
    class DIDBConfig {
        static $driver = 'DIMySQL';//驱动类
        static $host = 'localhost';
        static $port = 3306;
        static $db = MYSQL_DATABASE;
        static $user = MYSQL_USERNAME;
        static $pwd = MYSQL_PASSWORD;
        static $table_prefix;//表前缀
    }
    class DIMMCConfig {
        static $domain = 'doinject';
        static $host;
        static $port;
    }
    
} elseif (in_array($hostname, array(
    'ltre.me',
    'www.ltre.me',
    'me.ltre.me',
))) {
    
    class DIDBConfig {
        static $driver = 'DIMySQL';//驱动类
        static $host = 'localhost';
        static $port = 3306;
        static $db = 'ltreme_doinject';
        static $user = 'ltreme_doinject';
        static $pwd = 'aiyowocao';
        static $table_prefix = 'di_';//表前缀
    }
    class DIMMCConfig {
        static $domain = 'doinject';
        static $host;
        static $port;
    }
    
} else if (in_array($hostname, array(
	'emiku.cc',
    'www.emiku.cc',
    'innertest.emiku.cc'
))) {
    
    class DIDBConfig {
        static $driver = 'DIMySQL';//驱动类
        static $host = 'localhost';
        static $port = 3306;
        static $db = 'emikucc_doinject';
        static $user = 'emikucc_doinject';
        static $pwd = 'aiyowocao';
        static $table_prefix = 'di_';//表前缀
    }
    class DIMMCConfig {
        static $domain = 'doinject';
        static $host;
        static $port;
    }

} elseif (in_array($hostname, array(
	'miku.us', //恒创主机 - 香港PHP-300M体验型xmikucc域名更改为miku.us
    'www.miku.us',
))) {
    
    class DIDBConfig {
        static $driver = 'DIMySQL';//驱动类
        static $host = 'localhost';
        static $port = 3306;
        static $db = 'xmikucc_doinject';
        static $user = 'xmikucc_doinject';
        static $pwd = 'aiyowocao';
        static $table_prefix = 'di_';//表前缀
    }
    class DIMMCConfig {
        static $domain = 'doinject';
        static $host;
        static $port;
    }
    
} else {
    
    die;//环境不明确，终止
    
}