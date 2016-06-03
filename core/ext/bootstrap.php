<?php
/**
 * 分页快速生成(适用于GET型的URL)
 * @param string $shell “?”之后紧跟的URL指令，如“test-test”、“a.b”
 * @param array $params GET参数，键名必须符合变量命名规范
 * @return string
 * @example
 *      <?php import('bootstrap'); print bt_page('danmu/search', compact('pager'))?>
 */
function bt_page($shell, $params) {
    if( empty($params['pager']) ) return '';

    $pager = $params['pager'];
    $p = empty($params['_p']) ? 'p' : $params['_p'];//_p何用？
    unset($params['pager'], $params['_p']);

    $page_str='<div class="pagination"><ul>';
    if($pager['current_page']>1) {
        $params[$p] = $pager['prev_page'];
        $page_str .= '<li><a href="' . url($shell, $params) . '" rel="prev" title="上一页">上一页</a></li>';
        unset($params[$p]);
    }

    foreach($pager['all_pages'] as $value) {
        if($value>1) $params[$p]=$value;
        if( $value == $pager['current_page'] ) {
            $current = 'title="已经是当前页" class="current" ';
            $page_str .= '<li class="active"><a href="' . url($shell, $params) . '" ' . $current . '>' . $value . '</a></li>';
        } else {
            $current = 'title="第'. $value .'页" ';
            $page_str .= '<li><a href="' . url($shell, $params) . '" ' . $current . '>' . $value . '</a></li>';
        }

    }
    if($pager['current_page']<$pager['total_page']){
        $params[$p] = $pager['next_page'];
        $page_str .= '<li><a href="' . url($shell, $params) . '" rel="next" title="下一页">下一页</a></li>';
    }

    return $page_str."</ul></div>";
}

/**
 * Bootstrap3分页快速生成(适用于GET型的URL)
 * @param string $shell “?”之后紧跟的URL指令，如“test-test”、“a.b”
 * @param array $params GET参数，键名必须符合变量命名规范
 * @return string
 * @example
 *      <?php import('bootstrap'); print bt_page('danmu/search', compact('pager'))?>
 */
function bt3_page($shell, $params) {
    if( empty($params['pager']) ) return '';

    $pager = $params['pager'];
    $p = empty($params['_p']) ? 'p' : $params['_p'];//_p何用？
    unset($params['pager'], $params['_p']);

    $page_str='<div><ul class="pagination">';
    if($pager['current_page']>1) {
        $params[$p] = $pager['prev_page'];
        $page_str .= '<li><a href="' . url($shell, $params) . '" rel="prev" title="上一页">上一页</a></li>';
        unset($params[$p]);
    }

    foreach($pager['all_pages'] as $value) {
        if($value>1) $params[$p]=$value;
        if( $value == $pager['current_page'] ) {
            $current = 'title="已经是当前页" class="current" ';
            $page_str .= '<li class="active"><a href="' . url($shell, $params) . '" ' . $current . '>' . $value . '</a></li>';
        } else {
            $current = 'title="第'. $value .'页" ';
            $page_str .= '<li><a href="' . url($shell, $params) . '" ' . $current . '>' . $value . '</a></li>';
        }

    }
    if($pager['current_page']<$pager['total_page']){
        $params[$p] = $pager['next_page'];
        $page_str .= '<li><a href="' . url($shell, $params) . '" rel="next" title="下一页">下一页</a></li>';
    }

    return $page_str."</ul></div>";
}

/**
 * 分页快速生成(适用于shell型的URL，并兼容GET型参数)
 * @param string $shell_tpml “?”之后紧跟的URL指令模板，如'danmu/search//{p}'、'danmu/search/5/{p}/abc'、'test-test1.{p}.hehe'
 * @param array $pager Model层计算出的pager，详见__model.php
 * @param string $tpml_search URL指令模板参数中的可替换字符串，如'danmu/search//{p}'中的“{p}”
 * @param array $elseParams 要兼容的GET型参数,最终会转化为a=1&b=2的形式
 * @return string
 * @example
 *      <?php import('bootstrap'); print bt_shell_page('danmu/search//{p}', $pager)?>
 */
