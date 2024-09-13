<?php

namespace App\Plugins\Telegram\Commands;

use App\Models\User;
use App\Plugins\Telegram\Telegram;

class UnBind extends Telegram {
    public $command = '/unbind';  // دستور برای لغو اتصال
    public $description = 'لغو اتصال حساب تلگرام از سایت';

    public function handle($message, $match = []) {
        // فقط پیام‌های خصوصی را بررسی می‌کنیم
        if (!$message->is_private) return;

        // جستجوی کاربر با شناسه تلگرام
        $user = User::where('telegram_id', $message->chat_id)->first();
        $telegramService = $this->telegramService;

        // بررسی این که آیا کاربر پیدا شده است
        if (!$user) {
            $telegramService->sendMessage($message->chat_id, 'اطلاعات کاربری شما یافت نشد، لطفاً ابتدا حساب خود را متصل کنید', 'markdown');
            return;
        }

        // حذف شناسه تلگرام از حساب کاربر
        $user->telegram_id = NULL;
        
        // ذخیره‌سازی تغییرات
        if (!$user->save()) {
            abort(500, 'لغو اتصال ناموفق بود');
        }

        // ارسال پیام موفقیت به کاربر
        $telegramService->sendMessage($message->chat_id, 'حساب تلگرام با موفقیت از سایت لغو شد', 'markdown');
    }
}
