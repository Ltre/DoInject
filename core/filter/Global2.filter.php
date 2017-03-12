<?php
class Global2Filter implements DIFilter {
    
    public function doFilter() {
        $msg = "------------全局过滤器Global2Filter::doFilter()执行------------\r\n";
        file_put_contents(DI_LOG_PATH.'filter.log', $msg, FILE_APPEND);
    }

}