<?php

/* 定义关键词 */
//分段定义1
define('DI_KEYWORD_CORE', 'core');
define('DI_KEYWORD_BASE', 'base');
define('DI_KEYWORD_CONFIG', 'config');
define('DI_KEYWORD_ENTITY', 'entity');
define('DI_KEYWORD_MODEL', 'model');
define('DI_KEYWORD_FILTER', 'filter');
define('DI_KEYWORD_INJECT', 'inject');
define('DI_KEYWORD_DO', 'do');
define('DI_KEYWORD_SERVICE', 'service');
define('DI_KEYWORD_TPL', 'tpl');
define('DI_KEYWORD_LET', 'let');
define('DI_KEYWORD_SETTING', 'setting');
define('DI_KEYWORD_EXT', 'ext');
define('DI_KEYWORD_DATA', 'data');
//分段定义2
define('DI_KEYWORD_CONST', 'const');
define('DI_KEYWORD_DEFINE', 'define');
define('DI_KEYWORD_URLSHELL', 'urlshell');
define('DI_KEYWORD_REWRITE', 'rewrite');
define('DI_KEYWORD_FILTERMAP', 'filtermap');
define('DI_KEYWORD_DATABASE', 'database');
//分段定义3
define('DI_KEYWORD_ENV', 'env');
define('DI_KEYWORD_EXCEPTION', 'exception');
define('DI_KEYWORD_LIB', 'lib');
define('DI_KEYWORD_EVENT', 'event');
define('DI_KEYWORD_ROUTE', 'route');
define('DI_KEYWORD_INIT', 'init');


/* 系统保留目录：约定所有路径都以“/”结尾 */
define('DI_CORE_PATH', DI_KEYWORD_CORE . '/');
define('DI_BASE_PATH',		DI_CORE_PATH . DI_KEYWORD_BASE . '/');
define('DI_CONFIG_PATH',	DI_CORE_PATH . DI_KEYWORD_CONFIG . '/');
define('DI_ENTITY_PATH',	DI_CORE_PATH . DI_KEYWORD_ENTITY . '/');
define('DI_MODEL_PATH',	    DI_CORE_PATH . DI_KEYWORD_MODEL . '/');
define('DI_FILTER_PATH',    DI_CORE_PATH . DI_KEYWORD_FILTER . '/');
define('DI_INJECT_PATH',	DI_CORE_PATH . DI_KEYWORD_INJECT . '/');
define('DI_DO_PATH',		DI_CORE_PATH . DI_KEYWORD_DO . '/');
define('DI_SERVICE_PATH',	DI_CORE_PATH . DI_KEYWORD_SERVICE . '/');
define('DI_TPL_PATH',		DI_CORE_PATH . DI_KEYWORD_TPL . '/');
define('DI_LET_PATH',		DI_CORE_PATH . DI_KEYWORD_LET . '/');
define('DI_SETTING_PATH',	DI_CORE_PATH . DI_KEYWORD_SETTING . '/');
define('DI_EXT_PATH', 		DI_CORE_PATH . DI_KEYWORD_EXT . '/');
define('DI_DATA_PATH',		DI_CORE_PATH . DI_KEYWORD_DATA . '/');


class DIIncludeConfig {
    //具有特定意义后缀的文件(XxxDo、XxxModel、XxxInject、XxxService)对应的自动加载路径
    static function DI_SPCL_AUTOLOAD_PATH(){
        return array(
            DI_KEYWORD_MODEL    => DI_MODEL_PATH    . '{name}.' . DI_KEYWORD_MODEL  . '.php',
            DI_KEYWORD_FILTER   => DI_FILTER_PATH   . '{name}.' . DI_KEYWORD_FILTER . '.php',
            DI_KEYWORD_INJECT   => DI_INJECT_PATH   . '{name}.' . DI_KEYWORD_INJECT . '.php',
            DI_KEYWORD_DO       => DI_DO_PATH       . '{name}.' . DI_KEYWORD_DO     . '.php',
            DI_KEYWORD_SERVICE  => DI_SERVICE_PATH  . '{name}.' . DI_KEYWORD_SERVICE. '.php',
            /* 'let' => DI_LET_PATH . '{name}.let.php', 可能不需要*/
        );
    }

    //不具有后缀的特定意义文件(例如客观实体类不需要Entity这样繁琐的后缀)
    static function DI_NOSPCL_AUTOLOAD_PATH(){
        //包含的优先级以第一项为最高，配置项的键值暂无程序作用，仅供查看。
        return array(
            DI_KEYWORD_ENTITY    =>  DI_ENTITY_PATH . '{name}.php',
        );
    }
}


//包含用户配置文件（暂时不严格限制顺序）
foreach (array(
	DI_KEYWORD_CONST,
    DI_KEYWORD_DEFINE,
    DI_KEYWORD_URLSHELL,
    DI_KEYWORD_REWRITE,
    DI_KEYWORD_FILTERMAP,
    DI_KEYWORD_DATABASE
) as $config) {
    require DI_CONFIG_PATH . "{$config}.php";
}


/* 
 * 包含核心组件和系统配置，注意加载顺序：
 * >>env -> exception -> lib
 * ->event -> base -> config
 * ->entity -> model -> filter
 * ->inject -> do -> service
 * ->tpl -> let -> route
 * ->init 
 */
foreach (array(
    DI_KEYWORD_ENV,     DI_KEYWORD_EXCEPTION,   DI_KEYWORD_LIB,
    DI_KEYWORD_EVENT,   DI_KEYWORD_BASE,        DI_KEYWORD_CONFIG,
    DI_KEYWORD_ENTITY,  DI_KEYWORD_MODEL,       DI_KEYWORD_FILTER,
    DI_KEYWORD_INJECT,  DI_KEYWORD_DO,          DI_KEYWORD_SERVICE,
    DI_KEYWORD_TPL,     DI_KEYWORD_LET,         DI_KEYWORD_ROUTE,
    DI_KEYWORD_INIT,
) as $path){
	require DI_BASE_PATH . "__$path.php";
}

