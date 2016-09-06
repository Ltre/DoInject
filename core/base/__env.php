<?php

/* 框架特性开关、或选项（这里的define代码都不要动，移步到define.php即可） */

header('Content-type:text/html; charset=utf-8'); //这个放这里不科学，应该放置在任何涉及output的操作之前，如模板输出之前、dump之前
//开启输出缓冲（租用的服务器需要）。这个放这里不科学，应该放置在任何涉及output的操作之前，如模板输出之前、dump之前
ob_start();

date_default_timezone_set('PRC');
session_start();

//自定义会话名前缀，防止同客户端且同域名下造成的会话名称冲突。该常量配合函数session()使用，详见__lib.php。默认为'di_'
if(!defined('DI_SESSION_PREFIX'))
    define('DI_SESSION_PREFIX', 'di_');

//默认启用小程序支持，详见__let.php。默认为true
if(!defined('DI_LET_ENABLED'))
    define('DI_LET_ENABLED', true);

//路由重写。默认为false
if(!defined('DI_ROUTE_REWRITE'))
    define('DI_ROUTE_REWRITE', false);

//路由重写失败(无匹配)时，是否终止程序。默认为false
if (!defined('DI_KILL_ON_FAIL_REWRITE'))
    define('DI_KILL_ON_FAIL_REWRITE', false);

//备用的路由REQUEST参数，方便HTML表单使用。如http://danmu.me/?x=audio/parse5sing/1可替代http://danmu.me/?audio/parse5sing/1
if(!defined('DI_ROUTE_REQUEST_PARAM_NAME'))
    define('DI_ROUTE_REQUEST_PARAM_NAME', 'x');

//备用的高级加密路由REQUEST参数，方便HTML表单或有需要保护API的场景使用，该方式可防止被社工搜索到API源码
if(!defined('DI_ROUTE_ADVANCE_REQUEST_PARAM_NAME'))
    define('DI_ROUTE_ADVANCE_REQUEST_PARAM_NAME', 'xx');

//是否统一对有do命令的参数进行过滤  TODO:准备实现过滤函数，实现自动过滤和手动过滤。默认为false
if(!defined('DI_FILTRATE_DOPARAMS'))
    define('DI_FILTRATE_DOPARAMS', false);

//调试模式（启用时，所有的异常直接抛出；禁用时，所有异常会记录到日志或数据库，详见 DIException @ __exception.php）。默认为true
if(!defined('DI_DEBUG_MODE'))
    define('DI_DEBUG_MODE', true);

//服务器是否允许I/O读写，如file_get_contents,file_put_contents,read,put...但is_file, is_dir这些还是不被视为I/O操作，可以一直正常使用。(对于一些不支持I/O操作的主机，应该禁用之)。默认为false
if(!defined('DI_IO_RWFUNC_ENABLE'))
    define('DI_IO_RWFUNC_ENABLE', false);

//类自动加载加载是否启用严格模式（启用时，必须用import()进行ext类库引入；禁用时，在系统找不到目标类时，将对ext目录进行全部包含。强烈建议启用该严格模式，在引用外部库时，一定要提前用import()）。默认为true
if(!defined('DI_CLASS_AUTOLOAD_STRICT'))
    define('DI_CLASS_AUTOLOAD_STRICT', true);

//是否自动载入视图 TODO:待实现。默认为false
if(!defined('DI_VIEW_AUTOLOAD'))
    define('DI_VIEW_AUTOLOAD', false);

//是否启用smarty(对于一些不支持I/O操作的主机，应该禁用之)。默认为false
if(!defined('DI_SMARTY_DEFAULT'))
    define('DI_SMARTY_DEFAULT', false);

//Smarty标签左定界符。实际使用时，为兼容页面中js代码的“{”和“}”，建议使用“{{”和“}}”。如要兼容AngularJS, 可使用“{~”和“~}”、“[#”和“#]”等
if(!defined('DI_SMARTY_LEFT_DELIMITER'))
    define('DI_SMARTY_LEFT_DELIMITER', '{{');

//Smarty标签右定界符。实际使用时，为兼容页面中js代码的“{”和“}”，建议使用“{{”和“}}”。如要兼容AngularJS, 可使用“{~”和“~}”、“[#”和“#]”等
if(!defined('DI_SMARTY_RIGHT_DELIMITER'))
    define('DI_SMARTY_RIGHT_DELIMITER', '}}');

//缓存文件目录
if(!defined('DI_CACHE_PATH'))
    define('DI_CACHE_PATH', DI_DATA_PATH . 'cache/');

//日志文件目录
if(!defined('DI_LOG_PATH'))
    define('DI_LOG_PATH', DI_DATA_PATH . 'log/');

//400页面：无效请求
if(!defined('DI_PAGE_400'))
    define('DI_PAGE_400', DI_TPL_PATH . '400.php');

//403页面：权限相关
if(!defined('DI_PAGE_403'))
    define('DI_PAGE_403', DI_TPL_PATH . '403.php');

//404页面：请求有效，但无对应资源
if(!defined('DI_PAGE_404'))
    define('DI_PAGE_404', DI_TPL_PATH . '404.php');

//503错误页（主要用于DIException异常）
if(!defined('DI_PAGE_503'))
    define('DI_PAGE_503', DI_TPL_PATH . '503.php');

if(DI_DEBUG_MODE){
    //开启PHP错误提示，但关闭坑爹的严格模式（E_STRICT）
    ini_set("display_errors", "On");
    error_reporting(E_ALL & ~E_STRICT);
}
else{
    //关闭PHP错误提示，也关闭坑爹的严格模式（E_STRICT）
    ini_set("display_errors", "Off");
    error_reporting(E_ALL & ~(E_STRICT|E_NOTICE));
}