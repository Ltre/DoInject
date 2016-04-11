<?php
/**
 * 参照__env.php建议，按己所需，重新定制特性
 */
$hostname = substr( ($h = $_SERVER['HTTP_HOST']), 0, (false !== ($pos = strpos($h, ':')) ? $pos : strlen($h)) );
switch ($hostname) {
    //以下使用本地
	case '127.0.0.1':
        case '192.168.1.99':
	case 'localhost':
	case 'doinject.me':
    case 'di.webdev.duowan.com':
	    {
	        define('DI_ROUTE_REWRITE', true);
	        break;
	    }

	//以下使用SAE不可写空间
	case 'doinject.sinaapp.com':
	case 'ltre.xyz':
	    {
	        define('DI_DEBUG_MODE', false);
	        define('DI_IO_RWFUNC_ENABLE', false);
	        break;
	    }

	//以下使用可写空间(正式环境)
	case 'doinject.duapp.com'://BAE 2G空间
	case 'ltre.me'://老薛主机 - 香港1号
	case 'www.ltre.me'://老薛主机 - 香港1号
    case 'ltre.cc'://Hostker - fkb159357.host.smartgslb.com
	case 'www.ltre.cc'://Hostker - fkb159357.host.smartgslb.com
	case 'xmiku.cc'://恒创主机 - 香港九仓
	case 'www.xmiku.cc'://恒创主机 - 香港九仓
	case 'larele.com'://Hostker - fkb159357.host.smartgslb.com
	case 'www.larele.com'://Hostker - fkb159357.host.smartgslb.com
	case 'www.emiku.cc'://老薛主机 - 美国1号
	case 'emiku.cc'://老薛主机 - 美国1号
	case 'www.miku.us'://Hostker - fkb159357.host.smartgslb.com
	case 'miku.us'://Hostker - fkb159357.host.smartgslb.com
	case 'www.iio.ooo'://Hostker - fkb159357.host.smartgslb.com
	case 'iio.ooo'://Hostker - fkb159357.host.smartgslb.com
    	{
    	    define('DI_DEBUG_MODE', false);
    	    define('DI_IO_RWFUNC_ENABLE', true);
    	    define('DI_ROUTE_REWRITE', true);
    	    break;
    	}
    //以下使用可写空间(测试环境)
	case 'me.ltre.me'://老薛主机 - 香港1号
	case 'innertest.emiku.cc'://老薛主机 - 美国1号
	    {
	        define('DI_DEBUG_MODE', true);
	        define('DI_IO_RWFUNC_ENABLE', true);
	        break;
	    }
	default:die;//环境不明确，终止执行
}


define('DI_SMARTY_DEFAULT', false);//暂时所有环境不默认采用smarty
