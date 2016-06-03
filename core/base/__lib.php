<?php
/**
 * 这里放置系统内置函数，内置实用类
 * 按照顺序env->lib->event->base->config->entity->model->filter->action->service->tpl->route->init， 
 * 除了env和include，所有函数体中不能使用func之后定义或生成的常量、变量、类、函数。
 */


/* 目录包含算法 */
class DIInclude {
    
    //parseFilePaths()的解析结果
    static $parsedFilePaths = array();
    
	/**
	 * TODO: 包含目录内所有层次子目录的*.php文件
	 */
	public static function includeAllPHP( $dir ){
		foreach (explode('|', $dir) as $path){
			if( in_array($path, array('','./','../')) || false !== strpos($path, './'))
				continue;
			$path = trim(trim($path),'/');
			$path .= '/';
			self::parsePhpDirFromLibDirAndAutoIncludeTheir( $path );
		}
	}
	/**
	 * TODO: 自动检测某层目录内指定格式的文件，并包含之。
	 * 例如：Xxx.action.php，Xxx.filter.php, Xxx.entity.php
	 * @param string $xxxFormatDir 目录路径，要求以“/”结尾。例如：APPROOT.'core/entity/'
	 * @param string $secondExtname 显现特性的第二扩展名，例如：“AbcObject.obj.php”中的“obj”。
	 * @param string $suffix 显现特性的后缀，例如“AbcObject”中的“Object”。一般不推荐用这个参数，原因在于不简洁。
	 */
	public static function parseXxxFormatFromDirAndAutoIncludeTheir( $xxxFormatDir, $secondExtname, $suffix=null){
		foreach (glob(APPROOT.$xxxFormatDir.'{A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z}*' . $suffix . '.' . $secondExtname . '.php', GLOB_BRACE) as $xxxFormatDirFile){
			require $xxxFormatDirFile;
		}
	}

	/*
	 * 自动检测开发者在  指定的  目录自行添加的库文件或目录（或者其它php常量配置文件），并包含其内任何层次目录的[*.php]。
	* 参数要求：格式如：$path = APPROOT.'core/lib/'
	* 			必须从项目根目录开始指定
	* 			不能以“/”开头
	* 			要以“/”结尾
	* 参数$layer是层次，以输入路径为第0层，每递归一次便自增，用于测试，可以查看递归了多少次。
	*/
	private static function parsePhpDirFromLibDirAndAutoIncludeTheir($path, $layer=0){
		//echo "=====进入第($layer)层=====<br>";
		if( is_dir($path) && ($dh=opendir($path)) ) {
			while(false !== ($file=readdir($dh))){
				if(in_array($file, array('.','..')))
					continue;
				if(is_dir($path.$file)){
					//echo "第($layer)层：目录 - ".$path.$file."/<br>";
					self::parsePhpDirFromLibDirAndAutoIncludeTheir($path.$file.'/', $layer+1);
				}else{
					//echo "第($layer)层：文件 - ".$path.$file."<br>";
					if(preg_match('/\.php$/', $file))
						require $path.$file;
				}
			}
			closedir($dh);
			//echo "=====跳出第($layer)层=====<br>";
		}
	}
	
	/**
	 * 扫描得到指定路径的所有层次文件路径
	 * @param string $path 一般使用相对路径，例如'./'
	 * @param string $spec 可根据正则表达式来匹配需要的，例如'/\.php$/'匹配.php后缀的文件
	 * @param int $layer 指定当前所处目录层次，不需要手动指定，仅调试时查看层次用
	 */
	public static function parseFilePaths($path = './', $spec='/.*/', $layer=0){
	    if( is_dir($path) && ($dh=opendir($path)) ) {
	        while(false !== ($file=readdir($dh))){
	            if(in_array($file, array('.','..')))
	                continue;
	            if(is_dir($path.$file)){
	                self::parseFilePaths($path.$file.'/', $spec, $layer+1);
	            }else{
	                if(preg_match($spec, $file)){
	                    self::$parsedFilePaths[] = trim($path.$file, './');
	                }
	            }
	        }
	        closedir($dh);
	    }
	    return self::$parsedFilePaths;
	}
	
}


