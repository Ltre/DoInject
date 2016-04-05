<?php
/**
 * 比较完善的XSS过滤器(支持单个或批量)
 * @param array $items 待处理数组，引用变量。若传入字符串，则当做单个来处理。
 * @param bool $fullfilter 默认处理数组全部元素
 * @param array $needKeys 需要处理的元素所在的键，该参数仅在$fullfilter=false时有效
 */
function xssFilter(&$items = array(), $fullfilter = true, $needKeys = array()){
    import('xsshtml.class');
    $filter = function(&$v){
        $xss = new XssHtml($v);
        $v = $xss->getHtml();
        $v = preg_replace(array('/\\n\<p\>/is', '/\<\/p\>\\n/is'), '', $v);//作者已修复对纯文本自动换行和加p标签包裹的BUG，此行代码在观测稳定后再删除
    };
    if (is_string($items)) {
        $filter($items);
        return;
    }
    foreach ($items as $k => &$v) {
        if ($fullfilter || in_array($k, $needKeys) && is_string($v)) {
            $filter($v);
        }
    }
}