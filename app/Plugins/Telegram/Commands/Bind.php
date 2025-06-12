<?php

namespace App\Plugins\Telegram\Commands;

use App\Models\User;
use App\Plugins\Telegram\Telegram;

class Bind extends Telegram {
    public $command = '/bind';
    public $description = 'اتصال حساب تلگرام به وب‌سایت';

    public function handle($message, $match = []) {
        if (!$message->is_private) return;
        if (!isset($message->args[0])) {
            abort(500, 'پارامتر اشتباه است، لطفاً آدرس اشتراک را همراه ارسال کنید');
        }
        $subscribeUrl = $message->args[0];
        $subscribeUrl = parse_url($subscribeUrl);
        parse_str($subscribeUrl['query'], $query);
        $token = $query['token'];
        if (!$token) {
            abort(500, 'آدرس اشتراک نامعتبر است');
        }
        $user = User::where('token', $token)->first();
        if (!$user) {
            abort(500, 'کاربر وجود ندارد');
        }
        if ($user->telegram_id) {
            abort(500, 'این حساب قبلاً به تلگرام متصل شده است');
        }
        $user->telegram_id = $message->chat_id;
        if (!$user->save()) {
            abort(500, 'تنظیم ناموفق بود');
        }
        $telegramService = $this->telegramService;
        $telegramService->sendMessage($message->chat_id, 'اتصال با موفقیت انجام شد');
    }
}
