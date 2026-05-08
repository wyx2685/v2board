<?php

namespace App\Plugins\Telegram\Commands;

use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Plugins\Telegram\AutoOrder\VietQRHelper;
use App\Plugins\Telegram\Telegram;
use App\Services\OrderService;
use App\Services\PlanService;
use App\Services\UserService;
use App\Utils\Helper;
use Illuminate\Support\Facades\DB;

class Buy extends Telegram {
    public $callbackAction = ['buy', 'confirm_buy'];

    private $periodLabels = [
        'month_price' => '1 Tháng',
        'quarter_price' => '3 Tháng',
        'half_year_price' => '6 Tháng',
        'year_price' => '1 Năm',
        'two_year_price' => '2 Năm',
        'three_year_price' => '3 Năm',
        'onetime_price' => 'Trọn Đời',
        'reset_price' => 'Reset Data',
    ];

    public function handle($message, $match = []) {
        // Not a text command
    }

    public function handleCallback($message, $params = []) {
        if (!$message->is_private) return;
        $action = $params[0] ?? '';

        $user = User::where('telegram_id', $message->chat_id)->first();
        if (!$user) {
            $this->telegramService->sendMessage($message->chat_id, '⚠️ Bạn chưa liên kết tài khoản. Vui lòng gõ /start');
            return;
        }

        switch ($action) {
            case 'buy':
                $planId = (int)($params[1] ?? 0);
                $period = $params[2] ?? '';
                $this->showConfirmation($message->chat_id, $user, $planId, $period);
                break;
            case 'confirm_buy':
                $planId = (int)($params[1] ?? 0);
                $period = $params[2] ?? '';
                $this->processOrder($message->chat_id, $user, $planId, $period);
                break;
        }
    }

    private function showConfirmation(int $chatId, User $user, int $planId, string $period)
    {
        $plan = Plan::find($planId);
        if (!$plan || $plan->$period === null) {
            $this->telegramService->sendMessage($chatId, '⚠️ Gói hoặc chu kỳ không hợp lệ.');
            return;
        }

        $periodLabel = $this->periodLabels[$period] ?? $period;
        $price = number_format($plan->$period / 100, 0, ',', '.');
        $balance = number_format($user->balance / 100, 0, ',', '.');

        $text = "🛒 XÁC NHẬN ĐƠN HÀNG\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "📦 Gói: {$plan->name}\n";
        $text .= "⏱️ Chu kỳ: {$periodLabel}\n";
        $text .= "💰 Giá: {$price}đ\n";
        $text .= "💳 Số dư ví: {$balance}đ\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━━\n";

        if ($user->balance >= $plan->$period) {
            $text .= "✅ Số dư đủ! Thanh toán tự động từ ví.\n";
        } else {
            $text .= "⚠️ Số dư không đủ. Bạn sẽ nhận mã QR chuyển khoản.\n";
        }

        $text .= "\nBạn có muốn tiếp tục?";

        $keyboard = [
            [['text' => '✅ Xác Nhận Mua', 'callback_data' => "confirm_buy:{$planId}:{$period}"]],
            [['text' => '❌ Hủy', 'callback_data' => "plan_detail:{$planId}"]],
        ];

        $this->telegramService->sendMessageWithKeyboard($chatId, $text, $keyboard);
    }

