<?php
class Global1Filter implements DIFilter {
    
    public function doFilter() {
        echo '------------全局过滤器Global1Filter::doFilter()执行------------<br>';
    }

}