/**
 * 自动加载保留目录的文件，详见DIIncludeConfig::DI_SPCL_AUTOLOAD_PATH() || DI_NOSPCL_AUTOLOAD_PATH() @ __include.php
 * 如果保留目录里没有符合要求的文件，则加载ext目录中的扩展文件。
 * 如果启用了严格模式，且最终没有找到系统组件的支持文件，则会抛出DIExceptoin异常。
 */
/* 旧版：仅支持自动加载有特定后缀的文件以及ext目录文件
function __autoload( $class_name ){
	$aap = DIIncludeConfig::DI_SPCL_AUTOLOAD_PATH();
	$pos = false;
	$path = '';

	foreach ($aap as $i=>$p){
		$pos = strripos($class_name, $i);
		if(false!==$pos){
			$path = $p;
			break;
		}
	}

	if(false!==$pos){
		$name = substr($class_name, 0, $pos);
		$path = str_replace('{name}', $name, $path);
		if(file_exists($path)){
			require $path;//包含保留目录中的所需的文件
		}
	}else{
    	DIInclude::includeAllPHP( DI_EXT_PATH );//包含ext目录中所有文件（其实这么处理会有一定风险，因为在ext目录可能存在同名的类，且类中存在与欲访问的方法同名的方法，这样就导致了ext目录中某类方法的逻辑覆盖了预想的逻辑）
	}

	//如果ext目录中所有文件也没定义该类，则定义一个这样的类防止报Fatal Error的错误
	if(! class_exists($class_name, false) ){
		eval("class $class_name {}");
	}
} */
function __autoload( $class_name ){
    $aap = DIIncludeConfig::DI_SPCL_AUTOLOAD_PATH();
    $pos = false;
    $name = ''; $path = '';

    foreach ($aap as $i=>$p){
        $pos = strripos($class_name, $i);
        if(false!==$pos){
            $name = substr($class_name, 0, $pos);
            $path = str_replace('{name}', $name, $p);
            file_exists($path) && require $path;
            break;
        }
    }
    
    if(false===$pos){
        $aap = DIIncludeConfig::DI_NOSPCL_AUTOLOAD_PATH();
        $name = &$class_name;
        foreach ($aap as $i=>$p){
            $path = str_replace('{name}', $name, $p);
            if(file_exists($path)){
                require $path;
                //return;
                //goto INCLUDE_EXT;
                break;
            }
        }
    }
    
    //INCLUDE_EXT:
    //非AUTOLOAD严格模式下，可以在找不到目标类时，导入ext目录内全部文件来补救。（其实这么处理会有一定风险，因为在ext目录可能存在同名的类，且类中存在与欲访问的方法同名的方法，这样就导致了ext目录中某类方法的逻辑覆盖了预想的逻辑）
    // ! class_exists($class_name, false) && false===DI_CLASS_AUTOLOAD_STRICT || DIInclude::includeAllPHP( DI_EXT_PATH );//包含ext目录中所有文件（其实这么处理会有一定风险，因为在ext目录可能存在同名的类，且类中存在与欲访问的方法同名的方法，这样就导致了ext目录中某类方法的逻辑覆盖了预想的逻辑）
    ! class_exists($class_name, false) && ! DI_CLASS_AUTOLOAD_STRICT && DIInclude::includeAllPHP( DI_EXT_PATH );
    
    //如果已经包含了ext目录中所有文件，但还是没找到该类，则利用DIException有选择地抛异常
    if(! class_exists($class_name, false) ){
        $msg = "类[ $class_name ]不存在";
        throw new DIException($msg);
    }
}
// 强制注册__autoload，防止无法加载
spl_autoload_register('__autoload');


