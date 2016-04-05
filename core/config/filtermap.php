<?php

/**
 * 过滤器分为全局过滤器和特定作用点过滤器
 * 只对Do-Request有效，对Let-Request无效
 * @author ltre
 */
class DIFilterMap {
	
    /**
     * true-需要, false-不需要
     * 运行机制：执行某个XXDo::yyFunction()时，会在本类的两个Map【getNeedsMap和getWithoutMap】中扫描“XX/yy”已配置的过滤器需求。
     * 从中找出需要和不需要的过滤器，如需要{A,B,C}，不需要{B,C,D}，
     * 而后根据self::$need的值决定配置冲突的过滤器最终是否需要，如本例中self::$need=false时冲突的有{B,C},则剔除之。
     * 最终结果是{A}，算法与集合的减运算相同。
     * @var boolean
     */
	static $need = true;
	
	/**
	 * @Map
	 * 需要过滤器的模块或方法，大小写敏感
	 */
	static function getNeedsMap(){
	
		return array(
			//过滤器名(免Filter后缀) => array(作用域)
			'Xxx'	=> array(
				'Test/test', //Do方法
				'Test/lets', //Do方法
			),
		    'Yyy' => array(
				'Test/test',//Do方法
			),
		);
	
	}
	
	/**
	 * @Map
	 * 不需要过滤器的模块或方法，大小写敏感
	 */
	static function getWithoutMap(){
		
		return array(
			//过滤器名(免Filter后缀) => array(作用域)
			'Xxx'	=> array(
				'Testtest',	//Do模块
				'Test/start', //Do方法
			),
		);
		
	}
	
	/**
	 * @Sort
	 * 获取过滤器执行的优先级
	 * 每个[Xx[/yy]]对应一组优先级配置
	 * 在同一组优先级配置中，下标越小（即越靠前），优先级越高
	 * 具体算法详见：DIFilterUtil::setSort() @ __filter.php
	 */
	static function getSpecialFilterSort(){
	    return array(
	    	'Test/test' => array(
                'Yyy',
                'Xxx', 
	       ),
	    );
	}
	
	/**
	 * @Sort
	 * 获取默认的过滤器执行优先级
	 * 当getSpecialFilterSort()没有配置[Xx[/yy]]对应的优先级配置时，
	 * 则采用此优先级配置
	 */
	static function getDefaultFilterSort(){
	    return array(
	    	'Xxx',
	        'Yyy',
	    );
	}
	
	/**
	 * @Map
	 * @Sort
	 * 获取全局过滤器，也获取过滤器优先级
	 * 这些过滤器对全局所有访问有效
	 * 如果需要指定例外项（如不需要全局过滤器的作用点），
	 *     则需自行在过滤器的构造方法内作限制，具体过程不作赘述。
	 * 默认例外项：DIUrlShell::$_default_shell指定值不执行全局过滤器
	 * 格式：名称 => 例外项集合
	 * 例外项格式：array('Xx/yy', 'Xx', ...)
	 */
	static function getGlobalFilters(){
	    return array(
	    	'Global1' => array('Test/start', 'Test/shell'),
	        'Global2' => array('Test/start', 'Test/shell', 'Test/test', 'Test/lets'),
	        'Global3' => array('Test/start', 'Test/shell', 'Testtest'),
	    );
	}
	
}