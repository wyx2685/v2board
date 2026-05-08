<?php

namespace App\Plugins\Telegram\Commands;

use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Plugins\Telegram\Telegram;
use App\Services\OrderService;

class CheckPayment extends Telegram {
    public $callbackAction = ['check_payment', 'cancel_order'];

    public function handle($message, $match = []) {
        // Callback-only command
    }

    public function handleCallback($message, $params = []) {
        if (!$message->is_private) return;
        $action = $params[0] ?? '';
        $tradeNo = $params[1] ?? '';

        $user = User::where('telegram_id', $message->chat_id)->first();
        if (!$user) {
            $this->telegramService->sendMessage($message->chat_id, '⚠️ Bạn chưa liên kết tài khoản.');
            return;
        }

        switch ($action) {
            case 'check_payment':
                $this->checkPayment($message->chat_id, $user, $tradeNo);
                break;
            case 'cancel_order':
                $this->cancelOrder($message->chat_id, $user, $tradeNo);
                break;
        }
    }

    private function checkPayment(int $chatId, User $user, string $tradeNo)
    {
        $order = Order::where('trade_no', $tradeNo)->where('user_id', $user->id)->first();
        if (!$order) {
            $this->telegramService->sendMessage($chatId, '⚠️ Đơn hàng không tồn tại.');
            return;
        }

        switch ($order->status) {
            case 0:
                $keyboard = [
                    [['text' => '🔄 Kiểm Tra Lại', 'callback_data' => "check_payment:{$tradeNo}"]],
                    [['text' => '❌ Hủy Đơn Hàng', 'callback_data' => "cancel_order:{$tradeNo}"]],
                    [['text' => '🏠 Menu Chính', 'callback_data' => 'start']],
                ];
                $this->telegramService->sendMessageWithKeyboard(
                    $chatId,
                    "⏳ Chưa nhận được thanh toán cho đơn #{$tradeNo}.\n\nVui lòng thử lại sau 1-2 phút.",
                    $keyboard
                );
                break;
            case 1:
            case 3:
                $plan = Plan::find($order->plan_id);
                $planName = $order->plan_id == 0 ? 'Nạp tiền' : ($plan ? $plan->name : 'N/A');
                $text = "✅ THANH TOÁN THÀNH CÔNG\n";
                $text .= "━━━━━━━━━━━━━━━━━━━━━\n";
                $text .= "📦 Gói: {$planName}\n";
                $text .= "📋 Mã đơn: #{$tradeNo}\n";
                $text .= "🎉 Gói dịch vụ đã được kích hoạt!";

                $keyboard = [
                    [['text' => '📋 Xem Gói Hiện Tại', 'callback_data' => 'subscription']],
                    [['text' => '🔗 Link Đăng Ký', 'callback_data' => 'subscribe_url']],
                    [['text' => '🏠 Menu Chính', 'callback_data' => 'start']],
                ];
                $this->telegramService->sendMessageWithKeyboard($chatId, $text, $keyboard);
                break;
            case 2:
                $keyboard = [[['text' => '🏠 Menu Chính', 'callback_data' => 'start']]];
                $this->telegramService->sendMessageWithKeyboard($chatId, "❌ Đơn hàng #{$tradeNo} đã bị hủy.", $keyboard);
                break;
        }
    }

    private function cancelOrder(int $chatId, User $user, string $tradeNo)
    {
        $order = Order::where('trade_no', $tradeNo)->where('user_id', $user->id)->first();
        if (!$order) {
            $this->telegramService->sendMessage($chatId, '⚠️ Đơn hàng không tồn tại.');
            return;
        }

        if ($order->status !== 0) {
            $this->telegramService->sendMessage($chatId, '⚠️ Chỉ có thể hủy đơn đang chờ thanh toán.');
            return;
        }

        $orderService = new OrderService($order);
        if (!$orderService->cancel()) {
            $this->telegramService->sendMessage($chatId, '❌ Hủy đơn thất bại. Vui lòng thử lại.');
            return;
        }

        $keyboard = [
            [['text' => '🛒 Mua Gói Khác', 'callback_data' => 'plans']],
            [['text' => '🏠 Menu Chính', 'callback_data' => 'start']],
        ];
        $this->telegramService->sendMessageWithKeyboard(
            $chatId,
            "✅ Đơn hàng #{$tradeNo} đã được hủy.\nSố dư ví đã được hoàn trả (nếu có).",
            $keyboard
        );
    }
}