/**
 * TODO
 * 仿照java的import，导入ext目录的php文件。
 * 将按照递归顺序，从最深目录开始包含
 * 当包含的是单个文件时，还可以获得require语句的返回值，视所包含文件的return语句而定。
 * @param string $path 基于$inc的相对路径
 * @param string $inc 被作为参照的基路径，建议结尾要有“/”
 * @return mixed
 * <pre>
 * 		如a/b/c对应/core/ext/a/b/c.php
 * 		  a/b/*对应/core/ext/a/b/目录下所有层次目录的文件
 * 		  “a/b/*”会自动转换为“a/b/”
 * 		  “*” 对应 /core/ext/目录下所有层次目录的文件
 * 		  “*” 可以转换为 “/”
 *        import(1)对应core/ext/1.php
 * </pre>
 */
function import($path = '*', $inc=DI_EXT_PATH){
    $path = strval($path); $inc = strval($inc);
    empty($path) && $path='/';
    (strlen($inc)-1 === strrpos($inc, '/')) || $inc.='/';
    
	if('*' != $path){
		$path = trim($path, '*');
		$len = strlen($path);
		$path .= ( substr($path, $len-1) == '/' ) ? '' : '.php';
		$inc .= $path;
	}
	
	//如果从Runtime中发现已有导入，则不再导入。这里可以阻止已导入的文件夹和文件之间的冲突
	if (!DIRuntime_Imported::push($inc)) {
	    return false;
	}

	if(is_file($inc)){
		return require_once $inc;
	}else if(is_dir($inc)){
		DIInclude::includeAllPHP($inc);
	}else{
	    $d = debug_backtrace();
	    $msg = "import()操作错误：文件或目录[ $inc ]不存在<br>错误源：{$d[0]['file']}::第{$d[0]['line']}行import()处<br>";
	    throw new DIException($msg);
	}
}

/** 
 * 检测组件的支持文件是否存在
 * 支持的组件有：
 *      DO/MODEL/FILTER/INJECT/SERVICE
 *      ENTITY
 * @param string $name 
 *      组件全名称，即类名，如TestInject，TestDo，注意大小写
 *      对于如Entity类型的组件，是没有前后缀的。
 * @return bool
 *      true    存在
 *      false   组件不支持或文件不存在
 */
function component_exist($name){
    $aap1 = DIIncludeConfig::DI_SPCL_AUTOLOAD_PATH();
    $aap2 = DIIncludeConfig::DI_NOSPCL_AUTOLOAD_PATH();
    $aap = $aap1 + $aap2;
    foreach ($aap as $i=>$p){
        if (@$rpos = strripos($name, $i)) {
            $name = substr($name, 0, $rpos);//去除后缀(如果有)
            $path = str_replace('{name}', $name, $p);
            if(file_exists($path)) return true;
        }
    }
    return false;
}

/**
 * 调用对象的方法
 * @param object $obj 对象句柄
 * @param string $method 方法名
 * @param array $args 参数数组
 */
function invoke_method($obj, $method, $args=array()){
	if(! is_object($obj)){
		throw new DIException("{$obj}对象不存在或非对象类型");
	}
	if( method_exists($obj, $method) ){
		return call_user_func_array(array($obj, $method), $args);
	}
	else if(array_key_exists($method, get_object_vars($obj))){
		if(is_callable(array($obj, $method))){
			call_user_func_array(array($obj, $method), $args);
		}else{
			throw new DIException('成员方法访问操作：' . get_class($obj) . ' -> ' . $method . '成员变量不具备callable特性，不能调用<br>');
		}
	}
	else{
		//如果成员不存在
		throw new DIException(get_class($obj) . "对象的方法名[ {$method} ]不存在");
	}
}

function invoke_func($func, $args=array()){
    call_user_func_array($func, $args);
}


//是否为单个字母
function is_letter($s){
    if (1 != strlen($s)) return false;
    $o = ord($s);
    return ord('A') <= $o && $o <= ord('Z') || ord('a') <= $o && $o <= ord('z');
}

//是否为单个数字
function is_a_num($s){
    if (1 != strlen($s)) return false;
    return is_numeric($s) && 1 == strlen($s);
}

//是否形似变量
function is_var($s){
    return !! preg_match('/^[a-z_](?:[a-z][a-z0-9_]*)*/is', $s);
}

