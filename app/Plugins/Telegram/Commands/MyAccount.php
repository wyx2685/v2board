<?php

namespace App\Plugins\Telegram\Commands;

use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Plugins\Telegram\Telegram;
use App\Utils\Helper;

class MyAccount extends Telegram {
    public $callbackAction = ['account', 'subscription', 'data_history', 'orders', 'subscribe_url', 'unbind'];

    public function handle($message, $match = []) {
        // Callback-only command
    }

    public function handleCallback($message, $params = []) {
        if (!$message->is_private) return;
        $user = User::where('telegram_id', $message->chat_id)->first();
        if (!$user) {
            $this->telegramService->sendMessage($message->chat_id, '⚠️ Bạn chưa liên kết tài khoản. Vui lòng gõ /start');
            return;
        }

        $action = $params[0] ?? '';
        switch ($action) {
            case 'account':      $this->showAccountInfo($message->chat_id, $user); break;
            case 'subscription': $this->showSubscription($message->chat_id, $user); break;
            case 'data_history': $this->showDataHistory($message->chat_id, $user); break;
            case 'orders':       $this->showOrderHistory($message->chat_id, $user); break;
            case 'subscribe_url':$this->showSubscribeUrl($message->chat_id, $user); break;
            case 'unbind':       $this->unbindAccount($message->chat_id, $user); break;
        }
    }

    private function showAccountInfo(int $chatId, User $user)
    {
        $balance = number_format($user->balance / 100, 0, ',', '.') . ' đồng';
        $createdAt = date('d/m/Y', $user->created_at);
        $status = '⚪ Chưa kích hoạt';
        if ($user->expired_at === null && $user->plan_id) {
            $status = '🟢 Trọn đời';
        } elseif ($user->expired_at > time()) {
            $status = '🟢 Đang hoạt động';
        } elseif ($user->expired_at) {
            $status = '🔴 Đã hết hạn';
        }

        $text = "👤 THÔNG TIN TÀI KHOẢN\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "📧 Email: {$user->email}\n";
        $text .= "💰 Số dư: {$balance}\n";
        $text .= "📅 Ngày tạo: {$createdAt}\n";
        $text .= "📌 Trạng thái: {$status}\n";
        if ($user->discount) {
            $text .= "🎖 Giảm giá VIP: {$user->discount}%\n";
        }

        $keyboard = [[['text' => '⬅️ Quay Lại Menu', 'callback_data' => 'start']]];
        $this->telegramService->sendMessageWithKeyboard($chatId, $text, $keyboard);
    }

    private function showSubscription(int $chatId, User $user)
    {
        if (!$user->plan_id) {
            $keyboard = [
                [['text' => '🛒 Mua Gói Ngay', 'callback_data' => 'plans']],
                [['text' => '⬅️ Quay Lại Menu', 'callback_data' => 'start']],
            ];
            $this->telegramService->sendMessageWithKeyboard($chatId, "📋 Bạn chưa đăng ký gói dịch vụ nào.", $keyboard);
            return;
        }

        $plan = Plan::find($user->plan_id);
        $planName = $plan ? $plan->name : 'Không rõ';
        $totalTraffic = Helper::trafficConvert($user->transfer_enable);
        $usedTraffic = Helper::trafficConvert($user->u + $user->d);
        $remainingTraffic = Helper::trafficConvert($user->transfer_enable - ($user->u + $user->d));

        $expireText = 'Trọn đời';
        if ($user->expired_at !== null) {
            $daysLeft = max(0, ceil(($user->expired_at - time()) / 86400));
            $expireText = date('d/m/Y H:i', $user->expired_at) . " (còn {$daysLeft} ngày)";
        }

        $text = "📋 GÓI ĐĂNG KÝ HIỆN TẠI\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "📦 Gói: {$planName}\n";
        $text .= "📊 Tổng data: {$totalTraffic}\n";
        $text .= "📈 Đã dùng: {$usedTraffic}\n";
        $text .= "📉 Còn lại: {$remainingTraffic}\n";
        $text .= "⏰ Hết hạn: {$expireText}\n";
        if ($user->device_limit) $text .= "📱 Giới hạn TB: {$user->device_limit}\n";
        if ($user->speed_limit) $text .= "⚡ Tốc độ: {$user->speed_limit} Mbps\n";

        $keyboard = [
            [['text' => '🛒 Gia Hạn / Nâng Cấp', 'callback_data' => 'plans']],
            [['text' => '⬅️ Quay Lại Menu', 'callback_data' => 'start']],
        ];
        $this->telegramService->sendMessageWithKeyboard($chatId, $text, $keyboard);
    }

