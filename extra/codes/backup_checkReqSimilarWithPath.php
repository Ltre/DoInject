<?php

final class DIRoute {

    /**
     * 实验代码：检测请求路径是否类似于文件/目录的路径
     * @author Ltre
     * @since 2015-3-28
     */
    private function checkReqSimilarWithPath($reqPath){
        $filepaths = array('robots.txt', 'favicon.ico', 'index.php', 'index.html');//附加
    
        //每20分钟缓存所有路径
        $keyAF = DI_SESSIONKEY_ALL_FILEPATHS;
        $setPathCache = function() use ($keyAF, $filepaths){
            $paths = DIInclude::parseFilePaths();
            $paths = array_merge($paths, $filepaths);
            $pathsCache = array('time' => time(), 'paths' => $paths);
            session($keyAF, $pathsCache);
            return $pathsCache;
        };
        if (session_exists($keyAF)) {
            $pathsCache = session($keyAF);
            if (time() - $pathsCache['time'] > 1200) {
                $pathsCache = $setPathCache();
            }
        } else {
            $pathsCache = $setPathCache();
        }
    
        //缓存上次访问PATH
        $keyLR = DI_SESSIONKEY_LAST_REQPATH;
        if (session_exists($keyLR)) {
            $lastReqPath = session($keyLR);
        } else {
            $lastReqPath = false;
        }
        'favicon.ico' != $reqPath && session($keyLR, $reqPath);//每次记录访问PATH，并忽略favicon.ico
    
        //请求的PATH是否类似文件/目录
        $checkSimilar = function() use ($pathsCache, $reqPath){
            $check = false;
            foreach ($pathsCache['paths'] as $p) {
                if (0 === stripos($p, $reqPath)) {
                    $check = true;
                    break;
                }
            }
            return $check;
        };
        $keyIF = DI_SESSIONKEY_ISREQ_FILEPATH;
        if (session_exists($keyIF)) {
            if ($reqPath === $lastReqPath) {
                $isReqFilePath = session($keyIF);
            } else {
                $isReqFilePath = $checkSimilar();
            }
        } else {
            $isReqFilePath = $checkSimilar();
        }
    
        return $isReqFilePath;
    }
    
    
    //初始化一些与URL、路由有关的资源
    public function __construct(){
        define('DI_URL_PREFIX', url_prefix());//获取当前执行的URL前缀，截至当前执行目录，尾部有“/”
    }
    
    //总路由
    public function route(){
        DI_URLREWRITE_ENABLE && $this->uri_unshift_reqest();//根据实际情况重写路由
        
        $rt = $this->analyse($GLOBALS['request_args']);//分析路由
        DIRuntime::mergeNewItems($rt);
        
        $type = &$rt['reqtype'];
        $isal = &$rt['isallow'];
        $req = &$rt['request'];
        
        if('do'==$type && $isal){
            define('DI_LET_CURRENT', '');//无实际作用，仅防止在DO-Request模式时误用导致出错
            
            define('DI_DO_MODULE', $req['do']);
            define('DI_DO_FUNC', $req['func']);
            DIFilterUtil::execGlobalFilter();//此处插入全局过滤器(必须置于DI_DO_MODULE、DI_DO_FUNC定义之后)
            DIFilterUtil::execSpecialFilter(DI_DO_MODULE . '/'. DI_DO_FUNC); //此处插入具有特定作用点的过滤器
            $do = DI_DO_MODULE . 'Do';
            invoke_method(new $do(), DI_DO_FUNC, $req['args']);
        }
        else if('let'==$type && $isal){
            //无实际作用，仅防止在LET-Request模式时误用导致出错
            {
                define('DI_DO_MODULE', '');
                define('DI_DO_FUNC', '');
            }
            
            define('DI_LET_CURRENT', $req['path']);
            let(DI_LET_CURRENT);
        }
        else{
            throw new DIException('无法识别request类型或找不到request目标，详见：' . var_export($rt, true), DI_PAGE_400);
        }
    }
    
