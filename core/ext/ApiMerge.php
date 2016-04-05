<?php
/**
 * 用于合并API调用的工具
 * @version 1.0.0
 * @author Ltre<ltrele@yeah.net>
 * @since 2015-06-04
 */
class ApiMerge extends ApiMergeGlue {
    function config(array $conf){
        call_user_func_array($this->_method_config, func_get_args());
    }
    function define($id, $cmd){
        return call_user_func_array($this->_method_define, func_get_args());
    }
    function invoke($apis = array(), Closure $callback = null){
        $apis = is_array($apis) ? $apis : array($apis);
        $callback || $callback = function(){};
        return call_user_func_array($this->_method_invoke, array($apis, $callback));
    }
    //客户端传参格式：reqList:{req1:[1,2,3],req2:["a","b","c"],req3:null}，可嵌套多层数组
    function easyInvoke(Closure $callback = null){
        $callback || $callback = function(){};
        $reqList = arg('reqList');
        foreach ($reqList as &$r) { $r = (array)$r; }
        return $this->invoke($reqList, $callback);
    }
}


class ApiMergeBase {
    //模块池，使用后期静态绑定，以便子节点的操作结果存储在父节点
    static public $pool = array();
    static public $base = '';
    static public $alias = array();
}


//封装模块
class ApiMergeDefine extends ApiMergeBase {
    public function define($id, $cmd){
        static::$pool[$id] = $cmd;
    }
}


//引用模块
class ApiMergeInvoke extends ApiMergeBase {
    public $apis = array();
    
    public function invoke($apis, $callback){
    	$results = array();
    	foreach ($apis as $left => $right) {
    	    $r = $this->_apisFilter($left, $right);
    	    $id = $r==2 ? $this->_getRealID($left) : $this->_getRealID($right);
    	    $params = $r==2 ? $this->_packParams($right) : array();
    	    $callee = $this->_getCallee($id);
    	    $results[$id] = call_user_func_array($callee, $params);
    	}
    	call_user_func_array($callback, $results);
    	return $results;
    }
    //检测引入参数
    private function _apisFilter($left, $right){
        $re = '/(?:[a-z][a-z0-9_]*)/is';
        $leftIsID = preg_match($re, $left);
        $leftIsIndex = is_int($left) && $left >= 0;
        $rightIsID = is_string($right) && preg_match($re, $right);
        if ($leftIsID) return 2;//传了键值对：id=>参数表
        if ($leftIsIndex && $rightIsID) return 1;//只传了id
        throw new Exception('invoke操作中：引入参数格式有误', 0, NULL);
    }
    //取实际ID的优先级：alias > base
    private function _getRealID($id){
        foreach (static::$alias as $k => $v) {
            if ($k == $id) return $v;
        }
        return static::$base ? (static::$base.'/'.$id) : $id;
    }
    //获取需要回调的callable
    private function _getCallee($id){
        @$callee = static::$pool[$id];
        $inPool = $callee instanceof Closure;
        if (! $inPool) {
            $ex = explode('/', $id);
            $callee = array(new $ex[0], $ex[1]);
        }
        return $callee;
    }
    //正确包装参数表(仅包裹数字、字符串，若要传数组，请再自行包一层array)
    private function _packParams($params){
        return is_array($params) ? $params : array($params);
    }
}


//配置模块共有属性
class ApiMergeConfig extends ApiMergeBase {
    public function config($conf){
        @static::$base = $conf['base'] ?: '';
        @static::$alias = $conf['alias'] ?: array();
    }
}


//合并模块
class ApiMergeGlue extends ApiMergeBase {
    private $_names = array('define', 'invoke', 'config');
    
    //在重新初始化组合对象ApiMerge时，清空所有静态属性
    private function _init(){
        static::$pool = array();
        static::$base = '';
        static::$alias = array();
    }
    
