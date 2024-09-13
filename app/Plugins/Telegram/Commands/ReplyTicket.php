<?php

namespace App\Plugins\Telegram\Commands;

use App\Models\User;
use App\Plugins\Telegram\Telegram;
use App\Services\TicketService;

class ReplyTicket extends Telegram {
    public $regex = '/[#](.*)/';  // الگوی مورد نیاز برای شناسایی تیکت‌ها از پیام
    public $description = 'پاسخ سریع به تیکت‌ها';

    public function handle($message, $match = []) {
        // فقط پیام‌های خصوصی مجاز هستند
        if (!$message->is_private) return;
        
        // فراخوانی تابع پاسخ به تیکت با استفاده از شناسه تیکت
        $this->replayTicket($message, $match[1]);
    }

    private function replayTicket($msg, $ticketId)
    {
        // یافتن کاربر با استفاده از شناسه تلگرام
        $user = User::where('telegram_id', $msg->chat_id)->first();
        if (!$user) {
            abort(500, 'کاربر وجود ندارد');  // خطای 500 در صورت عدم وجود کاربر
        }

        // بررسی این که متن پیام موجود باشد
        if (!$msg->text) return;

        // بررسی این که کاربر ادمین یا کارمند باشد
        if (!($user->is_admin || $user->is_staff)) return;

        // پاسخ به تیکت با استفاده از سرویس تیکت
        $ticketService = new TicketService();
        $ticketService->replyByAdmin(
            $ticketId,
            $msg->text,
            $user->id
        );

        // ارسال پیام موفقیت به کاربر
        $telegramService = $this->telegramService;
        $telegramService->sendMessage($msg->chat_id, "تیکت شماره #`{$ticketId}` با موفقیت پاسخ داده شد", 'markdown');
        
        // اطلاع‌رسانی به ادمین‌ها
        $telegramService->sendMessageWithAdmin("تیکت شماره #`{$ticketId}` توسط {$user->email} پاسخ داده شد", true);
    }
}
