<?php

namespace App\Http\Controllers\V1\Guest;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Plan;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\TelegramService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function notify($method, $uuid, Request $request)
    {
        try {
            $paymentService = new PaymentService($method, null, $uuid);
            $verify = $paymentService->notify($request->input());
            if (!$verify) abort(500, 'verify error');
            if (!$this->handle($verify['trade_no'], $verify['callback_no'])) {
                abort(500, 'handle error');
            }
            return(isset($verify['custom_result']) ? $verify['custom_result'] : 'success');
        } catch (\Exception $e) {
            abort(500, 'fail');
        }
    }

    private function handle($tradeNo, $callbackNo)
    {
        $order = Order::where('trade_no', $tradeNo)->first();
        if (!$order) {
            abort(500, 'order is not found');
        }
        if ($order->status !== 0) return true;

        $orderService = new OrderService($order);
        if (!$orderService->paid($callbackNo)) {
            return false;
        }

        $user = User::find($order->user_id);
        $plan = Plan::find($order->plan_id);

        switch ($order->period) {
            case 'month_price':
                $periodText = '1 tháng';
                break;
            case 'quarter_price':
                $periodText = '3 tháng';
                break;
            case 'half_year_price':
                $periodText = '6 tháng';
                break;
            case 'year_price':
                $periodText = '12 tháng';
                break;
            case 'two_year_price':
                $periodText = '24 tháng';
                break;
            case 'three_year_price':
                $periodText = '36 tháng';
                break;
            case 'onetime_price':
                $periodText = 'Trọn đời';
                break;
            case 'reset_price':
                $periodText = 'Reset gói';
                break;
            case 'deposit':
                $periodText = 'Nạp Tiền';
                break;
            default:
                $periodText = 'Không rõ';
                break;
        }

        $formattedAmount = number_format($order->total_amount / 100, 0, ',', '.');
        $planName = ($order->plan_id == 0) ? 'Nạp tiền' : ($plan->name ?? 'Không rõ');

        $ctvEmail = null;
        if (!empty($user->invite_user_id)) {
            $inviter = User::find($user->invite_user_id);
            $ctvEmail = $inviter->email ?? 'Không';
        } else {
            $ctvEmail = 'Không';
        }

        $telegramService = new TelegramService();
        $message = sprintf(
            "💰Thông báo thanh toán thành công
———————————————
💲 Số tiền: %s đồng
———————————————
📋 ID Đơn hàng: %s
———————————————
📧 Email: %s
———————————————
📄 Gói: %s
———————————————
⏱️ Chu kỳ: %s
———————————————
👥 CTV: %s
———————————————
Mã giao dịch: %s",
            $formattedAmount,
            $order->id,
            $user->email,
            $planName,
            $periodText,
            $ctvEmail,
            $order->trade_no
        );
        $telegramService->sendMessageWithAdmin($message);
        return true;
    }

}
