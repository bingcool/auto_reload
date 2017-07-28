<?php
$http = new swoole_http_server("0.0.0.0", 9501);
$http->set([
    'daemonize'=>1,
    'pid_file' => __DIR__.'/server.pid',
]);

$http->on('request', function ($request, $response) {
    $response->header("Content-Type", "text/html; charset=utf-8");
    $response->end("<h1>Hello Swoole BINGCOOL. #".rand(1000, 9999)."</h1>");
});

$http->start();