function bt_shell_page($shell_tpml, $pager, $tpml_search = '{p}', array $elseParams = array()){
    if (empty($shell_tpml)) return '';
    if (empty($pager)) return '';

    $page_str='<div class="pagination"><ul>';
    if($pager['current_page']>1) {
        $shell = str_replace($tpml_search, $pager['prev_page'], $shell_tpml);
        $page_str .= '<li><a href="' . url($shell, $elseParams) . '" rel="prev" title="上一页">上一页</a></li>';
    }
    
    foreach($pager['all_pages'] as $value) {
        if($value >= 1) $shell = str_replace($tpml_search, $value, $shell_tpml);
        if( $value == $pager['current_page'] ) {
            $current = 'title="已经是当前页" class="current" ';
            $page_str .= '<li class="active"><a href="' . url($shell, $elseParams) . '" ' . $current . '>' . $value . '</a></li>';
        } else {
            $current = 'title="第'. $value .'页" ';
            $page_str .= '<li><a href="' . url($shell, $elseParams) . '" ' . $current . '>' . $value . '</a></li>';
        }
    
    }
    
    if ($pager['current_page'] < $pager['total_page']) {
        $shell = str_replace($tpml_search, $pager['next_page'], $shell_tpml);
        $page_str .= '<li><a href="' . url($shell, $elseParams) . '" rel="next" title="下一页">下一页</a></li>';
    }
    
    return $page_str."</ul></div>";
}

//{~spte s="posts/p{p}" p=$page e=$elseParams~}
function bt_shell_page_sm($params, $smarty){
    isset($params['s']) || $params['s'] = '';
    if (! isset($params['s'])) throw new DIException('没有指定pager');
    isset($params['t']) || $params['t'] = '{p}';
    isset($params['e']) || $params['e'] = array();
    return bt_shell_page($params['s'], $params['p'], $params['t'], $params['e']);
}

/**
 * Bootstrap3分页快速生成(适用于shell型的URL，并兼容GET型参数)
 * @param string $shell_tpml “?”之后紧跟的URL指令模板，如'danmu/search//{p}'、'danmu/search/5/{p}/abc'、'test-test1.{p}.hehe'
 * @param array $pager Model层计算出的pager，详见__model.php
 * @param string $tpml_search URL指令模板参数中的可替换字符串，一般表示页码，如'danmu/search//{p}'中的“{p}”
 * @param array $elseParams 要兼容的GET型参数,最终会转化为a=1&b=2的形式
 * @return string
 * @example
 *      <?php import('bootstrap'); print bt3_shell_page('danmu/search//{p}', $pager)?>
 */
function bt3_shell_page($shell_tpml, $pager, $tpml_search = '{p}', array $elseParams = array()){
    if (empty($shell_tpml)) return '';
    if (empty($pager)) return '';

    $page_str='<div><ul class="pagination">';
    if($pager['current_page']>1) {
        $shell = str_replace($tpml_search, $pager['prev_page'], $shell_tpml);
        $page_str .= '<li><a href="' . url($shell, $elseParams) . '" rel="prev" title="上一页">上一页</a></li>';
    }

    foreach($pager['all_pages'] as $value) {
        if($value >= 1) $shell = str_replace($tpml_search, $value, $shell_tpml);
        if( $value == $pager['current_page'] ) {
            $current = 'title="已经是当前页" class="current" ';
            $page_str .= '<li class="active"><a href="' . url($shell, $elseParams) . '" ' . $current . '>' . $value . '</a></li>';
        } else {
            $current = 'title="第'. $value .'页" ';
            $page_str .= '<li><a href="' . url($shell, $elseParams) . '" ' . $current . '>' . $value . '</a></li>';
        }

    }

    if ($pager['current_page'] < $pager['total_page']) {
        $shell = str_replace($tpml_search, $pager['next_page'], $shell_tpml);
        $page_str .= '<li><a href="' . url($shell, $elseParams) . '" rel="next" title="下一页">下一页</a></li>';
    }

    return $page_str."</ul></div>";
}

//{~spte3 s="posts/p{p}" p=$page e=$elseParams~}
function bt3_shell_page_sm($params, $smarty){
    isset($params['s']) || $params['s'] = '';
    if (! isset($params['s'])) throw new DIException('没有指定pager');
    isset($params['t']) || $params['t'] = '{p}';
    isset($params['e']) || $params['e'] = array();
    return bt3_shell_page($params['s'], $params['p'], $params['t'], $params['e']);
}