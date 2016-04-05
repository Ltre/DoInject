<?php

/* 框架基础架构 */


/**
 * 核心基类，为继承它的子类预留EventListener扩展的实现模板槽
 * 例如：DIDo继承本类后，可以重写__call()方法，
 * 		检测DIDo子类所实现的DIEventListener接口，
 * 		动态地套用这类接口的模板方法。
 */
abstract class DIBase extends DIEvent{
    
    /** @var array 前置增强保存的执行结果，暂时需要手动保存 */
    protected $_before = array();
    /** @var array 方法体本身保存的执行结果，暂时需要手动保存 */
    protected $_action = array();
    /** @var array 后置增强保存的执行结果，暂时需要手动保存*/
    protected $_after = array();
    
    /** @var array 保存虚拟成员方法数组，__set, __get触发使用 */
    protected $_lambda = array();
    /** @var array 保存虚拟成员变量数组，__set, __get触发使用*/
    protected $_vars = array();
    
    protected $isStrict = true;//禁用严格模式时，进行__set、__get时如遇冲突是否抛出异常(该参数放到构造器的所有参数最后)
    
    
    /*
     * 最后一个参数决定使用过程中被执行set操作时是否强制覆盖 
     * 注意：这里的参数是临时决定的，处于试验阶段，勿大范围使用 该功能尚未着手实现，需要在__set()改代码
     */
    function __construct() {
        $args = func_get_args();
        $last = array_pop($args);
        isset($last['isStrict']) && $this->isStrict = boolval($last['isStrict']);
    }
    
    
    /**
     * 获取虚拟成员变量数组
     * @return array
     */
    public function getVitualVars() {
        return $this->_vars;
    }
    
    
    /**
     * 获取虚拟成员方法数组
     * @return array
     */
    public function getLambdas() {
        return $this->_lambda;
    }


    // 如果子类继承该类时，没有覆盖该方法，将可以使用DIEvent_CallFuncListener预留的接口方法，实现前置/后置增强的定制
    function __call($name, $args) {
        $interfaces = class_implements($this);
        $has = in_array('DIEvent_CallFuncListener', $interfaces);
        
        $has && invoke_method($this, 'beforeCallFunc', array());
        $this->_invoke($name, $args);
        $has && invoke_method($this, 'afterCallFunc', array());
    }

    
    // 执行子类方法，在子类__call()方法覆盖本类的时候，提供给子类__call()复用 该模板方法不允许被重写
    protected final function _invoke($name, $args) {
        if (method_exists($this, $name)) { // TODO:这里还可以判断系统附加的方法访问性，二次决定是否执行之。### 该特性尚未实现
            call_user_func_array(array($this, $name), $args); // $m = new ReflectionMethod($this, $name);
        }
        elseif (array_key_exists($name, get_object_vars($this))) {
            if (is_callable(array($this, $name))) {
                call_user_func_array(array($this, $name), $args);
            } 
            else {
                throw new DIException('成员方法访问操作：' . get_class($this) . ' -> ' . $name . '成员变量不具备callable特性，不能调用<br>');
            }
        }
        elseif (array_key_exists($name, $this->_vars)) {
            throw new DIException('成员方法访问操作：所访问的虚拟成员[ ' . get_class($this) . '->' . "$name ]不可作为callable回调函数<br>");
        }
        elseif (array_key_exists($name, $this->_lambda)) {
            call_user_func_array($this->_lambda[$name], $args);
        }
    }
	
	
	//动态添加修改虚拟成员方法或成员变量，不会影响原有的成员方法或变量
	final function __set($name, $value){
		if(is_callable($value, true)){
			if(method_exists($this, $name)){
				throw new DIException('动态设置成员方法操作：在' . get_class($this) . "的方法[ $name ]已存在，不允许覆盖<br>");
			}
			else if(array_key_exists($name, $this->_vars)){
				throw new DIException('动态设置成员方法操作：在' . get_class($this) . "中已存在与[ $name ]同名的虚拟成员变量<br>");
			}
			else{
				$this->_lambda[$name] = $value;
			}
		}else{
			if(array_key_exists($name, get_object_vars($this))){
				throw new DIException('动态设置成员变量操作：在' . get_class($this) . "的成员变量/常量[ $name ]已存在，不允许覆盖<br>");
			}
			else if(array_key_exists($name, $this->_lambda)){
				throw new DIException('动态设置成员变量操作：在' . get_class($this) . "中已存在与[ $name ]同名的虚拟成员方法<br>");
			}
			else{
				$this->_vars[$name] = $value;
			}
		}
	}
	

