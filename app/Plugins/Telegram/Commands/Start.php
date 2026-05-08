<?php

namespace App\Plugins\Telegram\Commands;

use App\Models\User;
use App\Plugins\Telegram\Telegram;
use App\Utils\Helper;

class Start extends Telegram {
    public $command = '/start';
    public $description = 'Menu chính';
    public $callbackAction = ['start', 'link_account', 'quick_register'];

    public function handle($message, $match = []) {
        if (!$message->is_private) return;
        $user = User::where('telegram_id', $message->chat_id)->first();
        if ($user) {
            $this->showMainMenu($message->chat_id, $user);
        } else {
            $this->showWelcomeMenu($message->chat_id);
        }
    }

    public function handleCallback($message, $params = []) {
        if (!$message->is_private) return;
        $action = $params[0] ?? '';

        switch ($action) {
            case 'start':
                $user = User::where('telegram_id', $message->chat_id)->first();
                if ($user) {
                    $this->showMainMenu($message->chat_id, $user);
                } else {
                    $this->showWelcomeMenu($message->chat_id);
                }
                break;
            case 'link_account':
                $this->showLinkAccountGuide($message->chat_id);
                break;
            case 'quick_register':
                $this->quickRegister($message->chat_id);
                break;
        }
    }

    private function showMainMenu(int $chatId, User $user)
    {
        $appName = config('v2board.app_name', 'ZicBoard');
        $text = "👋 Xin chào! Chào mừng bạn đến với {$appName}.\n\nVui lòng chọn một mục bên dưới:";

        $keyboard = [
            [
                ['text' => '🛒 Mua Gói Dịch Vụ', 'callback_data' => 'plans'],
                ['text' => '📋 Gói Hiện Tại', 'callback_data' => 'subscription'],
            ],
            [
                ['text' => '👤 Thông Tin Tài Khoản', 'callback_data' => 'account'],
                ['text' => '📊 Lịch Sử Data', 'callback_data' => 'data_history'],
            ],
            [
                ['text' => '🧾 Lịch Sử Đơn Hàng', 'callback_data' => 'orders'],
                ['text' => '🔗 Link Đăng Ký', 'callback_data' => 'subscribe_url'],
            ],
            [
                ['text' => '🔓 Hủy Liên Kết', 'callback_data' => 'unbind'],
            ],
        ];

        $this->telegramService->sendMessageWithKeyboard($chatId, $text, $keyboard);
    }

    private function showWelcomeMenu(int $chatId)
    {
        $appName = config('v2board.app_name', 'ZicBoard');
        $text = "👋 Xin chào! Chào mừng bạn đến với {$appName}.\n\n";
        $text .= "Bạn chưa có tài khoản nào được liên kết.\n";
        $text .= "Vui lòng chọn một trong các tùy chọn bên dưới:";

        $keyboard = [
            [
                ['text' => '🔗 Liên Kết Tài Khoản Hiện Có', 'callback_data' => 'link_account'],
            ],
            [
                ['text' => '🚀 Mua Gói Nhanh (Tạo TK Mới)', 'callback_data' => 'quick_register'],
            ],
        ];

        $this->telegramService->sendMessageWithKeyboard($chatId, $text, $keyboard);
    }

    private function showLinkAccountGuide(int $chatId)
    {
        $text = "🔗 LIÊN KẾT TÀI KHOẢN\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━━\n\n";
        $text .= "Để liên kết tài khoản website với Telegram, vui lòng gửi lệnh:\n\n";
        $text .= "/bind <Link đăng ký của bạn>\n\n";
        $text .= "Bạn có thể lấy Link đăng ký tại trang Dashboard trên website.";

        $keyboard = [
            [['text' => '⬅️ Quay Lại', 'callback_data' => 'start']],
        ];

        $this->telegramService->sendMessageWithKeyboard($chatId, $text, $keyboard);
    }

    private function quickRegister(int $chatId)
    {
        // Check if already registered
        $existing = User::where('telegram_id', $chatId)->first();
        if ($existing) {
            $this->showMainMenu($chatId, $existing);
            return;
        }

        // Transparent registration
        $email = 'tg_' . $chatId . '@tg.local';
        $password = Helper::guid();
        $passwordShort = substr($password, 0, 12);

        // Check if email already taken (edge case)
        if (User::where('email', $email)->first()) {
            $existingUser = User::where('email', $email)->first();
            $existingUser->telegram_id = $chatId;
            $existingUser->save();
            $this->showMainMenu($chatId, $existingUser);
            return;
        }

        $user = new User();
        $user->email = $email;
        $user->password = password_hash($passwordShort, PASSWORD_DEFAULT);
        $user->uuid = Helper::guid(true);
        $user->token = Helper::guid();
        $user->telegram_id = $chatId;

        // Try out plan if configured
        if ((int)config('v2board.try_out_plan_id', 0)) {
            $plan = \App\Models\Plan::find(config('v2board.try_out_plan_id'));
            if ($plan) {
                $user->transfer_enable = $plan->transfer_enable * 1073741824;
                $user->device_limit = $plan->device_limit;
                $user->plan_id = $plan->id;
                $user->group_id = $plan->group_id;
                $user->expired_at = time() + (config('v2board.try_out_hour', 1) * 3600);
                $user->speed_limit = $plan->speed_limit;
            }
        }

        if (!$user->save()) {
            $this->telegramService->sendMessage($chatId, '❌ Tạo tài khoản thất bại. Vui lòng thử lại.');
            return;
        }

        $appName = config('v2board.app_name', 'ZicBoard');
        $appUrl = config('v2board.app_url', '');
        $text = "✅ TÀI KHOẢN ĐÃ ĐƯỢC TẠO THÀNH CÔNG\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "📧 Email: {$email}\n";
        $text .= "🔑 Mật khẩu: {$passwordShort}\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━━\n";
        if ($appUrl) {
            $text .= "🌐 Website: {$appUrl}\n";
            $text .= "━━━━━━━━━━━━━━━━━━━━━\n";
        }
        $text .= "⚠️ Vui lòng lưu lại thông tin trên!\n\n";
        $text .= "Bạn có thể bắt đầu mua gói ngay bây giờ:";

        $keyboard = [
            [['text' => '🛒 Mua Gói Dịch Vụ', 'callback_data' => 'plans']],
            [['text' => '🏠 Menu Chính', 'callback_data' => 'start']],
        ];

        $this->telegramService->sendMessageWithKeyboard($chatId, $text, $keyboard);
    }
}