    private function _combine(){
        foreach ($this->_names as $n) {
            $class = 'ApiMerge'.ucfirst($n);
            $o = new $class();
            foreach ($o as $i => $e) { $this->$i = $e; }
            $this->{'_method_'.$n} = function() use ($o, $n) {
                return call_user_func_array(array($o, $n), func_get_args());
            };
        }
    }
    
    public function __construct(){
        $this->_init();
        $this->_combine();
    }
}





//示例API
class ApiDemo {
    function api1(){
        return 'this is api1';
    }
    function api2($a, $b){
        return compact('a', 'b');
    }
    function api3($content){
        file_put_contents(DI_LOG_PATH.'log.api3.txt', $content);//do something
    }
}

//测试用例
class ApiMergeTest {
    static function test1(){
        $am = new ApiMerge();
        $am->invoke(array('ApiDemo/api1','ApiDemo/api2'=>array('a1', 'b2')), function($A1, $A2){
            dump(compact('A1', 'A2'));
        });
        $am->invoke(array('ApiDemo/api3'=>array('this is log')));
    }
    static function test2(){
        $am = new ApiMerge();
        $am->define('mypack', function(){
            echo 'this is mypack<br>';
        });
        $am->invoke(array('mypack'), function(){
            echo 'this is callback with mypack<br>';
        });
    }
    static function test3(){
        $am = new ApiMerge();
        $am->define('mypack', function($p1){
            echo 'this is mypack and param<p1> is '.$p1.'<br>';
            return 'ret of mypack';
        });
        $am->invoke(array('mypack'=>array('p1_value')), function($M){
            echo 'this is callback with mypack<br>';
            echo 'parameter<M> is in callback：';
            echo $M;
        });
    }
    static function test4(){
        $am = new ApiMerge();
        $results = $am->invoke(array(
            'ApiDemo/api1' => array(),
            'ApiDemo/api2' => array('a', 'b'),
            'ApiDemo/api3' => array('contents')
        ), function($A1, $A2, $A3){
            dump(compact('A1', 'A2', 'A3'));
        });
        dump($results);
    }
    static function test5(){
        $am = new ApiMerge();
        $am->config(array(
            'base' => 'ApiDemo',
            'alias' => array(
                'fooAlia' => 'ApiDemo/api3'
            ),
        ));
        $results = $am->invoke(array(
            'api1' => array(),
            'api2' => array('a', 'b'),
            'fooAlia' => array('contents')
        ), function($A1, $A2, $A3){
            dump(compact('A1', 'A2', 'A3'));
        });
        dump($results);
    }
    static function test6(){
        $am = new ApiMerge();
        $am->config(array(
        	'base' => 'ApiDemo',
            'alias' => array(
                'foo' => 'a/b/c/d'
            )
        ));
        $am->define('a/b/c/d', function($p) use ($am) {
            echo "this is $p <br>";
            return $am->invoke('api1');
        });
        $am->invoke(array('foo'=>'valueP'));
    }
    static function test7(){ //easyInvoke封装客户端响应
        if (! arg('flag')) {
            $script = '$.get("./?test/apiMerge",{reqList:{req1:[1,2,3],req2:["a","b","c"],req3:null}, flag:1},function(j){},"jsonp")';
            $html = '<!DOCTYPE html><head><script src="http://cdn.bootcss.com/jquery/2.1.4/jquery.min.js"></script><script>function sendMulti(){'.$script.'}</script></head><body><button onclick="sendMulti()">发送数据</button></body></html>';
            exit($html);
        }
        $am = new ApiMerge();
        $am->define('req1', function($p1,$p2,$p3){return array('p1'=>++$p1,'p2'=>++$p2,'p3'=>++$p3);});
        $am->define('req2', function($pa,$pb,$pc){return $pa.$pb.$pc;});
        $am->define('req3', function(){return !!rand(0, 1);});
        @$ret += $am->easyInvoke(function($R1,$R2,$R3) use (&$ret){
            $ret['sha1'] = sha1(serialize($R1).serialize($R2).serialize($R3));
            $ret['md5'] = md5(serialize($R1).serialize($R2).serialize($R3));
        });
        var_dump($ret);
    }
}