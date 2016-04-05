<?php
class TesttestDo extends TemplateDo {
    
    /*
     * 用于在外部测试TestDo的注入器特性的脚本
     * 启动测试脚本：$t = new TesttestDo;$t->test();
     * URL启动：<preurl>?testtest/test
     */
    protected function test(){
        /**
         * 测试DIDo注入器
         */
        $runtime = array(
            'version'	=> '1.1.0',
            'time'		=> time()
        );
        DIRuntime::mergeNewItems($runtime);
        $a = new TestDo();
        $a->test();
        $a->b = 1;
        echo $a->b;
    }
    
    //测试TemplateDo::stpl()，这里仅限Smarty输出的模板
    function testStpl(){
        $this->abc = 'this is $abc from TemplateDo::stpl(), supported by Smarty';
        $this->stpl();
    }
    
    //测试TemplateDo::tpl(), 这里仅限PHP常规输出的模板
    function testTpl(){
        $this->abc = 'this is $abc from TemplateDo::tpl(), suported by PHP general output';
        $this->tpl();
    }
    
}