<?php

//重新封装smarty，可以采用非I/O操作，完成缓存页面存取。能完成与DIDo的桥接
class DITpl extends DIBase {
	
    private $_smarty;
    
    private $_auto_show;
    
    private $_do;
    
    public function __construct(DIDo $_do){
        $this->_do = $_do;
    }
    
    public function preoutput(){
        header('Content-type: text/html; charset=utf-8');
        ob_start();
    }
    
    public function showWithSmarty($tpl_name, $return=false){
        import('smarty/Smarty.class');//引入模板引擎, 详见 http://www.php100.com/manual/smarty3/installation.html
        $this->smarty();
        $this->_smarty->assign($this->_do->getVitualVars());
        $this->_smarty->assign($this->_do->getLambdas());//20141231
        $this->_smarty->assign(get_object_vars($this->_do));

        if( $return ){
            return $this->_smarty->fetch($tpl_name);
        }
        else{
            $this->preoutput();
            $this->_smarty->display($tpl_name);
        }
    }
    
    public function showWithGeneral($tpl_name, $return = false){
        $this->preoutput();
        
        //将XxxDo所有成员作为共享变量供模板使用
        extract($this->_do->getVitualVars());//虚拟变量
        extract($this->_do->getLambdas());//虚拟lambda
        extract(get_object_vars($this->_do));
        
        $ret = require(DI_TPL_PATH . $tpl_name);
        if ($return) return $ret;
    }
    
    public function smarty(){
        if(! $this->_smarty){
            $this->_smarty = new Smarty;
            $this->_smarty->template_dir    = DI_TPL_PATH;
            $this->_smarty->compile_dir     = DI_DATA_PATH . 'cache';
            $this->_smarty->cache_dir       = DI_DATA_PATH . 'cache';
            $this->_smarty->left_delimiter  = DI_SMARTY_LEFT_DELIMITER;
            $this->_smarty->right_delimiter = DI_SMARTY_RIGHT_DELIMITER;
            $this->_smarty->auto_literal    = true;
            import('bootstrap');
            if (! function_exists('__autoload')) {
                @eval('function __autoload($class_name){}');
            }
            $this->_smarty->registerPlugin('function', 'spte', 'bt_shell_page_sm');
            $this->_smarty->registerPlugin('function', 'spte3', 'bt3_shell_page_sm');
            //$this->_smarty->default_modifiers = array('escape:"html"');
        }
    }
    
}

