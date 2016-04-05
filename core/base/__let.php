<?php
/**
 * 小程序段相关
 * 不需要放置在函数体或类中的脚本，可以放置在具有一定意义命名的php文件中
 */

class DILet extends DIBase {
    
}

/**
 * <pre>
 * let(path,code)持久化保存临时代码，可用于动态生成插件
 * let(path)执行小程序
 * let(path,null)永久删除小程序
 * </pre>
 * @param string $path 相对let目录的路径，不需.php后缀。输入纯数字将自动转换为字符串
 * @param mixed $other 其它参数
 * @return mixed
 * <pre>
 *      -1  -> let(path,code)方式调用时，文件路径不正确或写文件发生异常
 *      0   -> let(path,code)方式调用时，执行成功
 * </pre>
 */
function let($path, $other=null){
    if(! DI_LET_ENABLED){
        throw new DIException('需要先启用LET小程序支持，详见DI_LET_ENABLED @ __let.php');
    }
    
    $args = func_get_args();
    $num = func_num_args();
    if(1===$num){
        import(strval($args[0]), DI_LET_PATH);
    }
    else if(2===$num){
        $path = DI_LET_PATH . trim(strval($args[0]), '/') . '.php';
        if(is_string($args[1])){
            $code = "<?php {$args[1]}";
            if (! file_exists($dir = dirname($path)) && ! mkdir($dir, '0777', true)) {
                return -2;//文件夹无法创建
            }
            if(false===file_put_contents($path, $code)){
                return -1;
            }else{
                return 0;
            }
        }
        else if(null===$args[1]){
            if(is_file($path)){
                unlink($path);
                $dir = dirname($path);
                if( 0 === count(glob($dir)) ){
                    rmdir($dir);
                }
            }
        }
        else{
            //...参数个数为2，第一个参数确定为路径，第二个参数非字符串且非空
        }
    }
    else{
        //...参数个数最少为3个
    }
}

/**
 * 检测小程序是否存在
 * @param string $path 相对let目录的路径，不需.php后缀
 * @return bool
 */
function let_exists($path){
    if(! DI_LET_ENABLED){
        throw new DIException('需要先启用LET小程序支持，详见DI_LET_ENABLED @ __let.php');
    }
    
    $path = DI_LET_PATH . trim(strval($path), '/') . '.php';
    return is_file($path);
}
