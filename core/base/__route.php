<?php

final class DIRoute {

    //初始化一些与URL、路由有关的资源
    public function __construct(){
        define('DI_URL_PREFIX', url_prefix());//获取当前执行的URL前缀，截至当前执行目录，尾部有“/”
    }
    
    //总路由
    public function route(){
        $request = $GLOBALS['request_args'];
        $this->isAllowRewrite($request) && $this->rewrite2($request);//重写路由
        
        $rt = $this->analyse($request);//分析路由
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
    
    /**
     * 检查是否符合路由重写条件：
     *      1、启用了重写开关；
     *      2、路由“x”参数的值没被指定；
     *      3、路由“xx”参数的值没被指定
     *      4、request_args中不存在以下：
     *              值为空串、且在url中显式使用等号的参数（GET情况）
     *              或
     *              值为空串的POST参数（POST情况）
     */
    private function isAllowRewrite($request){
        if (! DI_ROUTE_REWRITE) {
            return false;
        }
        $x = DI_ROUTE_REQUEST_PARAM_NAME;
        if (isset($request[$x]) && '' !== $request[$x]) {
            return false;
        }
        $xx = DI_ROUTE_ADVANCE_REQUEST_PARAM_NAME;
        if (isset($request[$xx]) && '' !== $request[$xx]) {
            return false;
        }
        $checkFirst = true;
        foreach ($request as $k => $g) {
            if ('' === $g) {
                $usedEqual = preg_match('/[?&]'.str_replace('/', '\\/', $k).'=/', $_SERVER['REQUEST_URI']);
                $firstPostArgIsNulStr = strtolower($_SERVER['REQUEST_METHOD']) == 'post';
                if (! $usedEqual && ! $firstPostArgIsNulStr) {
                    $checkFirst = false;
                    break;
                }
            }
        }
        return $checkFirst;
    }
    
    /**
     * 根据自定义规则重写路由。
     * 不是所有规则都一定能实现，要看优先级。优先级以数组头部元素为最高
     * “<>”使用单个字母的，表示位置的代号，可实现规则的左右侧关系。
     *      一般推荐(但不强制)用D表示XxxDo, F表示XxxDo::Func(), D、F以外的单个字母表示LET的路径组成
     * <num>表示参数值为单数字，<nums>表示参数值为多个数字，<letter>表示参数值为单个字母，<word>表示参数值为多个字母，<var>表示参数值形似变量
     * 当不确定需要使用多少参数时，可以考虑使用尾随参数<*>
     * <*>表示尾随参数，值类型任意，从左向右自动追加到规则右侧的尾部。追加尾随参数时，右侧用什么分隔符，就使用什么分隔符来分割参数。
     */
    private function rewrite(&$request){
        $pureUri = '/' == path_prefix() ? uri_prefix() : uri_pure();
        /* if ('' === $pureUri) {return;}//请求中没有有用的URI */
        
        $rules = DIRouteRewrite::$rulesMap;
        $dontRules = DIRouteRewrite::$withoutMap;
        $req = url_prefix('://') . $pureUri;
        //示例规则
        $rules || $rules = array(
            't' => 'test/diyroute',
            't/d' => 'test/diyroute',
            't/d/prefix<*>' => 'test/diyroute',
            't/d.html' => 'test/diyroute',
            't/d/<*>' => 'test/detail',
            't/d/<*>.html' => 'test/detail',
            't/<*>' => 'test/detail',
            't/<*>.html' => 'test/detail',
            't/d/<*>' => 'test/diyroute',
            't.d' => 'test.diyroute',
            't-d' => 'test/diyroute',
            't-d.html' => 'test/diyroute',
            't-d-<*>' => 'test/detail',
            't-d-<*>.html' => 'test/detail',
            'd' => 'test.diyroute',
            'sitemap.xml' => 'a/b',
            '<var>.html' => 'test/diyroute/<var>',
            '<D>' => '<D>/start',
            '<D>.html' => '<D>/index',
        	'<D>/<F>' => '<D>/<F>',
            '<D>/<*>' => '<D>/detail',//<D>/<*>会和<D>/<F>冲突，只能依据优先级来定
            '<D>-<F>' => '<D>/<F>',
            '<D>-<*>' => '<D>/detail',//<D>-<*>会和<D>-<F>冲突，只能依据优先级来定
            '<D>.<F>' => '<D>.<F>',
            '<D>.<*>' => '<D>.detail',//<D>.<*>会和<D>.<F>冲突，只能依据优先级来定
        );
        $dontRules || $dontRules = array(
            'robots.txt'=>true, 'index.php'=>false, 'index.html'=>false, 'index.htm'=>false, 'favicon.ico'=>true,
        );
        
        //以下情况免重写
        if (in_array($pureUri, array_keys($dontRules))) {
            if ($dontRules[$pureUri]) die;
            return;
        }
        
        //开始尝试重写，遇到第一个符合的即确定结果
        foreach ($rules as $k => $rule) {
            $kBackup = $k;
            if (0 !== stripos($k, '://')) {
                $k = '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '\//') . '/' . $k;
            }
            $k = '/' . str_ireplace(
                array('\\\\', '://', '/', '<', '>',  '.', '*'),
                array( '', '', '\/', '(?<', '>\w+)', '\.', 'tailparam'),
                $k
            ) . '(\/|\.html?)?$/i'; //原来的\/?替换为(\/|\.html?)，默认支持后缀“/”、“.htm”、“.html”
            if (preg_match($k, $req, $matchs)) {
                //dump(compact('matchs', 'kBackup', 'k', 'rule', 'req'));
                $checkTypeInNeed = true;
                foreach($matchs as $matchkey => $matchval){
                    $isPosTag = is_letter($matchkey);
                    $keyIsLetter = !! preg_match('/^letter\d*$/', $matchkey);
                    $valueIsLetter = is_letter($matchval);
                    $keyIsWord = !! preg_match('/^word\d*$/', $matchkey);
                    $valueIsWord = is_word($matchval);
                    $keyIsNum = !! preg_match('/^num\d*$/', $matchkey);
                    $valueIsNum = is_a_num($matchval);
                    $keyIsNums = !! preg_match('/^nums\d*$/', $matchkey);
                    $valueIsNums = is_numeric($matchval);
                    $keyIsVar = !! preg_match('/^var\d*$/', $matchkey);
                    $valueIsVar = is_var($matchval);
                    $keyIsAny = !! preg_match('/^any\d*$/', $matchkey);
                    $valueIsAny = is_var($matchval) || is_word($matchval) || is_numeric($matchval);
                    
                    //var_dump(compact('matchkey', 'matchval', 'isPosTag', 'keyIsLetter', 'valueIsLetter', 'keyIsWord', 'valueIsWord', 'keyIsNum', 'valueIsNum', 'keyIsNums', 'valueIsNums', 'keyIsVar', 'valueIsVar', 'keyIsAny', 'valueIsAny'));
                    $typeIsNormal = $keyIsLetter && $valueIsLetter || $keyIsWord && $valueIsWord || $keyIsNum && $valueIsNum || $keyIsNums && $valueIsNums || $keyIsVar && $valueIsVar || $keyIsAny && $valueIsAny;
                    if ($isPosTag || $typeIsNormal) {
                        $rule = str_ireplace("<{$matchkey}>", $matchval, $rule);
                    } elseif ('tailparam' === $matchkey) {
                        $isDo = count(explode('/', $rule)) >= 2;
                        $delimiter = $isDo ? '/' : '.';
                        $rule .= $delimiter . $matchval;
                    }
                    //检查需要匹配类型时，是否匹配成功
                    if ($keyIsLetter&&!$valueIsLetter || $keyIsWord&&!$valueIsWord || $keyIsNum&&!$valueIsNum || $keyIsNums&&!$valueIsNums || $keyIsVar&&!$valueIsVar || $keyIsAny&&!$valueIsAny) {
                        $checkTypeInNeed = false;
                    }
                }
                //通过$_SERVER['REQUEST_URI']的关键信息，以及上述规则，来重写$GLOBALS['request_args']的首索引，达到重写路由的目的
                //转化关系：a-b => a/b, a.b => a_b
                //使用a-b代替a/b，可以确保前端资源URL的正确性
                //a_b的下划线“_”与URL中QUERY_STRING参数名保持一致写法，在进入route()后，会自动转化为“.”
                if ($checkTypeInNeed) {
                    $rule = str_replace(array('-', '.'), array('/', '_'), $rule);
                    array_unshift_withkey($request, $rule, '');
                    return; //到此处为重写成功
                } else {
                    $kBackup = str_ireplace(array('<','>'), array('＜', '＞'), $kBackup);
                    $msg = "已匹配到重写规则，但URI中的个别部分或参数不与规则中的设定匹配。当前规则：[{$kBackup} => {$rule}]";
                    throw new DIException($msg);
                }
            }
        }
        
        //没有任何规则被匹配，且当前有用的URI为空，则走DIUrlShell::$_default_shell指定的路由。
        if ('' === $pureUri) {
            return;
        }
        
        //如果还是没有任何匹配时，则作空请求处理。若启用了DI_KILL_ON_FAIL_REWRITE，则可以减少被盲点爬虫时消耗的流量
        if (DI_KILL_ON_FAIL_REWRITE) {
            die;
        }
    }
    
    
    private function rewrite2(&$request){
        $rules = DIRouteRewrite::$rulesMap;
        $dontRules = DIRouteRewrite::$withoutMap;
        
        $pureUri = '/' == path_prefix() ? uri_prefix() : uri_pure();
        $pureUriWithoutSuffix = preg_replace('/(\/|\.html?)$/i', '', $pureUri);
        $req = url_prefix('://') . $pureUri;
        $reqWithoutSuffix = preg_replace('/(\/|\.html?)$/i', '', $req);//默认支持后缀“/”、“.htm”、“.html”
        //以下情况免重写
        if (in_array($pureUri, array_keys($dontRules))) {
            if ($dontRules[$pureUri]) die;
            return;
        }
        //开始尝试重写，遇到第一个符合的即确定结果
        foreach ($rules as $k => $v) {
            $newK = '';
            if (0 !== stripos($k, '://')) {
                $newK = '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '\//') . '/' . $k;
            } else {
                $newK = $k;
            }
            $re = '/' . str_ireplace(
                array('\\\\', '://', '/', '<', '>',  '.'),
                array( '', '', '\/', '(?<', '>\w+)', '\.'),
                $newK
            ) . '$/i';
            if (preg_match($re, $reqWithoutSuffix, $matches)) {
                foreach ($matches as $matchKey => $matchVal) {
                    $v = str_ireplace("<{$matchKey}>", $matchVal, $v);
                }
                array_unshift_withkey($request, str_replace(array('.'), array('_'), urldecode($v)), '');
                return;//此处重写成功
            }
        }
        //当无规则可匹配，且当前有用的URL不为空时
        if ('' != $pureUri) {
            //若启用了DI_KILL_ON_FAIL_REWRITE，则终止。这可以减少被盲点爬虫时消耗的流量
            if (DI_KILL_ON_FAIL_REWRITE) {
                die;
            }
            //否则将QUERY_STRING部分重写为$pureUriWithoutSuffix
            array_unshift_withkey($request, str_replace(array('.'), array('_'), urldecode($pureUriWithoutSuffix)), '');
        }
        //无规则可匹配，但当前有用的URL为空，则走DIUrlShell::$_default_shell指定的路由。
        return;
    }
    
    
    //判断请求类型，识别DO请求和LET请求，并进行规则匹配，将匹配情况记入$runtime
    private function analyse( $request ){
        $rawShell = $this->getShell($request);
        $realShell = $this->tranAlias($rawShell);
        $runtime['shell'] = $realShell;
        
        if($this->is_do($realShell)){
            $shell_arr = explode('/', trim($realShell, '/'));
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
        else if($this->is_let($realShell)){
            $path = trim(str_replace('_', '/', $realShell), '/');//匹配URL里的“.”(传到这里变成“_”)
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
    
    //获取指令，不论GET还是POST都会进行分析。取值优先顺序：配置的默认指令 => “x”参数的值 => 位于左起第一个且没有值的参数名(如果没有，则取默认指令) => 最后分析该命令是否为regexp命令，是则重定向到对应的DO或LET命令，否则保持原值。
    /*
     * 获取指令，不论GET还是POST都会进行分析。
     * 取值优先顺序：
     *      => “xx”加密参数的值(用可变的值于保护接口API，防止被社工搜索到源码)
     *      => “x”参数的值
     *      => 位于左起第一个且没有值的参数名(如果没有，则取默认指令)
     *      => 配置的默认指令
     *      => 分析该命令是否为regexp命令，是则重定向到对应的DO或LET命令，否则保持原参数名。
     * 指令使用方式建议：
     *      在一个请求中，如能保证所有参数值不会出现空字符串的情况，则可以用“位于左起第一个且没有值的参数名”作为shell。
     *      否则，要以“x”参数的值作为shell
     */
    private function getShell( $request ){
        $shell = DIUrlShell::$_default_shell;
        $xx = DI_ROUTE_ADVANCE_REQUEST_PARAM_NAME;
        $x = DI_ROUTE_REQUEST_PARAM_NAME;
        if (isset($request[$xx])) {
            $shell = ltreDeCrypt($request[$xx]);
        } elseif (isset($request[$x])) {
            $shell = $request[$x];
        } else {
            foreach ($request as $k => $g) {
                if ('' === $g) {
                    $shell = rtrim($k, '/');
                    break;
                }
            }
        }
        $this->cmpregexp($shell);
        return $shell;
    }
    
    //将别名(如果设定了)转化为真正的路由值，详见DIURLShell::alias()的别名配置，不区分大小写
    private function tranAlias($alias){
        $shell = $alias;
        $map = DIUrlShell::alias() ?: array();
        foreach ($map as $k => $m) {
            $k = '/' . str_ireplace(
                array('/', '<', '>',  '.'),
                array('\/', '(?<', '>\w+)', '\.'),
                $k
            ) . '$/i';
            if (preg_match($k, $alias, $matchs)) {
                foreach ($matchs as $matchkey => $matchval) {
                    if (is_var($matchkey)) {
                        $m = str_ireplace("<{$matchkey}>", $matchval, $m);
                    }
                }
                $shell = $m;
                break;
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
    
    
    //匹配正则命令，以便重定向到DO或LET命令
    private function cmpregexp(&$shell){
        $rules = DIUrlShell::regexpshell();
        foreach ($rules as $regExp => $destShell) {
            if (preg_match($regExp, $shell, $matches)) {
                define('DI_REGEXP_SHELL', empty($matches) ? '' : $matches[0]);
                $shell = $destShell;
                break;
            }
        }
        defined('DI_REGEXP_SHELL') || define('DI_REGEXP_SHELL', '');
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