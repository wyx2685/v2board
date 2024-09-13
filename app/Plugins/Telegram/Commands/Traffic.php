<?php

namespace App\Plugins\Telegram\Commands;

use App\Models\User;
use App\Plugins\Telegram\Telegram;
use App\Utils\Helper;

class Traffic extends Telegram {
    public $command = '/traffic';  // دستور برای دریافت ترافیک
    public $description = 'مشاهده اطلاعات ترافیک';

    public function handle($message, $match = []) {
        $telegramService = $this->telegramService;

        // بررسی این که پیام خصوصی است
        if (!$message->is_private) return;

        // جستجوی کاربر با شناسه تلگرام
        $user = User::where('telegram_id', $message->chat_id)->first();
        if (!$user) {
            $telegramService->sendMessage($message->chat_id, 'اطلاعات کاربری شما یافت نشد، لطفاً ابتدا حساب خود را متصل کنید', 'markdown');
            return;
        }

        // تبدیل مقادیر ترافیک به فرمت خوانا
        $transferEnable = Helper::trafficConvert($user->transfer_enable);
        $up = Helper::trafficConvert($user->u);
        $down = Helper::trafficConvert($user->d);
        $remaining = Helper::trafficConvert($user->transfer_enable - ($user->u + $user->d));

        // پیام نهایی با اطلاعات ترافیک
        $text = "🚥 اطلاعات ترافیک\n———————————————\nترافیک کل: `{$transferEnable}`\nترافیک آپلود: `{$up}`\nترافیک دانلود: `{$down}`\nترافیک باقی‌مانده: `{$remaining}`";

        // ارسال پیام به کاربر
        $telegramService->sendMessage($message->chat_id, $text, 'markdown');
    }
}
