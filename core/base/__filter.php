<?php

interface DIFilter {

    function doFilter();
    
}

class DIFilterUtil extends DIBase {
    
    /**
     * 执行全局过滤器
     * 1、【此条没什么意义，还影响实际使用体验，暂时废弃】首选如果当前请求的shell等于首选启动指令，则忽略过滤器
     * 2、系统会根据配置的全局过滤器的数组下标递增执行，详见DIFilterMap::getGlobalFilters() @ filtermap.php
     * 3、如果shell等于全局过滤器中配置的例外项，也忽略对应的过滤器
     */
    static function execGlobalFilter(){
        /* $shell = DIRuntime::getItem('shell');
        $isDefault = !strcasecmp($shell, DIUrlShell::$_default_shell);
        if ($isDefault) {
            return;//首选启动指令将忽略全局过滤器
        } */
        
        $list = DIFilterMap::getGlobalFilters();
        $execlist = array();
        foreach ($list as $k => &$l) {
            if (!is_array($l)) continue;
            $exclude = false;
            foreach ($l as $ll) {
                if (DI_DO_MODULE == $ll) { $exclude = true; break; }
                if (DI_DO_MODULE .'/'. DI_DO_FUNC == $ll) { $exclude = true; break; }
            }
            if ($exclude) continue;
            $execlist[] = $k;
        }
        self::invokeFilter($execlist);
    }
    
    /**
     * 执行有特定作用点的过滤器
     * @author biao
     * @since 2014-10-28
     * @param string $spot 过滤器作用点
     * <pre>
     *      格式：[Do名称/方法名] ，不含“[]”，大小写敏感
     *      例如self::exec('test/lets');
     * </pre>
     */
    static function execSpecialFilter($spot){
        $validate = is_string($spot) && false !== strpos($spot, '/') && 2 == count($ex = explode('/', $spot));
        if (!$validate) { throw new DIException('DIFilterUtil::exec() FILE ' . __FILE__ . ' LINE '. __LINE__ . ' $spot参数错误，格式必须为[Do名/方法]，当前序列化值：' . serialize($spot)); }
        
        $ns = DIFilterMap::getNeedsMap();
        $ws = DIFilterMap::getWithoutMap();

        $lneed = self::loop($ns, $spot, 'getNeedsMap');//需要的过滤器
        $lwithout = self::loop($ws, $spot, 'getWithoutMap');//不需要的过滤器
        
        //根据DIFilterMap::$need的默认值，来决定一类过滤器（同时配置了“需执行”和“不执行”的）的去留。最终可执行的过滤器保存在$needs中
        $needlist = $lneed['list'];
        $withoutlist = $lwithout['list'];
        if (! DIFilterMap::$need) {
            foreach ($withoutlist as &$w) {
                while (is_int($index = array_search($w, $needlist))) {
                    array_unset($needlist, $index);
                }
            }
        }
        $sortlist = self::setSort($needlist, $spot);
        self::invokeFilter($sortlist);
    }
    
    /*
     * 通过循环来验证$spot是否在$loop中配置，并获取$spot对应的过滤器名称集合
     * 返回：flag 是否存在配置 list 过滤器集合
     * flag下标其实没用，只是用来证明有没有配置过need/without规则，但不能证明具体对应了哪些规则
     */
    static private function loop($loop, $spot, $loopname){
        if (!is_array($loop)) { throw new DIException("过滤规则[ {$loopname} ]配置格式错误"); }
        
        $list = array();
        $flag = false;
        foreach ($loop as $i => &$l) {
            if (!is_array($l)) { throw new DIException("过滤规则[ {$loopname} - {$i} ]配置格式错误"); }
            if (in_array($spot, $l)) { $flag = true; $list[] = $i; continue; }

            foreach ($l as $k => &$ll) {
                if (!is_string($ll)) { throw new DIException("过滤器作用点[ {$loopname} - {$i} - {$k} ]配置格式错误"); }
                $ex = explode('/', $spot);
                if ($ll == $ex[0]) { $flag = true; $list[] = $i; continue; }
            }
        }
        
        return array('flag' => $flag, 'list' => $list);
    }
    
    /*
     * 调整特定作用点过滤器的执行顺序
	 * 例如执行点spot的可执行过滤器有S1={A,B,C,D,E},其优先级配置为S2={C,B,F}，
	 *     则最终排序结果为{C,B,A,D,E}。
	 *     其中，S2的F被删除，S1的{A,D,E}按原顺序追加到S2尾部。
	 *     集合运算步骤如下：
	 *     (1)通过
	 *     S1 ∩ S2 = {C,B} = N1
	 *     或 S2 - (S2 - S1) = {C,B,F} - {F} = {C,B} = N1
	 *     得到S1中已对号入座的队列N1；
	 *     (2)取S1中剩余未对号入座的队列
	 *     S1 - N1 = {A,D,E} = N2
	 *     (3)将N2追加到N1尾部，得到最终结果R
	 *     N1 <+ N2 = {C,B,A,D,E} = R
     * @author biao
     * @since 2014-11-26
     */
    static private function setSort($list, $spot) {
        $spSort = DIFilterMap::getSpecialFilterSort();
        $dfSort = DIFilterMap::getDefaultFilterSort();
        $sort = isset($spSort[$spot]) ? $spSort[$spot] : $dfSort;
        //按照顺序对号入座
        foreach ($sort as $i=>$s) {
            $index = array_search($s, $list);
            if (is_int($index)) {
                array_unset($list, $index);
            } else {
                array_unset($sort, $i);
            }
        }
        //将剩余未对号入座的追加到sortlist尾部
        foreach ($list as $l) {
            array_push($sort, $l);
        }
        return $sort;
    }
    
    //真正开始执行过滤器
    static private function invokeFilter($list){
        foreach ($list as &$filter) {
            $filter .= 'Filter';
            invoke_method(new $filter, 'doFilter');
        }
    }
    
}