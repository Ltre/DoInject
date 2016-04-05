<?php 
/**
 * 可用于页面父模板嵌套的全局模板类
 * @author ltre
 * @since 2014-12-31
 */
abstract class TemplateDo extends DIDo {
    
    /**
     * 页面公共模板(这里仅限PHP常规输出的模板)
     * @see DIDo::tpl()
     * @example
     *      0、例如DanmuDo::okasii()，需要用到父页面的header和footer
     *      1、新建danmu-tpl.php，将公共的header、footer等转移到该文件中，
     *          内容有变化的地方，使用语句 
     *              <?php include DI_TPL_PATH . $concrete . '.php';//不要使用import，否则无法使用extract()生成的变量 ?>
     *          即可, 其中, $concretes是目录DI_TPL_PATH中的文件名(不需要.php后缀)
     *      2、DanmuDo继承TemplateDo
     *      3、编写function okasii()，最后结尾执行 $this->tpl()即可
     */
    protected function tpl($tpl_name=null, $return=false){
        if (! empty($tpl_name)) {
            $ret = parent::tpl($tpl_name, $return);
            if ($return) { return $ret; } else { exit; }
        }
        
        $d = debug_backtrace();
        $this->_tplCommon($d);
        
        $ret = parent::tpl();
        if ($return) { return $ret; } else { exit; }
    }
    
    /**
     * 页面公共模板(这里仅限Smarty输出的模板)
     * 作用与$this->tpl()类似, 但直接利用Smarty输出模板
     * @see DIDo::stpl()
     * @example
     *      0、例如TesttestDo::testStpl()，需要用到父页面的header和footer
     *      1、新建danmu-stpl.html，将公共的header、footer等转移到该文件中，
     *          内容有变化的地方，使用语句 
     *              {include file=$concrete} 
     *          即可, 其中, $concrete是目录DI_TPL_PATH中的文件名(不需要.html后缀)
     *      2、DanmuDo继承TemplateDo
     *      3、在业务代码最后结尾执行 $this->tpl()即可
     */
    protected function stpl($tpl_name = null, $return = false){
        if (! empty($tpl_name)) {
            $ret = parent::stpl($tpl_name, $return);
            if ($return) { return $ret; } else { exit; }
        }
        
        $d = debug_backtrace();
        $this->_tplCommon($d);
        $this->concrete = DI_TPL_PATH . $this->concrete . '.html';
        
        $ret = parent::stpl();
        if ($return) { return $ret; } else { exit; }
    }
    
    
    //$this->tpl()和$this->stpl()公用部分
    private function _tplCommon($d){
        $f = $d[1]['function'];
        if ('call_user_func_array' == $f || !isset($d[1]['class'])) {
            die;//禁止URL直接访问(?简短类名/tpl，如?danmu/tpl)
        }
        if ($d[1]['class'] != get_class($d[0]['object'])) {
            die;//必须确保当前子类对象的类名和上级调用点所处类名完全一样
        }
        
        //要注意：上级调用这有可能还是[s]tpl()，所以要逐层判断。在调用栈中，找到第一个非[s]tpl()的对象方法名，这个名称会对应模板名称
        $i = 1;
        $len = count($d);
        while ($i++ < $len && ('tpl' == $f || 'stpl' == $f)) {
            $e = $d[$i];
            if (!isset($e['function'])) continue;
            $f = $e['function'];
        }
        
        $this->concrete = strtolower($this->getShortClassName(DI_KEYWORD_DO) . '-' . $f);
    }
    
}