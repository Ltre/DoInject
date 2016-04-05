<?php

/* 注入增强，仅供DIDo使用 */

abstract class DIInject extends DIBase {
	
	/** @var DIDo 持有引用它本身的控制器XxxDo，可以作为监视XxxDo的依据 */
	protected $_do;
	
	final function __construct(DIDo $_do){
		$this->_do = $_do;
	}
	
}




/**
 * 向对象实例方法注入前置或后置代码
 * @param object $obj 对象句柄
 * @param callable $callback 回调函数
 * @param array $callback_args 回调函数的参数
 * @param string $memberFuncName 要注册的非static方法名
 * @return int 状态码 (
 * 		-1	-> 不是对象句柄
 * 		-2	-> 不是回调函数
 * 		-3	-> 要注册的方法不存在
 * 		-4	-> 回调函数的参数表非数组形式
 * )
 * 处于测试阶段，$obj->__call()处代码有问题
 * 已被inject()和inject_full()取代，即将废除
 */
function injectObjFunc($obj, $callback, $memberFuncName=null, $callback_args=array()){

    if( !is_object($obj) ) return -1;
    if( !is_callable($callback) ) return -2;
    if( !$memberFuncName && !method_exists($obj, $memberFuncName) ) return -3;
    if( !is_array($callback_args) ) return -4;

    try{
        $obj->__call = function($name, $args) use ($obj, $callback, $memberFuncName, $callback_args){
            	
            $bind = false;
            if(! $memberFuncName){
                $bind = true;	//不指定方法名，则视为所有方法都绑定回调函数
            }else if(! strcmp($name, $memberFuncName)){
                $bind = true;	//指定了方法名，且调用时的方法名和该指定方法名相同时，绑定回调函数
            }
            if($bind) call_user_func_array($callback, $callback_args);
            	
            call_user_func_array(array($obj, $name), $args); //正常执行所调用的成员方法
            	
        };
    }catch (DIException $e){
        throw $e;
    }

    return 0;
}


/**
 * 向对象实例方法注入前置或后置代码，只针对某一端注入
 * @param DIBase $obj 对象句柄
 * @param string $memberFuncName 要注册的非static方法名
 * @param callable $callback 回调函数
 * @param array $callback_args 回调函数的参数
 * @param bool $isAfter 是否作为后置注入
 * @return int 状态码 (
 * 		-1	-> 不是对象句柄
 * 		-2	-> 不是回调函数
 * 		-3	-> 要注册的方法不存在
 * 		-4	-> 回调函数的参数表非数组形式
 * )
 */
function inject(DIBase $obj, $memberFuncName, $callback, array $callback_args=array(), $isAfter=false){

    if( !is_object($obj) ) return -1;
    if( !is_callable($callback) ) return -2;
    if( !$memberFuncName && !method_exists($obj, $memberFuncName) ) return -3;
    if( !is_array($callback_args) ) return -4;

    //生成继承类，注入自定义代码，保持原有的参数传入和功能代码段
    $clazz = get_class($obj);
    $rc = new ReflectionClass($clazz);
    $num = $rc->getMethod($memberFuncName)->getNumberOfParameters();

    $vars = array(
        'var $callback;',
        'var $callback_args;',
    );

    $params = params_to_str($num);
    $funcs = array(
        "function $memberFuncName ( $params ){
        \$args = func_get_args();
        ".( $isAfter ? '' : "call_user_func_array(\$this->callback, \$this->callback_args);" )."
        //call_user_func_array(array('$clazz', '$memberFuncName'),\$args);//方式1
        //parent::{$memberFuncName}( $params );//方式2
        call_user_func_array('parent::{$memberFuncName}', \$args);//方式3
        ".( ! $isAfter ? '' : "call_user_func_array(\$this->callback, \$this->callback_args);" )."
		}",
		'function __construct(){
			$args = func_get_args();
			$last=$args[count($args)-1];
			$this->callback = $last[0];
			$this->callback_args = $last[1];
			call_user_func_array("parent::__construct", $args);
        }'
    );

    $tmpObj = create_class("tmp{$clazz}", false, $clazz, null, $vars, $funcs)->newInstance(array(array($callback, $callback_args)));
    return $tmpObj;
}

