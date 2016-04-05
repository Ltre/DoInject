<?php
class OrekiUtil {
	
	static $startTimeClip;
	static $endTimeClip;
	
	/**
	 * 输出当前时间<br>
	 * 格式：Y-m-d H:i:s
	 */
	static function getCurrentFormatTime($time = 0){
		$time or $time = time();
		return date('Y-m-d H:i:s', $time);
	}
	
	/**
	 * 输出当前时间（含小数部分）
	 * 格式：Y-m-d H:i:s{秒级小数部分}
	 */
	static function getCurrentFormatDecimalTime($microtime = 0){
		$microtime or $microtime = microtime();
		list($usec, $sec) = explode(" ", $microtime);//秒数的小数部分|秒级时间戳
		return date('Y-m-d H:i:s',$sec).'{'.$usec.'}';
	}
	
	/**
	 * 插入开始时刻
	 * 用于测量代码执行时间（要配合self::calcTimeGap()使用）
	 * @notice 测试阶段，请勿正式使用
	 */
	static function insertStartTimeClip(){
		self::$startTimeClip = microtime();
	}
	
	/**
	 * 插入结束时刻
	 * 用于测量代码执行时间（要配合self::calcTimeGap()使用）
	 * @notice 测试阶段，请勿正式使用
	 */
	static function insertEndTimeClip(){
		self::$endTimeClip = microtime();
	}
	
	/**
	 * 计算时差（含小数部分）
	 * @param microtime() $start
	 * @param microtime() $end
	 * @return float 秒级时差
	 */
	static function calcTimeGap($start=null, $end=null){
		$start = $start ? $start : self::$startTimeClip;
		$end = $end ? $end : self::$endTimeClip;
		list($usec1, $sec1) = explode(' ', $start);
		list($usec2, $sec2) = explode(' ', $end);
		return floatval( ($usec2-$usec1).'.'.ltrim(($sec2-$sec1), '0.') );
	}
	
	/**
	 * 输出对象
	 */
	public static function var_dump_obj( $var, $layer=0 ){
		$multispace = str_repeat('&nbsp;', 4 * $layer);
		$space = str_repeat('&nbsp;', 4);
		printf( $multispace . 'object('.get_class($var).')<br>'.$multispace.'{'. $space .'<br>' );
		foreach ( get_object_vars($var) as $name => $value ){
			if(is_object($value))
				self::var_dump_obj($value, $layer+1);
			else if (is_array($value))
				self::var_dump_array($value, $layer+1);
			else
				printf($multispace.$space.'["'. $name .'"]'. $space .'=>'. $space . $value . '<br>');
		}
		printf($multispace.'}<br>');
		-- $layer;
	}
	
	/**
	 * 遍历输出对象
	 */
	public static function dump_obj( $obj, $layer=0 ){
		$multispace = str_repeat('&nbsp;', 4 * $layer);
		$space = str_repeat('&nbsp;', 4);
		printf( $multispace . 'object('.get_class($obj).')<br>'.$multispace.'{'. $space .'<br>' );
		foreach ($obj as $i=>$v){
			if(is_object($v)){
				self::dump_obj($v, $layer+1);
			}else if(is_array($v)){
				self::var_dump_array($v, $layer+1);
			}else{
				print ($multispace . $space . '["' . $i . '"]' . $space . '=>'. $space . $v . '<br>');
			}
		}
		print ($multispace . '}<br>');
		-- $layer;
	}
	
	/**
	 * 输出数组
	 */
	public static function var_dump_array( $var, $layer=0 ){
		$multispace = str_repeat('&nbsp;', 4 * $layer);
		$space = str_repeat('&nbsp;', 4);
		printf( $multispace . 'Array<br>'.$multispace.'{'. $space .'<br>' );
		foreach ( $var as $index => $value ){
			if(is_array($value))
				self::var_dump_array($value, $layer+1);
			else if(is_object($value))
				self::var_dump_obj($value, $layer+1);
			else
				printf($multispace.$space.'["'. $index .'"]'. $space .'=>'. $space . $value . '<br>');
		}
		printf($multispace.'}<br>');
		-- $layer;
	}
	
	/**
	 * 输出常量(免警告、免if)
	 * @param string $const 常量名
	 * @return Ambigous <boolean, mixed> 常量值
	 */
	public static function getconst($const) {
		return !defined($const) ? false : constant($const);
	}
	
	/**
	 * @param Ambigous <string, object> $object 类名或对象值
	 * @param string $filter 用户匹配常量名的正则表达式
	 * @param mixed $find_value 其中某个常量的值，用于精确查找
	 * @return array 常量数组，索引值为所需常量名
	 * @example
	 * <pre>
	 * class Example
	 *	{
	 *	     const GENDER_UNKNOW = 0;
	 *	     const GENDER_FEMALE = 1;
	 *	     const GENDER_MALE = 2;
	 *	     const USER_OFFLINE = false;
	 *	     const USER_ONLINE = true;
	 *	}
	 *	$all = findConstantsFromObject('Example');
	 *	$genders = findConstantsFromObject('Example', '/^GENDER_/');
	 *	$my_gender = 1;
	 *	$gender_name = findConstantsFromObject('Example', '/^GENDER_/', $my_gender);
	 *	if (isset($gender_name[0]))
	 *	{
	 *	    $gender_name = str_replace('GENDER_', '', key($gender_name));
	 *	}
	 *	else
	 *	{
	 *	    $gender_name = 'WTF!';
	 *	}
     * </pre>
	 */
	public static function findConstantsFromObject($object, $filter = null, $find_value = null) {
		$reflect = new ReflectionClass($object);
		$constants = $reflect->getConstants();
		foreach ($constants as $name => $value) {
			if (!is_null($filter) && !preg_match($filter, $name)) {
				unset($constants[$name]);
				continue;
			}
			if (!is_null($find_value) && $value != $find_value) {
				unset($constants[$name]);
				continue;
			}
		}
		return $constants;
	}
	
	/**
	 * 检查编辑器内容，并转换部分不合法内容（从数据库取出时不需要还原）
	 * 去除危险标签、改双引号为单引号
	 * @param string $d 编辑器HTML文档
	 */
	public static function editorContentFilterToDB( $d ){
		$d = str_replace('&lt;php&gt;', '[php]', $d);
		$d = str_replace('&lt;/php&gt;', '[/php]', $d);
		$d = str_replace('&lt;script&gt;', '[script]', $d);
		$d = str_replace('&lt;/script&gt;', '[/script]', $d);
		$d = str_replace('style="', 'style=\'', $d);
		$d = str_replace('" style=', '\' style=', $d);
		$d = str_replace(';">', ';\'>', $d);
		$tags = array(
			'size','face','href','color','src','class','id','align','dir',
			'contenteditable','width','height',
		);
		foreach ($tags as $tag){
			//$d = str_replace('size="', 'size=\'', $d);
			$d = str_replace($tag.'="', $tag.'=\'', $d);
			//$d = str_replace('" size=', '\' size=', $d);
			$d = str_replace('" '.$tag.'=', '\' '.$tag.'=', $d);
		}
		$d = str_replace('">', '\'>', $d);
		$d = str_replace('" >', '\'>', $d);
		$d = str_replace('"', '＂',$d);//最后替换普通双引号
		return $d;
	}
	
	
}