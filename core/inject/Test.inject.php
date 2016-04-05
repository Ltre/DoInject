<?php

class TestInject extends DIInject {
	
	//前置增强在XxxDo::test()方法之前执行
	function beforeTest( $bridge=array() ){
		echo 'TestInject::beforeTest()<br>';
		var_dump($bridge);echo '<br>';
		if(false) die;//这里可以控制XxxDo::test()行为
	}
	
	//后置增强在XxxDo::test()方法之后执行
	function afterTest( $bridge=array() ){
		echo 'TestInject::afterTest()<br>';
		var_dump($bridge);echo '<br>';
	}
	
	//环绕增强将被嵌入到XxxDo::test()方法中
	function onTest( $bridge=array() ){
		echo 'ontest()...<br>';
		$funcs['first'] = function(){
			echo 'func1()执行<br>';
		};
		$funcs['second'] = function(){
			echo 'func2()执行<br>';
		};
		$funcs['third'] = function(){
			echo 'func3()执行<br>';
		};
		return $funcs;
	}
	
	
}