	//动态获取原有成员或虚拟成员
	final function __get($name){
		if(array_key_exists($name, get_object_vars($this)) || array_key_exists($name, get_object_vars($this))){
			throw new DIException('获取成员变量/方法操作：' . get_class($this) . "中的成员[ $name ]受protected保护，不允许直接读取<br>");
		}
		else if( array_key_exists($name, $this->_lambda) ){
			return $this->_lambda[$name];
		}
		else if( array_key_exists($name, $this->_vars) ){
			return $this->_vars[$name];
		}
		else{
			throw new DIException('获取成员变量/方法操作：' . get_class($this) . "中不存在名为[ $name ]的成员<br>");
		}
	}
	
	
	//动态撤销虚拟成员变量或成员方法，不会影响原有的成员
	final function __unset($name){
		if(array_key_exists($name, get_object_vars($this))){
			throw new DIException('动态撤销成员变量/方法操作：' . get_class($this) . "的固有成员变量/常量[ $name ]不允许删除<br>");
		}
		else if(method_exists($this, $name)){
			throw new DIException('动态撤销成员变量/方法操作：' . get_class($this) . "的固有成员方法[ $name ]不允许删除<br>");
		}
		else if(! array_key_exists($name, $this->_vars) && ! array_key_exists($name, $this->_lambda)){
			throw new DIException('动态撤销成员变量/方法操作：在' . get_class($this) . "中，[ $name ]成员不存在<br>");
		}
		else{
			unset($this->_lambda[$name]);
		}
	}

	
	/* function __isset($name){
		
	}
	
	function __clone(){
	    
	} */
	
	/**
	 * @ClassUtilTools
	 * 类的实用方法
	 * 去除当前类名的前缀、后缀
	 * @param string $suffix 后缀
	 * @param string $prefix 前缀
	 * @return string
	 */
	function getShortClassName($suffix = '', $prefix = ''){
	    $clazz = get_class($this);
	    empty($suffix) || $clazz = substr($clazz, 0, strripos($clazz, $suffix));
	    empty($prefix) || $clazz = substr($clazz, stripos($clazz, $prefix) + 1);
	    return $clazz;
	}
}






/**
 * 系统上下文
 * 一般是针对应用执行状态进行开发的。
 * 如：上次执行位置，本次执行位置，下次执行位置，历时执行记录等等。
 */
abstract class DIContext extends DIBase {
    
    private $contexts = array();
    
    public function setContext($index, $item){
        $this->contexts[$index] = $item;
    }
    
    public function getContext($index, $useStrict = false){
        if(!isset($this->contexts[$index]) && $useStrict)
            throw new DIException("该索引 [ $index ]在系统上下文中不存在");
        return $this->contexts[$index];
    }
    
    public function unsetContext($index, $useStrict = false){
        if(!isset($this->contexts[$index]) && $useStrict)
            throw new DIException("该索引 [ $index ]在系统上下文中不存在");
        array_unset($this->contexts, $index);
    }
    
}

//全局托管注册表，遇到不存在的类时，使用spl_autoload_register()临时覆盖__autoload(),新的autoload会自动调用注入逻辑，完成调用后，将恢复原__autoload()。
/**
 * 与注入相关的上下文
 */