    /*
     * 通过$_SERVER['REQUEST_URI']的关键信息，来重写$GLOBALS['request_args']的首索引，达到重写路由的目的
     * 重写映射关系：
     *      a-b => a/b
     *      a.b => a_b
     * [注意]:
     *      使用a-b代替a/b，可以确保前端资源URL的正确性
     *      a_b的下划线“_”与URL中QUERY_STRING参数名保持一致写法，在进入route()后，会自动转化为“.”
     */
    private function uri_unshift_reqest(){
        $pre = '/' == path_prefix() ? uri_prefix() : uri_pure();
        if ('' === $pre) {
            return;//请求中没有有用的URI
        }
        
        //进行重写的核心代码
        $rewrite = function(&$pre){
            $pre = str_replace(array('-', '.'), array('/', '_'), $pre);
            array_unshift_withkey($GLOBALS['request_args'], $pre, '');
        };
        
        //文件|目录不存在且请求PATH无路径特征，则重写；其它情况则按默认流程
        $isReqFilePath = $this->checkReqSimilarWithPath($pre);
        if (! file_exists($pre) && $isReqFilePath) {
            exit;
        } elseif (! file_exists($pre) && ! $isReqFilePath) {
            $rewrite($pre);
        }
    }
    
    //判断请求类型，识别DO请求和LET请求，并进行规则匹配，将匹配情况记入$runtime
    private function analyse( $request ){
        $shell = $this->getShell($request);
        $runtime['shell'] = $shell;
        
        if($this->is_do($shell)){
            $shell_arr = explode('/', trim($shell, '/'));
            $do = ucfirst(array_shift($shell_arr));
            $func = array_shift($shell_arr);
            $runtime['isallow'] = $this->cmpdo($do, $func, $shell_arr);
            $runtime += array(
                'reqtype' => 'do',
            	'request' => array(
            	    'do' => $do,
            	    'func' => $func,
            	    'args' => $shell_arr,
                ),
            );
        }
        else if($this->is_let($shell)){
            $path = trim(str_replace('_', '/', $shell), '/');//匹配URL里的“.”(传到这里变成“_”)
            $file = DI_LET_PATH . $path . '.php';
            $runtime['isallow'] = is_file($file) && $this->cmplet($path);
            $runtime += array(
            	'reqtype' => 'let',
                'request' => array(
            	   'path' => $path,
                ),
            );
        }
        else{
            $runtime += array(
            	'isallow' => false,
                'reqtype' => 'else',
                'request' => null,
            );
        }
        return $runtime;
    }
    
    //获取指令，不论GET还是POST都会进行分析。取值优先顺序：配置的默认指令 => “x”参数的值 => 左起第一个没有值的参数名。
    private function getShell( $request ){
        $shell = DIUrlShell::$_default_shell;
        if (isset($_REQUEST[DI_ROUTE_REQUEST_PARAM_NAME])) {
            $shell = $_REQUEST[DI_ROUTE_REQUEST_PARAM_NAME];
        } else {
            foreach ($request as $k => $g) {
                if('' === $g){
                    $shell = $k;
                    break;
                }
            }
        }
        return $shell;
    }
    
