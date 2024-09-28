<?php

namespace App\Http\Controllers\V1\Guest;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function notify($method, $uuid, Request $request)
    {
        $this->logInfo('Payment notification received', [
            'method' => $method,
            'uuid' => $uuid,
            'request_data' => $request->all(),
        ]);

        try {
            $paymentService = new PaymentService($method, null, $uuid);
            $verificationResult = $paymentService->notify($request->all());

            $this->logInfo('Payment verification result', ['verify' => $verificationResult]);

            if ($verificationResult === false) {
                throw new \Exception('Transaction was not successful or verification failed');
            }

            if (!$this->handleOrder($verificationResult['trade_no'], $verificationResult['callback_no'])) {
                throw new \Exception('Handle error');
            }

            $response = $verificationResult['custom_result'] ?? 'success';
            $this->logInfo('Payment process completed', ['response' => $response]);

            return $this->renderPaymentResult(true, 'پرداخت با موفقیت انجام شد.', $verificationResult['trade_no']);
        } catch (\Exception $e) {
            $this->logError('Payment notification error', $e);

            return $this->renderPaymentResult(false, 'خطا در پردازش پرداخت.');
        }
    }

    private function handleOrder($tradeNo, $transactionId)
    {
        $this->logInfo('Handling payment', [
            'trade_no' => $tradeNo,
            'transaction_id' => $transactionId
        ]);

        $order = Cache::remember("order_{$tradeNo}", 60, function() use ($tradeNo) {
            return Order::where('trade_no', $tradeNo)->first();
        });

        if (!$order) {
            $this->logError('Order not found', ['trade_no' => $tradeNo]);
            return false;
        }

        $this->logInfo('Order found', ['order' => $order->toArray()]);

        if ($order->status !== 0) {
            $this->logInfo('Order already processed', ['trade_no' => $tradeNo]);
            return true;
        }

        $user = User::find($order->user_id);
        $orderService = new OrderService($order);

        if (!$orderService->paid($transactionId)) {
            $this->logError('Could not update order status', [
                'trade_no' => $tradeNo,
                'transaction_id' => $transactionId
            ]);
            return false;
        }

        $this->logInfo('Order status updated successfully', [
            'trade_no' => $tradeNo,
            'transaction_id' => $transactionId
        ]);

        $adjustedAmount = $order->total_amount / 1000;
        $message = $this->generateTelegramMessage($adjustedAmount, $order, $user);

        $telegramService = new TelegramService();
        $telegramService->sendMessageWithAdmin($message);

        $this->logInfo('Telegram message sent', [
            'message' => $message
        ]);

        return true;
    }

    private function renderPaymentResult($success, $message, $tradeNo = null)
    {
        $this->logInfo('Rendering payment result', [
            'success' => $success,
            'trade_no' => $tradeNo,
            'message' => $message
        ]);

        $orderInfo = '';
        if ($success && $tradeNo) {
            $order = Cache::remember("order_{$tradeNo}", 60, function() use ($tradeNo) {
                return Order::where('trade_no', $tradeNo)->first();
            });

            if ($order) {
                $adjustedAmount = $order->total_amount / 1000;
                $orderInfo = "<p>شماره سفارش: {$order->trade_no}</p>" .
                             "<p>مبلغ پرداخت شده: " . number_format($adjustedAmount, 0, '.', ',') . " تومان</p>";
            }
        }

        if ($success) {
            return view('success', compact('orderInfo'));
        } else {
            return view('failure', compact('message'));
        }
    }

    private function logInfo($message, $data = [])
    {
        Log::info($message, $data);
    }

    private function logError($message, \Exception $e)
    {
        Log::error($message, [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    private function generateTelegramMessage($adjustedAmount, $order, $user)
    {
        $subscribeLink = "http://ddr.drmobilejayzan.info/api/v1/client/subscribe?token=" . $user->token;

        return sprintf(
            "💰 پرداخت موفق به مبلغ %s تومان\n———————————————\nشماره سفارش: %s\nایمیل کاربر: %s\n———————————————\nلینک اشتراک: %s",
            number_format($adjustedAmount, 0, '.', ','),
            $order->trade_no,
            $user->email,
            $subscribeLink
        );
    }
}
