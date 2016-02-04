<?php
require __DIR__.'/src/Swoole/ToolKit/AutoReload.php';

$kit = new Swoole\ToolKit\AutoReload(2914);
$kit->watch(__DIR__.'/tests');
$kit->run();
