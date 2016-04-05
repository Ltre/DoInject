<?php

/* 事件监听机制，用于扩展支持 */

/**
 * 监听器/观察者
 * 由前置增强、中置增强、后置增强构成。
 * 其中，前置增强、后置增强的执行结果分别保存在DIBase子类的$_before、$_after属性，类型为array
 * 		中置增强不需要外置的存储器（如$_before、$_after）。
 * 要使用中置增强，只需要在为DIBase的非抽象子类的方法参数传入callback即可，
 * 		在具体的子类方法中，可以手动获取到这些callback的返回值，
 * 		这也侧面说明，中置增强的返回值可以直接在DIBase非抽象子类方法体中获取。
 * DIEventListener子接口命名规范：严格依照 DIEvent_XxxxYyyZzzListener
 */
interface DIEventListener {}

//实例化
interface DIEvent_ObjCreateListener extends DIEventListener {
	function beforeObjCreate();
	function onObjCreate();
	function afterObjCreate();
}

//调用对象
interface DIEvent_ObjCallListener extends DIEventListener {
	function beforeObjCall();
	function onObjCall();
	function afterObjCall();
}

//调用类
interface DIEvent_ClassCallListener extends DIEventListener {
	function beforeClassCall();
	function onClassCall();
	function afterClassCall();
}

//读取类的实例变量
interface DIEvent_GetVariableListener extends DIEventListener {
	function beforeGetVariable();
	function onGetVariable();
	function afterGetVariable();
}

//读取类的静态变量
interface DIEvent_GetStaticVariableListener extends DIEventListener {
	function beforeGetStaticVariable();
	function onGetStaticVariable();
	function afterGetStaticVariable();
}

//写入类的实例变量
interface DIEvent_SetVariableListener extends DIEventListener {
	function beforeSetVariable();
	function onSetVariable();
	function afterSetVariable();
}

//写入类的静态变量
interface DIEvent_SetStaticVariableListener extends DIEventListener {
	function beforeSetStaticVariable();
	function onSetStaticVariable();
	function afterSetStaticVariable();
}

//向类的实例追加成员变量
interface DIEvent_AppendVariableListener extends DIEventListener {
	function beforeAppendVariable();
	function onAppendVariable();
	function afterAppendVariable();
}

//调用类的实例方法：[__call()提供支持]
interface DIEvent_CallFuncListener extends DIEventListener {
	function beforeCallFunc();
	function onCallFunc();
	function afterCallFunc();
}

//调用类的静态方法
interface DIEvent_CallStaticFuncListener extends DIEventListener {
	function beforeCallStaticFunc();
	function onCallStaticFunc();
	function afterCallStaticFunc();
}

//向类的实例追加方法
interface DIEvent_AppendFuncListener extends DIEventListener {
	function beforeAppendFunc();
	function onAppendFunc();
	function afterAppendFunc();
}

//销毁
interface DIEvent_DestroyListener extends DIEventListener {
	function beforeDestroy();
	function onDestroy();
	function afterDestroy();
}


//事件/主题。为子类提供使用观察者模式的支持。
abstract class DIEvent {
	
	protected $eventListeners = array();	//DIEventListener
	protected $eventType;	//当前事件类型，由此决定notify()方法执行情况
	protected $_emitPool = array();//模拟Node.js的Emitter模型
	
	function attachEventListener(DIEventListener $ael){
		array_push($this->eventListeners, $ael);
	}
	
	function detachEventListener(DIEventListener $ael){
		$this->eventListeners = array_filter($this->eventListeners, function ($var) use ($ael){
			return $var != $ael;
		});
	}

	function on($handle, $cb){
	    if (!is_string($handle)&&!is_numeric($handle) || ! is_callable($cb)) {
	        $this_class = get_class($this);
	        $d = array_item(debug_backtrace(), 0);
	        $info = var_export(array('file' => $d['file'], 'line' => $d['line']), true);
	        throw new DIException("注册emit事件出错，请确保handle为数字或字符串、cb为callable类型。上级调用信息：{$info}");
	    }
	    $index = '_'.sha1($handle);
	    $emitter = array('handle' => $handle, 'cb' => $cb);
	    if (key_exists($index, $this->_emitPool)) return;
	    $this->_emitPool[$index] = $emitter;
	}
	
	function emit($handle, array $args = array()){
	    $this_class = get_class($this);
	    $index = '_'.sha1($handle);
	    if (! key_exists($index, $this->_emitPool)) {
	        throw new DIException("对象[#{$this_class}]：不存在emit句柄[#{$handle}].");
	    }
	    $cb = $this->_emitPool[$index]['cb'];
	    if (! is_callable($cb)) {
	        $this_class = get_class($this);
	        throw new DIException("对象[#{$this_class}]：emit句柄[#{$handle}]对应的注册值不是callable类型.");
	    }
	    call_user_func_array($cb, $args);
	}
	
	//如何做到自动通知？
	function notify(){
		foreach ($this->eventListeners as $el){
			foreach (get_class_methods($el) as $m){
				call_user_func_array(array($el, $m), array());
			}
		}
	}
	
}