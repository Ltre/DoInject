<?php
$shell = 'test/test';
$shellargs = array(
	//1, 'a', 'b', false, ...
);

$request = array(
	$shell . (empty($shellargs)?'':'/') . join('/', $shellargs) => '',
);

$_REQUEST = array_merge($_REQUEST, $request);
$_SERVER['REQUEST_URI'] = '/';//一切URI的模拟写法都从“/”开始