/**
* 向对象实例方法注入前置或后置代码，可以同时对前后注入
* @param DIBase $obj 对象句柄
* @param string $memberFuncName 要注册的非static方法名
* @param array $before 前置增强array(callable, param_arr)
* @param array $after 后置增强array(callable, param_arr)
* @return int 状态码 (
* 		-1	-> 不是对象句柄
* 		-2	-> 没有检测到回调参数（$before和$after两者必有其一是array(callable, param_arr)形式，否则报该错误）
* 		-3	-> 要注册的方法不存在
* 		-4	-> 检测到回调函数的参数非数组形式
* )
*/
function inject_full(DIBase $obj, $memberFuncName, array $before=array(), array $after=array()){

    $before = empty($before)||2!=count($before) ? array(function(){},array()) : $before;
    $after = empty($after)||2!=count($after) ? array(function(){},array()) : $after;

    if( !is_object($obj) ) return -1;
    if( ! ( 2==count($before) && is_callable($before[0]) || 2==count($after) && is_callable($after[0]) ) ) return -2;
    if( !$memberFuncName && !method_exists($obj, $memberFuncName) ) return -3;
    if( ! ( 2==count($before) && is_array($before[1]) || 2==count($after) && is_array($after[1]) ) ) return -4;


    //生成继承类，注入自定义代码，保持原有的参数传入和功能代码段
    $clazz = get_class($obj);
    $rc = new ReflectionClass($clazz);
    $num = $rc->getMethod($memberFuncName)->getNumberOfParameters();

	$vars = array(
    	'var $bf_callback;',
    	'var $bf_callback_args;',
    	'var $af_callback;',
    	'var $af_callback_args;',
    );

    $params = params_to_str($num);
    $funcs = array(
        "function $memberFuncName ( $params ){
            \$args = func_get_args();
            call_user_func_array(\$this->bf_callback, \$this->bf_callback_args);
            //call_user_func_array(array('$clazz', '$memberFuncName'),\$args);//方式1
            //parent::{$memberFuncName}( $params );//方式2
            call_user_func_array('parent::{$memberFuncName}', \$args);//方式3
    		call_user_func_array(\$this->af_callback, \$this->af_callback_args);
    	}",
        'function __construct(){
    		$args = func_get_args();
    		$last1 = $args[count($args)-2];
    		$this->bf_callback = $last1[0];
    		$this->bf_callback_args = $last1[1];
    		$last2 = $args[count($args)-1];
    		$this->af_callback = $last2[0];
    		$this->af_callback_args = $last2[1];
    		call_user_func_array("parent::__construct", $args);
        }'
    );

	$tmpObj = create_class("tmp{$clazz}", false, $clazz, null, $vars, $funcs)->newInstance(array( $before, $after ));
	return $tmpObj;
}


/**
 * 实现动态注入或批量注入
 * 注：需求尚未明确
 */
function inject_dymic(){

}

/**
 * 向游离的函数注入代码
 * 注：技术可行性较低
 */
function inject_func($func, $callback, $callback_args=array(), $isAfter=false){
    
}

/**
 * 重复调用
 * 用法同call_user_func_array()
 * 参数$useCache默认不使用结果缓存
 */
function recall($callback, $param_arr, $useCache = false){
    if ('recall' == strtolower(__FUNCTION__))
        throw new DIException('不能使用recall()调用自己');
    $key = 'recall_ret_' . sha1( ( is_array($callback) ? get_class($callback[0]).$callback[1] : json_encode($callback) ) . serialize($param_arr) );
    $cache = $useCache ? $GLOBALS[$key] : false;
    if ($cache) return $cache;
    $ret = call_user_func_array($callback, $param_arr);
    $GLOBALS[$key] = $ret;
    return $ret;
}