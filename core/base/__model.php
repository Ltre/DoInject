<?php

/* Model层相关，以数据库为主 */
/* 为支持多种库，采用模板模式 */


/**
 * 存放Model层的便捷实用方法和超级方法
 */
class DIModelUtil {
    
    /**
     * 根据简短表名获取继承于DIModel的模型实例
     * @return DIModel
     */
    static function supertable($shortname){
        $table_class = ucfirst($shortname) . ucfirst(DI_KEYWORD_MODEL);
        if (class_exists($table_class, false)) {
            return new $table_class();
        } else {
            //由于newInstance()过于先进，很多服务器不支持。建议先定义好XxxModel类再调用本方法
            return create_class($table_class, false, 'DIModel')->newInstance();
        }
    }
    
    /**
     * 创建一个临时的DIModel实例，和任何表无关
     * @return DIModel
     */
    static function instance(){
        $c = 'di_model_instance_'.rand(0, 999);
        return self::supertable($c);
    }
    
}


//模型操作模板接口
interface DIModelTemplate {
	/**
	 * @return PDO
	 */
	function connect();
	function query($sql, $params = array());
	function insert($data = array());
	function update(array $cond, $data = array());
	function alter(array $cond, $field, $optval = '+1');
	function alterByExpr(array $cond, array $exprs);
	function delete(array $cond);
	function select($cond=array(), $field='', $order=null, $limit=null);
	function find($cond=array(), $field='', $order=null);
	function count($cond = null, $bindparams = array());
	function execute($sql=null, $bindparams=array());
	function pager($page, $pageSize = 10, $scope = 10, $total);
}

/**
 * 套用模板的抽象模型
 * 会根据DIDBConfig::$driver来调用所需的数据库驱动，并套用具体的数据库操作过程
 * XxxxModel类只需要继承此类即可使用数据库操作。
 */
abstract class DIModel extends DIBase implements DIModelTemplate {
    public $table = null;//表名默认为前缀+类名去掉“Model”，也可以在继承后充写改属性来指定表名
    public $page;//分页缓存变量，操作权共享给数据库驱动，如DIMySQL，详见__constructor()
    protected $_conn = null;
    protected $_cache_rs = array();
    
    /** @var DIModel */
    protected $_driver_handler;
    
    final function __construct(){
        if (empty($this->table)) {
            $short_table = $this->getShortClassName(ucfirst(DI_KEYWORD_MODEL));
            $short_table = preg_replace('/^\_/', '', preg_replace('/([^A-Z]*)([A-Z])/', '$1_$2', $short_table));//如AbcDefGh=>Abc_Aef_Gh
            $this->table = strtolower(DIDBConfig::$table_prefix . $short_table);
        }
        
        $driver = DIDBConfig::$driver;
        if (!class_exists($driver, false)) {
            throw new DIException('找不到数据库驱动类，请检查数据库配置文件和驱动类文件');
        }
        try {
            $this->_driver_handler = new $driver($this->table, $this);
        } catch (Exception $e) {
            throw new DIException($e->getMessage());
        }
        $this->_conn or self::connect();
    }
    
	/**
	 * 继承DIModel后的类在实例化后时自动连接数据库
	 * @return PDO
	 */
	final function connect(){
	    //填写模板套用过程，并在DIModelTemplate中声明这些模板方法
	    $this->_conn = $this->_driver_handler->connect();
	    return $this->_conn;
	}
	
	final function query($sql, $params = array()){
	    if (empty($sql)) return false;
		//填写模板套用过程，并在DIModelTemplate中声明这些模板方法
	    return $this->_driver_handler->query($sql, $params);
	}
	
	final function insert($data = array()){
	    if (empty($data)) return false;
		//填写模板套用过程，并在DIModelTemplate中声明这些模板方法
	    return $this->_driver_handler->insert($data);
	}
	
    final function update(array $cond, $data = array()){
		//填写模板套用过程，并在DIModelTemplate中声明这些模板方法
        return $this->_driver_handler->update($cond, $data);
	}
	
	function alter(array $cond, $field, $optval = '+1') {
	    if (empty($cond)) return false;
	    //填写模板套用过程，并在DIModelTemplate中声明这些模板方法
	    return $this->_driver_handler->alter($cond, $field, $optval);
	}
	
	function alterByExpr(array $cond, array $exprs){
	    if (empty($cond)) return false;
	    //填写模板套用过程，并在DIModelTemplate中声明这些模板方法
	    return $this->_driver_handler->alterByExpr($cond, $exprs);
	}
	
	final function delete(array $cond){
		//填写模板套用过程，并在DIModelTemplate中声明这些模板方法
		if (empty($cond)) return false;
		return $this->_driver_handler->delete($cond);
	}
	
