<?php

namespace App\Console;

use App\Utils\CacheKey;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Cache;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // تغییر مالکیت و مجوزها برای فولدر logs
        $schedule->call(function () {
            $directory = base_path('storage/logs');

            // تغییر مالکیت و گروه به کاربر www
            exec("sudo chown -R www:www " . escapeshellarg($directory));

            // تغییر مجوزها به 775
            exec("sudo chmod -R 775 " . escapeshellarg($directory));

            echo "مجوزها و مالکیت فایل‌ها به‌روزرسانی شد.\n";
        })->before(function () {
            // هر عملیاتی که باید قبل از اجرای تمامی دستورات انجام شود
        })->everyMinute(); // می‌توانید زمان‌بندی اجرای این تابع را تغییر دهید

        Cache::put(CacheKey::get('SCHEDULE_LAST_CHECK_AT', null), time());
        // traffic
        $schedule->command('traffic:update')->everyMinute()->withoutOverlapping();
        // v2board
        $schedule->command('v2board:statistics')->dailyAt('0:10');
        // check
        $schedule->command('check:order')->everyMinute()->withoutOverlapping();
        $schedule->command('check:commission')->everyFifteenMinutes();
        $schedule->command('check:ticket')->everyMinute();
        $schedule->command('check:renewal')->dailyAt('22:30');
        // reset
        $schedule->command('reset:traffic')->daily();
        $schedule->command('reset:log')->daily();
        // send
        $schedule->command('send:remindMail')->dailyAt('11:30');
        // horizon metrics
        $schedule->command('horizon:snapshot')->everyFiveMinutes();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