class DIContext_Inject extends DIContext {
    
    public function addInject(DIBase $obj, $memberFuncName, $callback, array $callback_args=array(), $isAfter=false){
        $index = get_class($obj) . '->' . $memberFuncName;
        $item = inject($obj, $memberFuncName, $callback, $callback_args, $isAfter);
        $this->setContext($index, $item);
    }
    
    public function removeInject(DIBase $obj, $memberFuncName){
        $index = get_class($obj) . '->' . $memberFuncName;
        $this->unsetContext($index);
    }
    
    //不需要getInject()
    
}





/**
 * 系统运行时，一般是针对库支持开发的。
 * 如import操作会触发索引添加
 */
class DIRuntime extends DIBase {
    
    private static $pool = array();//存储池
    
    //获取某类固定前缀的键的最大序号（测试中，暂无用处）
    protected static function getMaxNum($prefix){
        $pool = parent::getRuntime();
        $keys = array_keys($pool);
        $max = false;
        foreach ($keys as $k) {
            $num = substr_replace($k, '', 0, strrpos($k, $prefix)+1);
            if ( (false === $max || $max < $num) && is_numeric($num) ) $max = $num;
        }
        return $max ? $max : 0;
    }
    
    //添加
    static function addItem($index, $item, $useStrict = false){
        if(isset(self::$pool[$index]) && $useStrict)
            throw new DIException("该索引 [ $index ]在系统运行时中已存在");
        self::$pool[$index] = $item;
    }
    
    //获取
    static function getItem($index){
        if(!isset(self::$pool[$index])) return null;
        return self::$pool[$index];
    }
    
    //更新
    static function updItem($index, $item, $useStrict = false){
        if(!isset(self::$pool[$index]) && $useStrict)
            throw new DIException("该索引 [ $index ]在系统运行时中不存在");
        self::$pool[$index] = $item;
    }
    
    //删除
    static function delItem($index, $useStrict = false){
        if(!isset(self::$pool[$index]) && false)
            throw new DIException("该索引 [ $index ]在系统运行时中不存在");
        array_unset(self::$pool, $index);
    }
    
    //是否存在索引
    static function hasIndex($index) {
        return isset(self::$pool[$index]);
    }
    
    //根层次是否存在值为$item的项
    static function hasItem($item) {
        return in_array($item, self::$pool);
    }
    
    //按根层次来合并Runtime数组
    static function mergeNewItems($items){
        self::$pool += $items;
    }
    
    static function getRuntime(){
        return self::$pool;
    }
    
}

/**
 * 记录import()导入的文件路径
 */
class DIRuntime_Imported extends DIRuntime {
    
    const INDEX = 'imported';
    
    //初始化import池
    private static function initPool(){
        $keyexist = parent::hasIndex(self::INDEX);
        $ispool = $keyexist ? is_array(parent::getItem(self::INDEX)) : false;
        if (!$ispool) parent::addItem(self::INDEX, array());
    }
    
    //是否已导入
    private static function isPushed($path){
        $imported = parent::getItem(self::INDEX);
        $flag = false;
        foreach ($imported as $i) {
            if (!is_string($i)) continue;//要求pool[imported]的格式为array(0=>string,1=>string,...)
            if (0 === strpos($i, $path) || 0 === strpos($path, $i)) {
                $flag = true;
                break;
            }
        }
        return $flag;
    }
    
    /**
     * 加入import记录 
     * $path必须是相对于启动脚本[如index.php]所在根目录的完整相对路径
     * 返回true可以导入 false不能再导入
     */
    static function push($path) {
        self::initPool();
        if (self::isPushed($path)) {
            return false;
        }
        $imported = parent::getItem(self::INDEX);
        array_push($imported, $path);
        parent::updItem(self::INDEX, $imported);
        return true;
    }
    
    //获取import池
    static function imported(){
        return parent::getItem(self::INDEX);
    }
    
}