    private function processOrder(int $chatId, User $user, int $planId, string $period)
    {
        // Check for pending orders
        $userService = new UserService();
        if ($userService->isNotCompleteOrderByUserId($user->id)) {
            $pendingOrder = Order::where('user_id', $user->id)->where('status', 0)->first();
            $keyboard = [];
            if ($pendingOrder) {
                $keyboard[] = [['text' => '❌ Hủy Đơn Cũ', 'callback_data' => "cancel_order:{$pendingOrder->trade_no}"]];
            }
            $keyboard[] = [['text' => '⬅️ Quay Lại Menu', 'callback_data' => 'start']];
            $this->telegramService->sendMessageWithKeyboard(
                $chatId,
                "⚠️ Bạn đang có đơn hàng chờ thanh toán.\nVui lòng thanh toán hoặc hủy đơn cũ trước.",
                $keyboard
            );
            return;
        }

        $planService = new PlanService($planId);
        $plan = $planService->plan;

        if (!$plan) {
            $this->telegramService->sendMessage($chatId, '⚠️ Gói dịch vụ không tồn tại.');
            return;
        }

        if ($user->plan_id !== $plan->id && !$planService->haveCapacity() && $period !== 'reset_price') {
            $this->telegramService->sendMessage($chatId, '⚠️ Gói dịch vụ đã hết chỗ.');
            return;
        }

        if ($plan[$period] === null) {
            $this->telegramService->sendMessage($chatId, '⚠️ Chu kỳ thanh toán không hợp lệ.');
            return;
        }

        if ($period === 'reset_price') {
            if (!$userService->isAvailable($user) || $plan->id !== $user->plan_id) {
                $this->telegramService->sendMessage($chatId, '⚠️ Gói đã hết hạn hoặc không khớp.');
                return;
            }
        }

        if ((!$plan->show && !$plan->renew) || (!$plan->show && $user->plan_id !== $plan->id)) {
            if ($period !== 'reset_price') {
                $this->telegramService->sendMessage($chatId, '⚠️ Gói dịch vụ đã ngừng bán.');
                return;
            }
        }

        if (!$plan->renew && $user->plan_id == $plan->id && $period !== 'reset_price') {
            $this->telegramService->sendMessage($chatId, '⚠️ Gói này không thể gia hạn.');
            return;
        }

        // Create order (mirrors OrderController@save)
        DB::beginTransaction();
        $order = new Order();
        $orderService = new OrderService($order);
        $order->user_id = $user->id;
        $order->plan_id = $plan->id;
        $order->period = $period;
        $order->trade_no = Helper::generateOrderNo();
        $order->total_amount = $plan[$period];

        $orderService->setVipDiscount($user);
        $orderService->setOrderType($user);

        // Deduct balance
        if ($user->balance > 0 && $order->total_amount > 0) {
            $remainingBalance = $user->balance - $order->total_amount;
            $balanceService = new UserService();
            if ($remainingBalance > 0) {
                if (!$balanceService->addBalance($order->user_id, -$order->total_amount)) {
                    DB::rollBack();
                    $this->telegramService->sendMessage($chatId, '❌ Lỗi trừ số dư ví.');
                    return;
                }
                $order->balance_amount = $order->total_amount;
                $order->total_amount = 0;
            } else {
                if (!$balanceService->addBalance($order->user_id, -$user->balance)) {
                    DB::rollBack();
                    $this->telegramService->sendMessage($chatId, '❌ Lỗi trừ số dư ví.');
                    return;
                }
                $order->balance_amount = $user->balance;
                $order->total_amount -= $user->balance;
            }
        }

        $orderService->setInvite($user);

        if (!$order->save()) {
            DB::rollBack();
            $this->telegramService->sendMessage($chatId, '❌ Tạo đơn hàng thất bại.');
            return;
        }
        DB::commit();

        // Auto-complete if total is 0 (fully paid by balance)
        if ($order->total_amount <= 0) {
            $orderService = new OrderService($order);
            if ($orderService->paid($order->trade_no)) {
                $periodLabel = $this->periodLabels[$period] ?? $period;
                $text = "✅ THANH TOÁN THÀNH CÔNG\n";
                $text .= "━━━━━━━━━━━━━━━━━━━━━\n";
                $text .= "📦 Gói: {$plan->name}\n";
                $text .= "⏱️ Chu kỳ: {$periodLabel}\n";
                $text .= "💰 Thanh toán bằng số dư ví\n";
                $text .= "━━━━━━━━━━━━━━━━━━━━━\n";
                $text .= "🎉 Gói dịch vụ đã được kích hoạt!";

                $keyboard = [
                    [['text' => '📋 Xem Gói Hiện Tại', 'callback_data' => 'subscription']],
                    [['text' => '🔗 Link Đăng Ký', 'callback_data' => 'subscribe_url']],
                    [['text' => '🏠 Menu Chính', 'callback_data' => 'start']],
                ];
                $this->telegramService->sendMessageWithKeyboard($chatId, $text, $keyboard);
            } else {
                $this->telegramService->sendMessage($chatId, '❌ Kích hoạt gói thất bại. Vui lòng liên hệ hỗ trợ.');
            }
            return;
        }

        // Need payment via VietQR
        $this->sendVietQR($chatId, $order, $plan, $period);
    }

    private function sendVietQR(int $chatId, Order $order, Plan $plan, string $period)
    {
        $amountVND = $order->total_amount / 100;

        if (!VietQRHelper::isConfigured()) {
            $text = "⚠️ Chưa cấu hình thông tin ngân hàng.\n";
            $text .= "Vui lòng liên hệ Admin để thanh toán đơn hàng #{$order->trade_no}";
            $keyboard = [[['text' => '🏠 Menu Chính', 'callback_data' => 'start']]];
            $this->telegramService->sendMessageWithKeyboard($chatId, $text, $keyboard);
            return;
        }

        // Generate transfer content: e.g. "HD1803" (prefix + order ID)
        $transferContent = VietQRHelper::getTransferContent($order->id);
        $qrUrl = VietQRHelper::generateQRUrl($transferContent, $amountVND);
        $periodLabel = $this->periodLabels[$period] ?? $period;

        $caption = "🧾 ĐƠN HÀNG #{$order->trade_no}\n";
        $caption .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $caption .= "📦 Gói: {$plan->name} - {$periodLabel}\n";
        $caption .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $caption .= VietQRHelper::getBankInfoText($transferContent, $amountVND);
        $caption .= "\n━━━━━━━━━━━━━━━━━━━━━\n";
        $caption .= "⏳ Đơn hàng sẽ tự hủy sau 2 giờ.\n";
        $caption .= "Lưu ảnh QR và mở app Ngân Hàng để quét!";

        $keyboard = [
            [['text' => '🔄 Kiểm Tra Thanh Toán', 'callback_data' => "check_payment:{$order->trade_no}"]],
            [['text' => '❌ Hủy Đơn Hàng', 'callback_data' => "cancel_order:{$order->trade_no}"]],
            [['text' => '🏠 Menu Chính', 'callback_data' => 'start']],
        ];

        $this->telegramService->sendPhoto($chatId, $qrUrl, $caption, $keyboard);
    }
}
