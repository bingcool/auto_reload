<?php
require __DIR__.'/src/Swoole/ToolKit/SuAutoReload.php';
// require __DIR__.'/src/Swoole/ToolKit/AutoReload.php';

$sukit = new Swoole\ToolKit\SuAutoReload();
$sukit->afterNSeconds = 5;
$sukit->watch('/home/wwwroot/default');

// $kit = new Swoole\ToolKit\AutoReload();
// $kit->afterNSeconds = 5;
// $kit->watch('/home/wwwroot/default');
// $kit->run();
$sukit->run();