    private function showDataHistory(int $chatId, User $user)
    {
        $text = "📊 THỐNG KÊ LƯU LƯỢNG\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "⬆️ Upload: " . Helper::trafficConvert($user->u) . "\n";
        $text .= "⬇️ Download: " . Helper::trafficConvert($user->d) . "\n";
        $text .= "📊 Tổng đã dùng: " . Helper::trafficConvert($user->u + $user->d) . "\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "📦 Tổng data: " . Helper::trafficConvert($user->transfer_enable) . "\n";
        $text .= "📉 Còn lại: " . Helper::trafficConvert($user->transfer_enable - ($user->u + $user->d)) . "\n";

        $keyboard = [[['text' => '⬅️ Quay Lại Menu', 'callback_data' => 'start']]];
        $this->telegramService->sendMessageWithKeyboard($chatId, $text, $keyboard);
    }

    private function showOrderHistory(int $chatId, User $user)
    {
        $orders = Order::where('user_id', $user->id)->orderBy('created_at', 'DESC')->limit(5)->get();

        $text = "🧾 LỊCH SỬ ĐƠN HÀNG (5 gần nhất)\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━━\n";

        if ($orders->isEmpty()) {
            $text .= "Chưa có đơn hàng nào.\n";
        } else {
            foreach ($orders as $order) {
                $plan = Plan::find($order->plan_id);
                $planName = $order->plan_id == 0 ? 'Nạp tiền' : ($plan ? $plan->name : 'N/A');
                $amount = number_format($order->total_amount / 100, 0, ',', '.');
                $date = date('d/m/Y', $order->created_at);

                $statusMap = [0 => '⏳ Chờ TT', 1 => '💳 Đã TT', 2 => '❌ Đã hủy', 3 => '✅ Xong', 4 => '🔄 Thay thế'];
                $statusText = $statusMap[$order->status] ?? '❓';

                $text .= "\n{$statusText} | {$planName}\n";
                $text .= "   💰 {$amount}đ | 📅 {$date}\n";
                $text .= "   🔖 #{$order->trade_no}\n";
            }
        }

        $keyboard = [[['text' => '⬅️ Quay Lại Menu', 'callback_data' => 'start']]];
        $this->telegramService->sendMessageWithKeyboard($chatId, $text, $keyboard);
    }

    private function showSubscribeUrl(int $chatId, User $user)
    {
        $appUrl = config('v2board.app_url', '');
        $subscribeUrl = $appUrl . '/api/v1/client/subscribe?token=' . $user->token;

        $text = "🔗 LINK ĐĂNG KÝ (SUBSCRIBE URL)\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "Copy link bên dưới và import vào ứng dụng VPN:\n\n";
        $text .= $subscribeUrl;

        $keyboard = [[['text' => '⬅️ Quay Lại Menu', 'callback_data' => 'start']]];
        $this->telegramService->sendMessageWithKeyboard($chatId, $text, $keyboard);
    }

    private function unbindAccount(int $chatId, User $user)
    {
        $user->telegram_id = null;
        if (!$user->save()) {
            $this->telegramService->sendMessage($chatId, '❌ Hủy liên kết thất bại.');
            return;
        }
        $this->telegramService->sendMessage($chatId, "✅ Hủy liên kết thành công!\n\nGõ /start để bắt đầu lại.");
    }
}
