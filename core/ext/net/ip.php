<?php
/**
 * 有关IP的开放API和相关的库
 */
class Api_ip {
    
    /**
     * 新浪提供的IP归属地查询
     * http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js
     * 返回：var remote_ip_info = {“ret”:1,”start”:”当前IP段起始″,”end”:”当前IP段结束″,”country”:”\u4e2d\u56fd”,”province”:”省份”,”city”:”城市″,”district”:””,”isp”:”运营商″,”type”:””,”desc”:””}
     * http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js&ip=指定IP
     * 返回：var remote_ip_info = {“ret”:1,”start”:”指定IP段起始″,”end”:”指定IP段结束″,”country”:”\u4e2d\u56fd”,”province”:”省份”,”city”:”城市″,”district”:””,”isp”:”运营商″,”type”:””,”desc”:””}
     * http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip=指定IP
     * 返回：{“ret”:1,”start”:”指定IP段起始″,”end”:”指定IP段结束″,”country”:”\u4e2d\u56fd”,”province”:”省份”,”city”:”城市″,”district”:””,”isp”:”运营商″,”type”:””,”desc”:””}
     * @param string $ip
     * @return temp_api_ip_ip_loca_sina
     */
    static function ip_loca_sina($ip = '', $timeout = 5){
        empty($ip) && $ip = trim(array_shift(explode(',', getip())));
        $isip = !!preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $ip);
        if (!$isip) {
            return false;
        }
        
        $remote = 'http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip=' . $ip;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $remote);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);//不输出
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        //curl_setopt($curl, CURLOPT_ENCODING, "gzip");
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPGET, 0);
        $ret = json_decode(curl_exec($curl));
        curl_close($curl);
        
        if (!is_object($ret)) {
            return false;
        }
        return $ret;
    }
    
    /**
     * 获取客户端IP(兼容多种平台)
     * 返回值受服务端IP段和所处网络环境影响。
     * 如本地测试时会返回127.0.0.1; 如果服务器处于国际公网，则返回公网地址。
     * @return string
     */
    static function get_client_ip(){
        return @trim(array_shift(explode(',', getip())));
    }
    
    /**
     * 生成随机IP(实验用)
     * @return string
     */
    static function get_rand_ip(){
        $ip = array();
        for ($i = 0; $i < 4; $i++) {
            $ip[] = rand(0,254);
        }
        return join('.', $ip);
    }
    
}

//供Api_ip::ip_loca_sina()返回值用
class temp_api_ip_ip_loca_sina {
    var$ret;var$start;var$end;var$country;var$province;
    var$city;var$district;var$isp;var$type;var$desc;
}