//是否全为字母
function is_word($s){
    foreach ((array) $s as $ss) {
        if (! is_letter($ss)) return false;
    }
    return true;
}

/**
 * 解决call_user_func_array()和new ReflectionMethod($obj, $method)
 * 参数调用错误的问题。
 * 使用：将需要传入的数组$arr用该方法包裹即可，如ref_arr($arr)
 */
function ref_arr($arr){
	if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
		$refs = array();
		foreach($arr as $key => $value)
			$refs[$key] = &$arr[$key];
		return $refs;
	}
	return $arr;
}

//按位置获取数组的key
function array_get_key(array $arr, $pos){
    $keys = array_keys($arr);
    $pos = intval($pos);
    if ($pos < 0) $pos = count($arr) + $pos;//如-1对应倒数第一个

    return key_exists($pos, $keys) ? $keys[$pos] : false;
}

//按键获取数组中的项
function array_item($arr, $key, $strict = false){
    if (key_exists($key, $arr)) {
        return $arr[$key];
    } else {
        if (! $strict) return null;
        $d = debug_backtrace();
        $msg = "数组下标[ {$key} ]不存在<br>错误源：{$d[0]['file']}::第{$d[0]['line']}行<br>";
        throw new DIException($msg);
    }
}

//删除数组某一项
function array_unset(&$array, $key) {
    $value = $array[$key];
    unset($array[$key]);
    return $value;
}

//在array_unshift的基础上，对插入首部的元素带上自定义key
function array_unshift_withkey(&$arr, $key, $value){
    $backup = $arr;
    $arr = array($key => $value);
    foreach ($backup as $k => $v) {
        if ($k == $key) continue;
        $arr[$k] = $v;
    }
    return count($arr);
}

//删除数组重复
function array_remove_duplicate($arr){
    $new = array();
    foreach ($arr as $k=>$a) {
        if (! in_array($a, $new)) {
            $new[$k] = $a;
        }
    }
    return $new;
}

/**
 * 函数的参数表转为字符串化
 * @param int $num 参数个数
 */
function params_to_str( $num ){
	$params = '';
	for ($i=0;$i<$num;$i++) $params .= '$param'.$i.',';
	$params = rtrim($params, ',');
	return $params;
}

/**
 * 创建一个类
 * @param string $name
 * @param bool $abstract
 * @param string $extends
 * @param string $implements
 * @param array $vars
 * @param array $funcs
 * @return DITempObject 如果需要，可使用这个返回的对象来创建临时对象
 * @example
 * <pre>
 * create_class(
 * 		'Nima', true, 'DIBase', 'DIEventListener,DIEvent_CallFuncListener', 
 * 		array(
 * 			'private $var1;',
 * 			'public $var2 = "2";'
 * 		),
 * 		array(
 * 			'function __construct($var1){$this->var1=$var1;}',
 * 			'public function func1(){echo "this is func1()";}',
 * 			'protected function _func2(){echo "this is _fun2()";}'
 * 		)
 * );
 * </pre>
 */
function create_class($name, $abstract=false, $extends=null, $implements=null, $vars=array(), $funcs=array()){
	$abstract = $abstract===true ? 'abstract' : '';
	$code = "$abstract class $name ";
	empty($extends) || $code .= "extends $extends ";
	empty($implements) || $code .= "implements $implements ";
	$code .= '{';
	foreach (array('vars','funcs') as $v){
		$$v = $$v === null || ! is_array($$v) ? array() : $$v;
		foreach ($$v as $item){
			$code .= $item;
		}
	}
	$code .= '}';
	//file_put_contents(DI_DATA_PATH . 'cache/a.php', '<?php  '.$code);//调试用，只注释不删除！
	eval($code);
	
	//创建类之后，可选择性的实例化一个临时对象。如create_class('clazz')->newInstance(array(参数集));
	if(!class_exists('DITempObject', false)){
	    class DITempObject extends DIBase {
	        var $class_name;
	        function __construct($class_name){$this->class_name = $class_name;}
	        function newInstance($param_arr = array()){ //如果要传参给构造，则创建class时必须继承  {已经显示声明空参构造器的类}
	            $clazz = $this->class_name;
	            $rc = new ReflectionClass($clazz);
	            return $rc->newInstanceArgs($param_arr);
	        }
	    }
	}
	return new DITempObject($name);
}


