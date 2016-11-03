<?php

/**
 * DIDo抽象控制器，从ActionInvoker的`action`更名为现在的`do`
 * 将和注入增强DIInject协作，完整监视和导向Do方法的行为
 * 继承DIDo后，子类命名规则为XxxDo。
 */

abstract class DIDo extends DIBase{

	/** @var string 当前控制器的名称(去除Do后缀) */
	protected $_name;
	
	/** @var DIInject 对应的注入增强实例 */
	protected $_inject;

	/** @var array 与inject共享的信息数组 */
	protected $_bridge = array();
	
	/** @var DITpl引擎实例 */
	protected $_tpl;
	
	/** @var string 布局页面 */
	protected $_layout;
	
	/**
	 * Output
	 * @param string $tpl_name Do类名(无Do后缀)-方法名，全小写
	 * @param string $return 是否仅返回，默认执行完就结束程序
	 */
	protected function tpl($tpl_name=null, $return=false){
	    if(null===$tpl_name){
	        if (!! $this->_layout) {
	            $tpl_name = $this->_layout;//在$this->tpl()之前，设置layout属性，不需要文件后缀名
	        } else {
    	        $do_name = substr(get_class($this), 0, strripos(get_class($this), 'Do'));
    	        $debug_backtrace = debug_backtrace();
    	        $func_name = $debug_backtrace[1]['function'];
    	        if(! method_exists($do_name.'Do', $func_name)){
    	            throw new DIException("由于{$do_name}Do::{$func_name}组件不存在，导致输出模板[ $tpl_name ]时出错");
    	        }
    	        $tpl_name = strtolower($do_name . '-' . $func_name);
	        }
	    }
	    
	    $tplFile = DI_TPL_PATH . $tpl_name . (DI_SMARTY_DEFAULT?'.html':'.php');
	    if(! is_file($tplFile)){
	        throw new DIException("模板文件[ $tplFile ]不存在");
	    }
	    
	    if (DI_SMARTY_DEFAULT) {
	        $ret = $this->_tpl->showWithSmarty($tpl_name.'.html', $return);
	    } else {
	        $ret = $this->_tpl->showWithGeneral($tpl_name.'.php', $return);
	    }
	    
        if ($return) {
            return $ret;
        } else {
            exit;
        }
	}
	
	/**
	 * 直接使用smarty输出模板
	 * 设计背景：
	 *     当Smarty不被作为默认模板(DI_SMARTY_DEFAULT==false)时，tpl()将只能使用PHP常规输出，不能使用Smarty。
	 *     而使用该方法，则可以无视DI_SMARTY_DEFAULT的配置，采用Smarty输出。
	 */
	protected function stpl($tpl_name = null, $return = false){
	    if (null===$tpl_name) {
	        if (!! $this->_layout) {
	            $tpl_name = $this->_layout;//在$this->tpl()之前，设置layout属性，不需要文件后缀名
	        } else {
	            $do_name = substr(get_class($this), 0, strripos(get_class($this), 'Do'));
	            $debug_backtrace = debug_backtrace();
	            $func_name = $debug_backtrace[1]['function'];
	            if(! method_exists($do_name.'Do', $func_name)){
	                throw new DIException("由于{$do_name}Do::{$func_name}组件不存在，导致输出模板[ $tpl_name ]时出错");
	            }
	            $tpl_name = strtolower($do_name . '-' . $func_name);
	        }
	    }
	    
	    $tplFile = DI_TPL_PATH . $tpl_name . '.html';
	    if(! is_file($tplFile)){
	        throw new DIException("模板文件[ $tplFile ]不存在");
	    }
	    
	    $ret = $this->_tpl->showWithSmarty($tplFile, $return);
	    if ($return) {
	        return $ret;
	    } else {
	        exit;
	    }
	}
	
	//自定义的控制器初始化代码，在子类中实现
	protected function _init(){
	}
	
