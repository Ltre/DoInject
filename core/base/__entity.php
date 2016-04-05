<?php
/**
 * 不需要抽象成数据实体的基础类，与model不同。
 * 通过合理的设计模式及一定的算法可以实现复杂的业务逻辑 
 */
abstract class DIEntity extends DIBase {
	function __construct(){
	    parent::__construct();
	}
}