/**
 * 取本应用的会话或设置会话
 * session($name)取会话；session($name, $value)设置会话；session()取所有会话。
 * 按名称存取会话时，将只会取出名称被指定前缀的会话。
 * @param string $name 会话名
 * @param string $value 设置值
 * @param string $prefix 会话名前缀，默认值详见DI_SESSION_PREFIX @ __lib.php
 * @example
 * 		session('abc')
 */
function session($name=null, $value=null, $prefix=null){
	$num = count(func_get_args());
	$prefix = null===$prefix ? DI_SESSION_PREFIX : $prefix;
	if(2 === $num){
		$name = func_get_arg(0);
		$value = func_get_arg(1);
		$_SESSION[$prefix.$name] = serialize($value);
	}
	else if(1 === $num){
	    $arg0 = func_get_arg(0);
	    if (empty($arg0) && !is_int($arg0)) {
	        foreach ($_SESSION as $i=>$s) {
	            if (0 === strpos($i, DI_SESSION_PREFIX))
	                unset($_SESSION[$i]);
	        }
	        return;//如果第一个参数为null/false/空字符串,则清空所有DI前缀设置的会话
	    }
	    if(!isset($_SESSION[$prefix.$name])){
	        //throw new DIException("试图获取不存在的会话 [ {$prefix}{$name} ] ");
	        return null;
	    }
		return unserialize($_SESSION[$prefix.$name]);
	}
	else if(0 === $num){
		$zezzion = array();//DI专用的session
		foreach ($_SESSION as $i=>$s) {
			if(0===strpos($i, $prefix) && $i!=$prefix)
				$zezzion[$i] = unserialize($s);
		}
		return $zezzion;
	}
}

//获取包括常规session在内的所有会话, private设置为true时仅获取私有会话(带前缀的会话)。用法同session()不传参。
function session_all($private = false){
	$zezzion = array();//DI专用的session
	$session = array();//常规的session
	$prefix = DI_SESSION_PREFIX;
	foreach ($_SESSION as $i=>$s) {
		if(0===strpos($i, $prefix) && $i!=$prefix)
			$zezzion[$i] = unserialize($s);
		else
			$session[$i] = $s;
	}

	if($private){
		return $zezzion;
	}else{
		return array_merge($zezzion, $session);
	}
}

//会话是否存在
function session_exists($key, $prefix=null){
	empty($prefix) && !is_numeric($prefix) && $prefix = DI_SESSION_PREFIX;
	$fullkey = $prefix . $key;
	if (empty($fullkey) && !is_numeric($fullkey)) {
	    return false;
	} else {
    	return isset($_SESSION[$fullkey]);
	}
}


/**
 * 删除某个key的会话(注意第二个参数是严格模式开关)
 * @param string $key 要删除的会话名（可能含前缀）
 * @param boolean $strict 如果指定为true，则在删除不存在的会话时抛出异常
 * @param string $prefix 会话名前缀
 * @throws DIException
 */
function session_remove($key, $strict = false, $prefix = null){
    $no_pre = empty($prefix) && !is_numeric($prefix);
    $no_pre && $prefix = DI_SESSION_PREFIX;
    $fullkey = $prefix . $key;
    $no_fullkey = empty($fullkey) && !is_numeric($fullkey);
    $no_dikey = (!$no_pre && $fullkey === $prefix);//session的key刚好等于前缀，且有显式设置前缀
    
    if (!$no_fullkey && isset($_SESSION[$fullkey])) {
        if(!$no_dikey) unset($_SESSION[$fullkey]);
    } elseif ($strict) {
        throw new DIException("会话{$fullkey}不存在，不需要删除");
    }
}

