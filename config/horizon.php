<?php

use Illuminate\Support\Str;
use Linfo\Linfo;

// مقدار پیش‌فرض برای جلوگیری از خطا
$maxProcesses = 10;

try {
    if (php_sapi_name() !== 'cli') {
        throw new Exception("Linfo is not required in non-CLI mode.");
    }
    $lInfo = new Linfo();
    $parser = $lInfo->getParser();
    $maxProcesses = (int)ceil($parser->getRam()['total'] / 1024 / 1024 / 1024 * 6);
} catch (\Exception $e) {
    // در صورت بروز خطا، مقدار پیش‌فرض 10 باقی می‌ماند
}

return [

    'domain' => null,
    'path' => 'monitor',
    'use' => 'default',

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_horizon:'
    ),

    'middleware' => ['admin'],

    'waits' => [
        'redis:default' => 60,
    ],

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    'fast_termination' => false,

    'memory_limit' => 32,

    'environments' => [
        'local' => [
            'V2board' => [
                'connection' => 'redis',
                'queue' => [
                    'order_handle',
                    'traffic_fetch',
                    'stat',
                    'send_email',
                    'send_email_mass',
                    'send_telegram',
                ],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => $maxProcesses,
                'tries' => 1,
                'balanceCooldown' => 3,
            ],
        ],
    ],
];
