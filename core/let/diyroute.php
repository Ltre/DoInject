<?php
$yourpath = preg_replace('/^diyroute\/?/i', '', DI_REGEXP_SHELL);

$mirrors = array(
	'a' => 'mirror a',
    'b' => 'mirror b',
    'a/b' => 'mirror a/b',
    '1/2/3/5/a' => 'mirror 1/2/3/5/a',
);

echo @$mirrors[$yourpath] ?: 'else mirror';