//方便参数获取（注意合并顺序，可能会影响到路由的最终值）
$GLOBALS['request_args'] = $_GET + $_POST + $_REQUEST;
function arg($name = null, $default = null, $callback_funcname = null) {
    if($name){
        if(!isset($GLOBALS['request_args'][$name]))return $default;
        $arg = $GLOBALS['request_args'][$name];
    }else{
        $arg = $GLOBALS['request_args'];
    }
    if($callback_funcname)array_walk_recursive($arg, $callback_funcname);
    return $arg;
}


function dump($var, $exit = false){
    $output = print_r($var, true);
    //if(!DI_DEBUG_MODE)return error_log(str_replace("\n", '', $output));//此行准备删除
    $content = "<div align=left><pre>\n" .htmlspecialchars($output). "\n</pre></div>\n";
    echo "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">";
    echo "</head><body>{$content}</body></html>";
    if($exit) exit();
}

function putjson($code, $data = null, $msg = ''){
    //application/x-javascript和application/json会触发IE下载，暂时取消
    echo json_encode(compact('code', 'data', 'msg'));
    exit;
}

//jsonp数据输出
function putjsonp($code, $data = null, $msg = '', $callback='callback'){
    header("Content-Type: application/x-javascript; charset=UTF-8");
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST');
    $json = json_encode(compact('code', 'data', 'msg'));
    if( !empty($_GET[$callback])){
        exit ($_GET[$callback]. '('. $json . ')');
    }else{
        exit ($json);
    }
}

//js的alert()
function putalert($msg = null, $url = null){
    header("Content-type: text/html; charset=utf-8");
    $alert_msg = null === $msg ? '' : "alert('$msg');";
    if( empty($url) ) {
        $gourl = 'history.go(-1);';
    }else{
        $gourl = "window.location.href = '{$url}'";
    }
    exit ("<script>{$alert_msg} {$gourl}</script>");
}

/**
 * 超级输出
 * 默认采用json输出
 * 除了return类型，其它类型的执行后将终止程序
 * 依赖于arg(),putalert(),putjson(),putjsonp()
 * @param array $options 扩展参数集，视数据输出类型，指定所需的值
 */
function superput($code = 0, $data = null, $msg = '', $options = array(), $putTypeParamName = 'puttype') {
    @$return = $options['return'] ? $options['return'] : null;
    @$redirect = $options['redirect'] ? $options['redirect'] : null;
    @$text = $options['text'] ? $options['text'] : '';
    @$script = $options['script'] ? $options['script'] : '';
    @$html = $options['html'] ? $options['html'] : '';
    //可以考虑XML输出putxml()，将code,data,msg封装

    $putType = arg($putTypeParamName, 'json');
    if ('return' == $putType) { return $return; }
    if ('redirect' == $putType) { exit("<script>window.location.href='{$redirect}'</script>"); }
    if ('alert' == $putType) { putalert($msg, $redirect); } //使用alert()时，$redirect可不指定
    if ('text' == $putType) { header('Content-type: text/plain; charset=utf-8'); exit(strval($text)); }
    if ('script' == $putType) { header('Content-type: application/x-javascript; charset=utf-8'); exit(strval($script)); }
    if ('html' == $putType) { header('Content-type: text/html; charset=utf-8'); exit(strval($html)); }
    'json' == $putType && putjson($code, $data, $msg);
    'jsonp' == $putType && putjsonp($code, $data, $msg);
}

/**
 * 重定向，在真正需要301/302响应时，才能使用
 */
function redirect($url){
    header("Location: {$url}");
    exit;
}

/**
 * 生成URL
 * @param string $shell “?”之后紧跟的URL指令，如“test-test”、“a.b”
 * @param array $params GET参数，键名必须符合变量命名规范
 * @return string
 */
function url($shell, array $params = array()){
    if ('' === $shell) return './';
    $u = './?' . $shell;
    foreach ($params as $k => $v) {
        if (!is_numeric($k) && (is_string($v) || is_numeric($v) || is_bool($v)))
            $u .= "&{$k}={$v}";
    }
    return $u;
}

