<?php
class Global1Filter implements DIFilter {
    
    public function doFilter() {
        $msg = "------------全局过滤器Global1Filter::doFilter()执行------------\r\n";
        file_put_contents(DI_LOG_PATH.'filter.log', $msg, FILE_APPEND);
    }

}