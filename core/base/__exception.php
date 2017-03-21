<?php

/**
 * 核心出错异常类
 * 可以根据DEBUG模式的启用情况，给出常规处理方式。
 * 处理方式：覆盖__construct方法。
 */
class DIException extends Exception {
	
    private $args = array();
    
    /**
     * @param string $message 错误信息
     * @param string $errPage 错误页，用来include。当处于处于DI_DEBUG_MODE时，可能会调用。
     * @param array|callable $callback 回调体。如果在回调体中终止程序，则$errPage参数会失效。
     */
	function __construct($message, $errPage=DI_PAGE_503, $callback=NULL, $code=0, $previous=NULL){
        $this->args = array(
            'message' => $message,
            'errPage' => $errPage,
            'callback' => $callback,
            'code' => $code,
            'previous' => $previous,
        );
	}
    
    //处理异常过程
    function deal(){
        $message = $this->args['message'];
        $errPage = $this->args['errPage'];
        $callback = $this->args['callback'];
        $code = $this->args['code'];
        $previous = $this->args['previous'];
        
        //记录错误日志到文件或数据库
        if (DI_IO_RWFUNC_ENABLE) {
            $time = date('Y-m-d H:i:s');
            $file = DI_LOG_PATH . 'exception_' . date('Y-m-d') . '.txt';
            $link = fopen($file, 'a+');
            $msg = "=========================={$time}==========================\r\n";
            @$msg .= "    {$_SERVER['SERVER_PROTOCOL']}    {$_SERVER['SERVER_NAME']}" . (80==$_SERVER['SERVER_PORT']?'':':'.$_SERVER['SERVER_PORT']) . "{$_SERVER['REQUEST_URI']}    REFERER[{$_SERVER['HTTP_REFERER']}]    REMOTE_ADDR[{$_SERVER['REMOTE_ADDR']}]    REQUEST_METHOD[{$_SERVER['REQUEST_METHOD']}]    $message\r\n";
            fwrite($link, $msg);
        } else {
            //需要提前配置好数据库
        }

        //执行回调
        if (is_callable($callback)) {
            $callback();
        } elseif (is_array($callback) && 2==count($callback) && is_callable($callback[0]) && is_array($callback[1])) {
            call_user_func_array($callback[0], $callback[1]);
        }

		if(DI_DEBUG_MODE){
            dump($this);
		}
		else{
    		//这里不继承调用父类构造器，阻止显式抛出异常，这样就不用在每个catch块中进行手动判断DEBUG模式了
            if (is_file($errPage)) {
                include $errPage;
            }
		}
    }
	
}



/**
 * 错误捕捉类(仅在非调试模式触发)
 * 目前除了E_ERROR以外，应该都可以捕捉
 */
class DIError {

    private $errorHandler;
    private $addtionHander;
    private $errPage;
    private $ignoreErrs;
    
    function __construct(){
        date_default_timezone_set('PRC');
        $this->initErrorHandler();
        $this->errPage = '';
        $this->ignoreErrs = array();
    }
    
    /*
     * set_error_handler()回调处理
     * int $error 错误类型
     * string $message 出错提示
     * string $file 所在文件路径
     * numeric $line 出错所在行
     */
    private function initErrorHandler(){
        $this->errorHandler = function($errno, $errstr, $errfile, $errline, $errcontext){
            if (in_array($errno, $this->ignoreErrs)) {
                return;
            }
            //记录错误日志到文件或数据库
            if (DI_IO_RWFUNC_ENABLE) {
                $time = date('Y-m-d H:i:s');
                $file = DI_LOG_PATH . 'err_' . date('Y-m-d') . '.txt';
                $link = fopen($file, 'a+');
                $msg = "=========================={$time}==========================\r\n";
                @$msg .= "访问信息： {$_SERVER['SERVER_PROTOCOL']}    {$_SERVER['SERVER_NAME']}" . (80==$_SERVER['SERVER_PORT']?'':':'.$_SERVER['SERVER_PORT']) . "{$_SERVER['REQUEST_URI']}    REFERER[{$_SERVER['HTTP_REFERER']}]    REMOTE_ADDR[{$_SERVER['REMOTE_ADDR']}]    REQUEST_METHOD[{$_SERVER['REQUEST_METHOD']}]\r\n";
                $msg .= "错误类型：" . $this->friendlyErrorType($errno) . "\r\n";
                $msg .= "错误提示：{$errstr}\r\n";
                $msg .= "错误文件：{$errfile}\r\n";
                $msg .= "错误位置：{$errline}\r\n";
                fwrite($link, $msg);
            } else {
                //需要提前配置好数据库
            }
            //执行附加的回调过程
            $cb = $this->addtionHander;
            call_user_func_array($this->addtionHander, func_get_args());
            //处于非调试模式时，则会包含错误覆盖页面
            if (is_file($this->errPage)){
                include $this->errPage;
            }
        };
    }

    /*
     * 错误类型
     */
    private function friendlyErrorType($type)
    {
        switch($type)
        {
        	case E_ERROR: // 1
        	    return 'E_ERROR';
        	case E_WARNING: // 2
        	    return 'E_WARNING';
        	case E_PARSE: // 4
        	    return 'E_PARSE';
        	case E_NOTICE: // 8
        	    return 'E_NOTICE';
        	case E_CORE_ERROR: // 16
        	    return 'E_CORE_ERROR';
        	case E_CORE_WARNING: // 32
        	    return 'E_CORE_WARNING';
        	case E_CORE_ERROR: // 64
        	    return 'E_COMPILE_ERROR';
        	case E_CORE_WARNING: // 128
        	    return 'E_COMPILE_WARNING';
        	case E_USER_ERROR: // 256
        	    return 'E_USER_ERROR';
        	case E_USER_WARNING: // 512
        	    return 'E_USER_WARNING';
        	case E_USER_NOTICE: // 1024
        	    return 'E_USER_NOTICE';
        	case E_STRICT: // 2048
        	    return 'E_STRICT';
        	case E_RECOVERABLE_ERROR: // 4096
        	    return 'E_RECOVERABLE_ERROR';
        	case E_DEPRECATED: // 8192
        	    return 'E_DEPRECATED';
        	case E_USER_DEPRECATED: // 16384
        	    return 'E_USER_DEPRECATED';
        }
        return $type;
    }

    
    /**
     * 设置额外的处理过程
     * @param Closure $handler 该回调函数的参数和set_error_hander()指定的回调函数的一样
     */
    function setAddtionHandler(Closure $handler){
        $this->addtionHander = $handler;
    }
    
    /**
     * 设置出错时的覆盖页面
     * @param string $errPage
     */
    function setErrPage($errPage){
        $this->errPage = $errPage;
    }
    
    /**
     * 设置要忽略的错误类型
     * 默认忽略E_STRICT
     */
    function setIgnoreErrors($errors = array(2048)){
        $this->ignoreErrs = $errors;
    }
    
    /**
     * 开始捕捉错误
     */
    function beginTrace(){
        if (! DI_DEBUG_MODE) {
            set_error_handler($this->errorHandler);//仅在非调试模式触发
        }
    }
    
    /**
     * 停止捕捉错误
     */
    function endTrace(){
        restore_error_handler();
    }
    
    static function test(){
        $e = new DIError();
        $e->setErrPage(DI_PAGE_503);
        $e->setIgnoreErrors();
        $e->setAddtionHandler(function(){ dump(func_get_args()); });
        $e->beginTrace();
        {
            $a = new shabifdsafsaasf;//error code
        }
        $e->endTrace();
    }
    
}
