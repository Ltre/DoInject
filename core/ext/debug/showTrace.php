<?php
function showTrace($errMsg = '', $goDie = true) {
    $trace = debug_backtrace();
    $out = "<hr/><div>".$errMsg."<br /><table border='1'>";
    $out .= "<thead><tr><th>file</th><th>line</th><th>function</th><th>args</th></tr></thead>";
    foreach ($trace as $k => $v) {
        if ($k == 0) continue;//忽略本方法调用信息
        if (!isset($v['file'])) $v['file'] = '[PHP Kernel]';
        if (!isset($v['line'])) $v['line'] = '';
        $v['args'] = print_r($v['args'], true);
        $out .= "<tr><td>{$v["file"]}</td><td>{$v["line"]}</td><td>{$v["function"]}</td><td><pre>{$v["args"]}</pre></td></tr>";
    }
    $out .= "</table></div><hr/></p>";
    echo $out;
    $goDie AND die();
}