    //指令为DO-Request
    private function is_do($shell){
        //匹配详见：http://www.txt2re.com/index-php.php3?s=f_1ds/f2d5_g&1&-3
        $re1='^((?:[a-z][a-z0-9_]*))';	# Variable Name 1
        $re2='(\\/)';	# Any Single Character 1
        $re3='((?:[a-z][a-z0-9_]*))';	# Variable Name 2
        $re4 = '((?:\\/[\\w\\.\\-]+)*)';	# Unix Path 1
        
        $rs = preg_match("/".$re1.$re2.$re3.$re4."/is", $shell);
        return false!==$rs && 0 < $rs;
    }
    
    
    //指令为LET-Request
    private function is_let($shell){
        $re1 = '';//原值 = '^(\\|)(\\|)'
        $re2 = '((?:[a-z0-9_-]+(\\_))*)';//原值 = '((?:[a-z0-9_-]+(\\|))*)'，现值匹配URL里的“.”(传到这里变成“_”)
        $re3 = '((?:[a-z0-9_-]*))+$';

        $rs = preg_match("/".$re1.$re2.$re3."/is", $shell);
        return false!==$rs && 0 < $rs;
    }
    
    
    //匹配Do的放行规则
    private function cmpdo(&$do, &$func, $args){
        $strict = DIUrlShell::$_strict_mode;
        $allowed = DIUrlShell::doshell();
        
        // 判断是否显式声明过（顺便将do命令的大小写与doshell()里的同步（如果有显式声明））
        $declared = false;
        foreach ($allowed as $k => $al) {
            if (0 == strcasecmp($do, $k)) {
                $do = $k;
                foreach ($al as $kk => $a) {
                    if (0 == strcasecmp($func, $kk)) {
                        $func = $kk;
                        $declared = true;
                    }
                }
                break;
            }
        }
        $inspect = "{$do}Do::{$func}";
        
        //严格模式下判断 - XxxDo::func是否存在于DO配置里
        if ($strict && !$declared) {
            throw new DIException("URLSHELL严格模式已启用，当前请求的[ {$inspect}() ]没有在DO规则中配置", DI_PAGE_400);
        }
        
        //任何模式下都判断//XxxDo::func在代码中是否定义
        if (! method_exists($do.'Do', $func)) {
            throw new DIException("当前请求的[ {$inspect}() ]在代码中找不到定义", DI_PAGE_400);
        }
        
        //严格模式下判断 - XxxDo::func实际传参个数不少于DO配置的最少个数
        if ( $strict && $declared && count($args) < (@$func_rule_num = $allowed[$do][$func]) ) {
            throw new DIException("URLSHELL严格模式已启用，实际传入[ {$inspect}() ]的参数个数少于DO配置的最少必需个数({$func_rule_num})，当前传入个数" . count($args), DI_PAGE_400);
        }
        
        //任何模式下都判断 - 代码中函数参数表配置的必需个数，如果实际传入个数小于这个值，则抛出异常
        $rm = new ReflectionMethod($inspect);
        $needNum = $rm->getNumberOfRequiredParameters();
        $c3 = $needNum <= count($args);
        $real_nums = count($args);
        if ($needNum > $real_nums) {
            throw new DIException("实际传入[ {$inspect}() ]的参数个数少于代码中函数参数表的最少必需数，当前传参{$real_nums}个，最少需要{$needNum}个", DI_PAGE_400);
        }
        
        //检测通过，可以放行
        return true;
    }
    
    
    //匹配Let的放行规则 TODO:尚未完成匹配
    private function cmplet($path){
        $allowed = DIUrlShell::letshell();
        array_walk($allowed, function(&$item, $key){
        	$item = str_replace('_', '/', $item);
        });
        
        return true;
    }
    
}




//会话key，用于记录初次访问会将所有文件的路径集合记录，详见uri_unshift_reqest()@__route.php。该值本应为全局共享，但暂存到session中，由session()函数统一管理，如有memcache或redis，将会改进该机制。
if (!defined('DI_SESSIONKEY_ALL_FILEPATHS'))
    define('DI_SESSIONKEY_ALL_FILEPATHS', 'sessionkey_all_filepaths');

//会话key，用于记录上次请求PATH，详见uri_unshift_reqest()@__route.php。该值为会话独享，由session()函数管理。
if (!defined('DI_SESSIONKEY_LAST_REQPATH'))
    define('DI_SESSIONKEY_LAST_REQPATH', 'sessionkey_last_reqpath');

//会话key，用于记录本次请求PATH是否类似于文件路径，详见uri_unshift_reqest()@__route.php。该值为会话独享，由session()函数管理。
if (!defined('DI_SESSIONKEY_ISREQ_FILEPATH'))
    define('DI_SESSIONKEY_ISREQ_FILEPATH', 'sessionkey_isreq_filepath');