	final function select($cond=array(), $field='', $order=null, $limit=null){
	    //填写模板套用过程，并在DIModelTemplate中声明这些模板方法
	    return $this->_driver_handler->select($cond, $field, $order, $limit);
	}
	
	final function find($cond=array(),$field='', $order=null){
	    //填写模板套用过程，并在DIModelTemplate中声明这些模板方法
	    return $this->_driver_handler->find($cond,$field, $order);
	}
	
	final function count($cond = null, $bindparams = array()){
	    //填写模板套用过程，并在DIModelTemplate中声明这些模板方法
	    return $this->_driver_handler->count($cond, $bindparams);
	}
	
	final function execute($sql=null, $bindparams=array()){
	    //填写模板套用过程，并在DIModelTemplate中声明这些模板方法
	    return $this->_driver_handler->execute($sql, $bindparams);
	}
	
	final function pager($page, $pageSize = 10, $scope = 10, $total){
	    //填写模板套用过程，并在DIModelTemplate中声明这些模板方法
	    return $this->_driver_handler->pager($page, $pageSize, $scope, $total);
	}
}



/**
 * 具体的MySQL驱动
 */
class DIMySQL implements DIModelTemplate {
    
    protected $_conn;//与DIModel::$_conn存在引用关联，详见本类的&connect()
    protected $table;//当前表名
    protected $_M;//DIModel对象授予操作权限
    
    function __construct($table, DIModel $M){
        $this->table = $table;
        $this->_M = $M;
    }
    
    /** 
     * 连接并获取句柄
     * @return PDO
     */
    function &connect(){
        $dsn = 'mysql:host=' . DIDBConfig::$host;
        $dsn .= ';dbname=' . DIDBConfig::$db;
        $dsn .= ';port=' . DIDBConfig::$port;
        $options = array(
        	PDO::MYSQL_ATTR_INIT_COMMAND => 'set names utf8'
        );
        $this->_conn = new PDO($dsn, DIDBConfig::$user, DIDBConfig::$pwd, $options);
        return $this->_conn;
    }
    
    /**
     * 示例
     * $month = date("Y-m-d", strtotime($date));
     * $sql = "SELECT * FROM pai2_signin WHERE uid =:uid AND signin_date LIKE '{$month}%' ORDER BY signin_id ASC ";
     * $rs = $this->query($sql, array('uid' => $uid));
     */
    function query($sql, $params=array()){
		$this->sql = $sql;
		$sth = $this->_bindParams($sql, $params);
		if($sth->execute()) return $sth->fetchAll(PDO::FETCH_OBJ);
		$err = $sth->errorInfo();
		throw new DIException('Database SQL: "' . $sql. '". ErrorInfo: '. $err[2], 1);
	}
    
    function insert($data = array()){
        foreach($data as $k=>$v){
            $keys[] = "`{$k}`";
            $prevalues[] = $k;
            $values[] = $this->escape($v);
        }
        $sql = "INSERT INTO " . $this->table . " (" . join(', ', $keys) . ") VALUES ( :" . join(', :', $prevalues) . ")";
        $sth = $this->_bindParams($sql, $data);
        if ($sth->execute()) return $this->_conn->lastInsertId();
        $err = $sth->errorInfo();
        throw new DIException('Database SQL: "' . $sql. '". ErrorInfo: '. $err[2], 1);
    }
    
    function update(array $cond, $data = array()){
        if( empty($cond) ) return false;
        foreach ($data as $k=>$v){
            $setstr[] = "`{$k}`=".$this->escape($v);
        }
        return $this->execute("UPDATE ".$this->table." SET ".implode(', ', $setstr).$this->_where($cond));
    }
    
    /**
     * 在原字段值基础上修改（一个字段）
     * 如 alter(array('id'=>1, 'key'=>'k1'), 'nums', '-5')
     * 还没试过符号：*、/、%
     */
    function alter(array $cond, $field, $optval = '+1') {
        if( empty($cond) ) return false;
        return $this->execute("UPDATE ".$this->table." SET `{$field}` = `{$field}` {$optval} ".$this->_where($cond));
    }
    
    /**
     * 在原字段值基础上修改（自定义表达式，可以改多个字段）
     * @param array $cond
     * @param array $exprs
     *      如：array("nums=nums+1", "`value`=`value`-1")
     * @return boolean|number
     */
    function alterByExpr(array $cond, array $exprs){
        if (empty($cond)) return false;
        $set = 'SET ' . join(', ', $exprs);
        $sql = "UPDATE {$this->table} {$set} {$this->_where($cond)}";
        return $this->execute($sql);
    }
    
    function delete(array $cond){
        if(empty($cond)) return false;
        return $this->execute("DELETE FROM ".$this->table.$this->_where( $cond ));
    }
    
