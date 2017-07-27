<?php
require __DIR__.'/src/Swoole/ToolKit/AutoReload.php';

// 获取pid

$content = file_get_contents('/home/wwwroot/default/pwa/server.pid');
$pid = intval($content);

$kit = new Swoole\ToolKit\AutoReload($pid);
$kit->afterNSeconds = 5;
$kit->watch('/home/wwwroot/default');
$kit->run();
