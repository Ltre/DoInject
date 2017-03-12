<?php
class Global3Filter implements DIFilter {
    
    public function doFilter() {
        $msg = "------------全局过滤器Global3Filter::doFilter()执行------------\r\n";
        file_put_contents(DI_LOG_PATH.'filter.log', $msg, FILE_APPEND);
    }

}