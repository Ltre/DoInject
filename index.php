<?php
define('BASE_DIR', dirname(__FILE__).'/');
//附加服务器环境检测
if (version_compare(PHP_VERSION, '5.3.0') < 0) {
    die('<h3 align=center style="margin-top:100px;font-family:微软雅黑;">检测到PHP版本低于5.3.0，建议安装<a href="http://pan.baidu.com/s/1o6kgqHO" target="_blank">WampServer 2.2(PHP 5.3.x)</a>以上的版本后，再次执行该安装向导。</h3><br><h6 align=center><a href="http://miku.us/" target="_blank">.</a></h6>');
}

define('CLI_DEBUG', 0);
CLI_DEBUG && require 'cli_debug.php';//启用CLI调试
require 'core/base/__include.php';