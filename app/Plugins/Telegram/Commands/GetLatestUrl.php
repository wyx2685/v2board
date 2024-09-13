<?php

namespace App\Plugins\Telegram\Commands;

use App\Models\User;
use App\Plugins\Telegram\Telegram;

class GetLatestUrl extends Telegram {
    public $command = '/getlatesturl';
    public $description = 'دریافت آخرین آدرس سایت و اتصال حساب تلگرام به سایت';

    public function handle($message, $match = []) {
        $telegramService = $this->telegramService;
        
        // متن پیام برای ارسال به کاربر
        $text = sprintf(
            'آخرین آدرس سایت %s: %s',
            config('v2board.app_name', 'V2Board'),  // دریافت نام سایت از تنظیمات
            config('v2board.app_url')              // دریافت URL سایت از تنظیمات
        );
        
        // ارسال پیام به کاربر در تلگرام
        $telegramService->sendMessage($message->chat_id, $text, 'markdown');
    }
}
