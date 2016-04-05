<?php

/**
 * 名为“Xxx”的过滤器，其作用域详见filtermap.php的配置
 */
class XxxFilter implements DIFilter {
    
    function doFilter(){
        echo '===============Xxx过滤器执行===============<br>';
    }
    
}