/**
 * 获取当前执行的URL前缀，截至当前执行目录，尾部有“/”
 * 例如：
 *      在http://danmu.me/pub/danmu/index.php中，
 *      执行后得到http://danmu.me/pub/danmu/
 * @return string
 */
function url_prefix($protocolPre = 'http://'){
    $di_sn = $_SERVER['SCRIPT_NAME'];
    $di_num = strrpos($di_sn, '/');
    $di_ppr = substr($di_sn, 1, $di_num);//所在目录的相对路径，如“danmu/”
    return "{$protocolPre}{$_SERVER['HTTP_HOST']}/{$di_ppr}";
}

/**
 * 获取去除端口号的HOSTNAME
 * TIP：使用$_SERVER['SERVER_NAME']并不保险，有可能会获取到带有*号的自定义名称
 */
function host_name(){
    $h = $_SERVER['HTTP_HOST'];
    $pos = strpos($h, ':');
    $len = false !== $pos ? $pos : strlen($h);
    $hostname = substr($h, 0, $len);
    return $hostname;
}

/**
 * 获取当前执行的WEB目录前缀，截至当前执行目录，头部和尾部有“/”
 * 例如：
 *      WEB根目录是 /usr/www/，要执行的脚本文件所在路径为/usr/www/aaa/index.php，
 *      则该脚本所在目录相对WEB根目录的路径值为：/aaa/，该值可由此方法得到。
 */
function path_prefix(){
    $di_sn = $_SERVER['SCRIPT_NAME'];
    $di_num = strrpos($di_sn, '/');
    $di_ppr = substr($di_sn, 1, $di_num);//所在目录的相对路径，如“danmu/”
    return "/{$di_ppr}";
}

/**
 * 获取$_SERVER['REQUEST_URI']中去除“QUERY_STRING”和前面“/”之后的部分
 * 例如：
 *      URL为http://127.0.0.1/shell.linenet?hehe.nima&m=n!abc#a，
 *      则URI为/shell.linenet?hehe.nima&m=n!abc，
 *      处理后得到shell.linenet
 */
function uri_prefix(){
    $uri = $_SERVER['REQUEST_URI'];
    $path_prefix = path_prefix();
    $len = false === ($len = strpos($uri, '?')) ? strlen($uri) : $len;
    $pre = strval(substr($uri, 1, $len - 1));
    return $pre;
}

/**
 * 在uri_prefix()基础上，去除真实目录路径部分（当index.php不位于文档根目录时才需要用）
 * 例如：
 *      URL为http://127.0.0.1/pub/danmu/shell.linenet?hehe.nima&m=n!abc#a，
 *      则URI为/shell.linenet?hehe.nima&m=n!abc，
 *      处理后得到shell.linenet
 * 注意：该方法要配合.htaccess文件使用才有效。
 * 具体如下：
 *      假定服务器文档根目录为/usr/www/
 *      文件index.php为启动脚本，放置在虚拟相对于/usr/www/的虚拟目录pub/danmu/之下，可以视为/usr/www/pub/danmu/index.php
 *      在虚拟目录中，pub/danmu/.htaccess文件的RewriteRule需要作特殊处理：
 *          <IfModule mod_rewrite.c>
 *          RewriteEngine on
 *          RewriteBase /
 *          RewriteCond %{REQUEST_FILENAME} !-f
 *          RewriteCond %{REQUEST_FILENAME} !-d
 *          RewriteRule . pub/danmu/index.php
 *          </IfModule>
 */
function uri_pure(){
    $uri = $_SERVER['REQUEST_URI'];
    $start = strlen(path_prefix());
    $len = false === ($len = strpos($uri, '?')) ? strlen($uri) : $len;
    $pure = strval(substr($uri, $start, $len - $start));
    return $pure;
}

/**
 * 取得访问IP
 * @since 2014-12-29
 */
function getip(){
    if (!empty($_SERVER['HTTP_CLIENT_IP'])){ //check ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }elseif(!empty($_SERVER['HTTP_CDN_SRC_IP'])){
        $ip = $_SERVER['HTTP_CDN_SRC_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){  //to check ip is pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else{
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}
