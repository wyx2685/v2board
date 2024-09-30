<?php

use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | این تنظیم مشخص می‌کند که کدام کانال به صورت پیش‌فرض برای لاگ‌گیری
    | استفاده می‌شود. در اینجا از "stack" استفاده می‌کنیم که شامل چندین
    | کانال است.
    |
    */

    'default' => 'stack',

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | در این بخش کانال‌های لاگ‌گیری تنظیم می‌شوند. در اینجا ما دو کانال
    | اصلی داریم، یکی برای ذخیره در فایل و دیگری برای ذخیره در دیتابیس.
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily', 'mysql'], // ترکیب لاگ‌گیری در فایل و دیتابیس
            'ignore_exceptions' => false,
        ],

        'mysql' => [
            'driver' => 'custom',
            'via' => App\Logging\MysqlLogger::class,
            'level' => 'debug', // بالاترین سطح لاگ‌گیری (تمام جزئیات) برای دیتابیس
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => 'debug', // بالاترین سطح لاگ‌گیری (تمام جزئیات) برای فایل
            'days' => 14, // نگهداری لاگ‌ها تا ۱۴ روز
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => 'debug',
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => 'critical',
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => 'debug',
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => 'debug',
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => 'debug',
        ],
    ],

];
