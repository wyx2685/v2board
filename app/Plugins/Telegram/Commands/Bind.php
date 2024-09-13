<?php

namespace App\Plugins\Telegram\Commands;

use App\Models\User;
use App\Plugins\Telegram\Telegram;

class Bind extends Telegram {
    public $command = '/bind';
    public $description = 'اتصال حساب تلگرام به سایت';

    public function handle($message, $match = []) {
        // بررسی این که پیام خصوصی است
        if (!$message->is_private) return;
        
        // بررسی این که آرگومان مورد نیاز وجود دارد
        if (empty($message->args[0])) {
            abort(400, 'پارامتر اشتباه است، لطفاً با آدرس اشتراک ارسال کنید'); // استفاده از کد خطای 400
        }

        // تجزیه آدرس اشتراک برای دریافت توکن
        $subscribeUrl = parse_url($message->args[0]);
        if (!$subscribeUrl || !isset($subscribeUrl['query'])) {
            abort(400, 'آدرس اشتراک نامعتبر است'); // بررسی معتبر بودن آدرس
        }

        // استخراج توکن از آدرس
        parse_str($subscribeUrl['query'], $query);
        $token = $query['token'] ?? null;

        if (empty($token)) {
            abort(422, 'آدرس اشتراک فاقد توکن معتبر است'); // استفاده از کد خطای 422 برای توکن نامعتبر
        }

        // جستجوی کاربر با توکن مربوطه
        $user = User::where('token', $token)->first();
        if (!$user) {
            abort(404, 'کاربر با توکن داده شده وجود ندارد'); // استفاده از کد خطای 404 برای کاربر ناموجود
        }

        // بررسی این که کاربر قبلاً حساب تلگرام خود را متصل کرده است یا خیر
        if ($user->telegram_id) {
            abort(409, 'این حساب کاربری قبلاً به حساب تلگرام متصل شده است'); // استفاده از کد خطای 409 برای تداخل
        }

        // ذخیره شناسه تلگرام کاربر
        $user->telegram_id = $message->chat_id;
        if (!$user->save()) {
            abort(500, 'ذخیره‌سازی اطلاعات ناموفق بود'); // استفاده از کد خطای 500 برای خطای سرور
        }

        // ارسال پیام موفقیت‌آمیز به کاربر
        $telegramService = $this->telegramService;
        $telegramService->sendMessage($message->chat_id, 'اتصال موفقیت‌آمیز بود');
    }
}