    //limit参数说明array(当前页码，页内条数上限，可见页码数)
    function select($conditions=array(), $field='', $order=null, $limit=null){
		$field = !empty($field) ? $field : '*';
		$order = !empty($order) ? ' ORDER BY '.$order : '';
		$sql = ' FROM '.$this->table.$this->_where($conditions);
		if(is_array($limit)){
			if(! $total = $this->query('SELECT COUNT(*) as dw_counter '.$sql))return null;
			$limit = $limit + array(1, 10, 10);
			$limit = $this->pager($limit[0], $limit[1], $limit[2], $total[0]->dw_counter);
			$limit = empty($limit) ? '' : ' LIMIT '.$limit['offset'].','.$limit['limit'];			
		}else{
			$limit = !empty($limit) ? ' LIMIT '.$limit : '';
		}
		return $this->query('SELECT '. $field . $sql . $order . $limit);
	}
    
	function find($conditions=array(),$field='', $order=null){
	    $field = !empty($field) ? $field : '*';
	    $res = $this->select($conditions,$field, $order,1);
	    return !empty($res) ? array_pop($res) : false;
	}
	
	/**
	 * 两种方式统计总数
	 * 示例：$t = DIModelUtil::supertable('table');
	 * 1、$t->count(array('id'=>2));
	 * 2、$t->count(array('id > :id'), array('id'=>2));
	 */
	function count($conditions = array(), $bindparams = array()){
	    $select = "SELECT COUNT(*) AS total FROM {$this->table} ";
	    $needbind = false;//是否需要预处理参数
	    $where = ' WHERE 1 ';
	    foreach ($conditions as $i=>$c) {
	        if (is_numeric($i)) {
	            $needbind = true;
	            $where .= " AND {$c} ";
	        }
	    }
	    if (!$needbind) {
    	    $count = $this->query($select . $this->_where($conditions));
	    } else {
	        $count = $this->query("{$select} {$where}", $bindparams);
	    }
	    return isset($count[0]->total) && $count[0]->total ? intval($count[0]->total) : 0;
	}
	
	function execute($sql=null, $params=array()){
	    $this->sql = $sql;
	    $sth = $this->_bindParams( $sql, $params );
	    if( $sth->execute() ) return $sth->rowCount();
	    $err = $sth->errorInfo();
	    throw new DIException('Database SQL: "' . $sql. '". ErrorInfo: '. $err[2], 1);
	}
	
    private function _bindParams($sql, $params=array()){
        $sth = $this->_conn->prepare($sql);
        if(is_array($params) && !empty($params)){
            foreach($params as $k=>&$v){
                $sth->bindParam($k, $v);
            }
        }
        return $sth;
    }
    
    private function _where($conditions = array()){
        if(is_array($conditions) && !empty($conditions)){
            $join = array();
            foreach( $conditions as $key => $condition ){
                $condition = $this->escape($condition);
                $join[] = "`{$key}` = {$condition}";
            }
            return " WHERE ".join(" AND ",$join);
        }
        return ' ';
    }
    
    private function escape($str){
        if(is_null($str))return 'null';
        if(is_bool($str))return $str ? 1 : 0;
        if(is_int($str))return (int)$str;
        if(@get_magic_quotes_gpc())$str = stripslashes($str);
        return $this->_conn->quote($str);
    }
    
    //参数：当前页码，页内条数上限，可见页码数，记录总条数
    function pager($page, $pageSize = 10, $scope = 10, $total)
    {
        $total_page = ceil( $total / $pageSize );
        $page = max(intval($page), 1);
        $this->_M->page = array(
            'total_count' => $total,
            'page_size'   => $pageSize,
            'total_page'  => $total_page,
            'first_page'  => 1,
            'prev_page'   => ( ( 1 == $page ) ? 1 : ($page - 1) ),
            'next_page'   => ( ( $page == $total_page ) ? $total_page : ($page + 1)),
            'last_page'   => $total_page,
            'current_page'=> $page,
            'all_pages'   => array(),
            'offset'      => ($page - 1) * $pageSize,
            'limit'       => $pageSize,
        );
        $scope = (int)$scope;
    
        if($total <= $pageSize){
            $this->_M->page['all_pages'] =  array();
        }elseif($total_page <= $scope ){
            $this->_M->page['all_pages'] = range(1, $total_page);
        }elseif( $page <= $scope/2) {
            $this->_M->page['all_pages'] = range(1, $scope);
        }elseif( $page <= $total_page - $scope/2 ){
            $right = $page + (int)($scope/2);
            $this->_M->page['all_pages'] = range($right-$scope+1, $right);
        }else{
            $this->_M->page['all_pages'] = range($total_page-$scope+1, $total_page);
        }
        return $this->_M->page;
    }
    
}

/*******************************                        *******************************/
/******************************* 以下是方便调用的别名函数 *******************************/
/*******************************                        *******************************/

function supertable($shortname){
    return DIModelUtil::supertable($shortname);
}

function supermodel(){
    return DIModelUtil::instance();
}
