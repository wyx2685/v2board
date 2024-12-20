<?php

require_once __DIR__ . '/vendor/autoload.php';

use Adapterman\Adapterman;
use Workerman\Worker;
use Illuminate\Support\Facades\Cache;

putenv('APP_RUNNING_IN_CONSOLE=false');
define('MAX_REQUEST', 6600);
define('isWEBMAN', true);

Adapterman::init();

$ncpu = substr_count((string)@file_get_contents('/proc/cpuinfo'), "\nprocessor")+1;

$http_worker                = new Worker('http://127.0.0.1:6600');
$http_worker->count         = $ncpu * 2;
$http_worker->name          = 'AdapterMan';

$http_worker->onWorkerStart = static function () {
    //init();
    require __DIR__.'/start.php';
};

$http_worker->onMessage = static function ($connection, $request) {
    static $request_count = 0;
    static $pid;
    if ($request_count == 1) {
        $pid = posix_getppid();
        Cache::forget("WEBMANPID");
        Cache::forever("WEBMANPID", $pid);
    }
    $connection->send(run());
    if (++$request_count > MAX_REQUEST) {
        Worker::stopAll();
    }
};

Worker::runAll();
