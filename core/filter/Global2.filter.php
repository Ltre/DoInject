<?php
class Global2Filter implements DIFilter {
    
    public function doFilter() {
        echo '------------全局过滤器Global2Filter::doFilter()执行------------<br>';
    }

}