# 自动重启脚本

* 使用`inotify`监听PHP源码目录
* 程序文件更新时自动`reload`服务器程序

运行脚本
----
依赖`inotify`和`swoole`扩展
```shell
pecl install swoole
pecl install inotify
php daemon.php
```

运行程序
```php
require __DIR__.'/src/Swoole/ToolKit/SuAutoReload.php';
$sukit = new Swoole\ToolKit\SuAutoReload();
$sukit->afterNSeconds = 5;
$sukit->watch('/home/wwwroot/default');
$sukit->run();
//两者不能共存，只能取其一，建议第一个，因为是经过优化的，与swoole的启动不存在关系，独立功能
//无需按顺序先启动swoole

// require __DIR__.'/src/Swoole/ToolKit/AutoReload.php';
// $kit = new Swoole\ToolKit\AutoReload();
// $kit->afterNSeconds = 5;
// $kit->watch('/home/wwwroot/default');
// $kit->run();

```
