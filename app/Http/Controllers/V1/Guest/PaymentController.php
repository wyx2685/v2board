<?php

namespace App\Http\Controllers\V1\Guest;

use App\Http\Controllers\Controller;
use App\Models\Order;
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
            if (is_array($verify) && !empty($verify['ignored'])) {
                return $verify['custom_result'] ?? 'success';
            }
            if (
                !is_array($verify)
                || empty($verify['trade_no'])
                || empty($verify['callback_no'])
            ) {
                abort(500, 'verify error');
            }
            if (!$this->handle($verify, $paymentService->getPaymentId())) {
                abort(500, 'handle error');
            }
            return(isset($verify['custom_result']) ? $verify['custom_result'] : 'success');
        } catch (\Throwable $e) {
            abort(500, 'fail');
        }
    }

    private function handle(array $notification, $paymentId)
    {
        $order = Order::where('trade_no', $notification['trade_no'])->first();
        if (!$order) {
            abort(500, 'order is not found');
        }
        if ((int)$order->payment_id !== (int)$paymentId) return false;

        if (array_key_exists('amount', $notification)) {
            $expectedAmount = (int)$order->total_amount + (int)$order->handling_amount;
            if ((int)$notification['amount'] !== $expectedAmount) return false;
        }

        if (
            !empty($notification['currency'])
            && strtoupper((string)$notification['currency']) !== strtoupper((string)config('v2board.currency', 'CNY'))
        ) {
            return false;
        }

        $callbackNo = (string)$notification['callback_no'];
        if (
            $order->callback_no
            && !hash_equals((string)$order->callback_no, $callbackNo)
        ) {
            return false;
        }
        if (Order::where('callback_no', $callbackNo)->where('id', '!=', $order->id)->exists()) {
            return false;
        }

        if ((int)$order->status !== 0) return true;
        $orderService = new OrderService($order);
        if (!$orderService->paid($callbackNo)) {
            return false;
        }
        if ($orderService->paymentTransitioned()) {
            $telegramService = new TelegramService();
            $telegramService->sendTranslatedMessageWithAdmin('telegram.payment_received', [
                'amount' => $order->total_amount / 100,
                'currency' => config('v2board.currency', 'CNY'),
                'trade_no' => $order->trade_no,
            ], false, '');
        }
        return true;
    }
}
