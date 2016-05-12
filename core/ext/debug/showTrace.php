<?php
function showTrace($errMsg = '', $goDie = true) {
    $trace = debug_backtrace();
    dump($trace);
    $out = "<hr/><div>".$errMsg."<br /><table border='1'>";
    $out .= "<thead><tr><th>file</th><th>line</th><th>function</th><th>args</th></tr></thead>";
    foreach ($trace as $v) {
        if (!isset($v['file'])) $v['file'] = '[PHP Kernel]';
        if (!isset($v['line'])) $v['line'] = '';
        $v['args'] = var_export($v['args'], true);
        $out .= "<tr><td>{$v["file"]}</td><td>{$v["line"]}</td><td>{$v["function"]}</td><td>{$v["args"]}</td></tr>";
    }
    $out .= "</table></div><hr/></p>";
    echo $out;
    $goDie AND die();
}