	final function __construct(){
		//取得控制器简化名称
		$clazz = get_class($this);
		$this->_name = substr($clazz, 0, strripos($clazz, 'Do'));
		//实例化inject,并将XxxDo本例注入到inject中,供inject方法使用（如果不存在对应的XxxInject， 则用这个对应的Inject名称创建类并实例化临时对象，进行假注入，防止报错）
		$inject = $this->_name . 'Inject';
		if (component_exist($inject)){
    		$this->_inject = new $inject($this);
    		if (method_exists($this->_inject, 'doInject')) {
    		    $this->_inject->doInject();//执行模块级过滤
    		}
		}
		//实例化并将XxxDo本例注入到DITpl中
		$this->_tpl = new DITpl($this);
		//执行自定义的控制器初始化代码
		$this->_init();
	}
	
	/*
	 * 本方法将覆盖DIBase中__call()方法，
	 * 终止套用DIEvent_CallFuncListener接口方法的约定。
	 * 重写该方法之后，将实现XxxDo控制对应的XxxInject增强代码的注入
	 * 在XxxDo中，每个控制器方法将在XxxInject中有同名的注入增强。
	 * 例如：XxxDo::yy()对应XxxInject中的beforeYy(),onYy(),afterYy().
	 * 注意：不允许重写本方法，否则以上特性均无效。
	 */
	final function __call($name, $args){
		//判断是否被注入inject
		if($this->_inject){
    		$has_before = 'has_before'; $has_on = 'has_on'; $has_after = 'has_after';
    		$before = 'before'; $on = 'on'; $after = 'after';
    		foreach (array('before', 'on', 'after') as $prep){
    			$hasPrep = 'has_' . $prep;//是否存在注入
    			$$prep .= ucfirst($name);//注入的方法名称（前置/环绕/后置）
    			
    			$$hasPrep = method_exists($this->_inject, $$prep);
    		}
    		
    		//invoke_method($this->_inject, $before, array($this->_bridge));//访问前置增强，并注入共享信息
    		$has_before && call_user_func_array(array($this->_inject, $before), array($this->_bridge));
    		
    		$on_ret = $has_on ? invoke_method($this->_inject, $on, array($this->_bridge)) : array();//获取onXxx()配置的注入function数组
    		$on_ret = $has_on ? call_user_func_array(array($this->_inject, $on), array($this->_bridge)) : array();//获取onXxx()配置的注入function数组
    		$args = empty($on_ret) ? $args : array_merge( $args, array( $on_ret ) );
    		$this->_invoke( $name, $args );//注入环绕代码数组到参数中，并执行XxxDo方法
    		
    		//invoke_method($this->_inject, $after, array($this->_bridge));//访问后置增强，并注入桥接信息
    		$has_after && call_user_func_array(array($this->_inject, $after), array($this->_bridge));
		}
		else{
		    $this->_invoke( $name, $args );
		}
		
	}
	
	
}


/* ------------------------- 与DIDo相关的实用函数 ------------------------- */


/**
 * 分派XxxDO::func()，否则视为/index.php的相对路径，找不到时输出404页
 * 也可以强制用第二个参数将shell指定为文件路径
 * 正常情况下HTTP响应200，不会使用301/302重定向
 * @notice
 *  依赖方法：component_exist()、invoke_method()
 *  依赖常量：DI_KEYWORD_DO、DI_PAGE_503
 * @author biao
 * @since 2014-12-16
 */
function dispatch($shell, $params = array()){
    //如果第二参数设置为false，则将shell视为文件路径
    if (false === $params) {
        require $shell;
        exit;
    }

    $ar = explode('/', $shell);
    $validate = is_string($shell) && !empty($ar) && 2 == count($ar);
    if ($validate) {
        $do = ucfirst($ar[0]) . ucfirst(DI_KEYWORD_DO);
        $func = $ar[1];
        $d = component_exist($do);
        $m = method_exists($do, $func);
        if ($d && $m) {
            invoke_method(new $do(), $func, $params);
            exit;
        }
    }

    if (file_exists($shell)) {
        require $shell;
        exit;
    }

    throw new DIException("shell参数[{$shell}]不是一个有效的DO指令，也不是一个文件路径，无法跳转");
}
