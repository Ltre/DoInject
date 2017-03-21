<?php
try {
    invoke_method($r = new DIRoute, 'route');
} catch (DIException $e) {
    $e->deal();
}
