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
            $cardNumber = $verificationResult['card_number'] ?? 'N/A';

            if (!$this->handleOrder($verificationResult['trade_no'], $verificationResult['callback_no'], $cardNumber)) {
                throw new \Exception('Handle error');
            }
            $response = $verificationResult['custom_result'] ?? 'success';
            $this->logInfo('Payment process completed', ['response' => $response]);

            $this->logInfo('Redirecting to success page', [
                'trade_no' => $verificationResult['trade_no']
            ]);
            return $this->renderPaymentResult(true, 'پرداخت با موفقیت انجام شد.', $verificationResult['trade_no']);
        } catch (\Exception $e) {
            $this->logError('Payment notification error', $e);

            return $this->renderPaymentResult(false, 'خطا در پردازش پرداخت.');
        }
    }
    private function handleOrder($tradeNo, $transactionId, $cardNumber = 'N/A')
    {
        $this->logInfo('Handling payment', [
            'trade_no' => $tradeNo,
            'transaction_id' => $transactionId,
            'card_number' => $cardNumber
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
        if ($order->total_amount == 0 && $order->balance_amount > 0) {
            $this->logInfo('Order paid using balance', [
                'trade_no' => $tradeNo,
                'balance_used' => $order->balance_amount
            ]);
            $order->status = 3;
            $order->paid_at = now();
            $order->updated_at = now();
            $order->save();

            $this->logInfo('Order status updated successfully using balance', [
                'trade_no' => $tradeNo
            ]);
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
        $adjustedAmount = $order->total_amount;
        $message = $this->generateTelegramMessage($adjustedAmount, $order, $user, $cardNumber);

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
                $adjustedAmount = ($order->total_amount > 0) ? $order->total_amount : $order->balance_amount;
                $orderInfo = "<p>شماره سفارش: {$order->trade_no}</p>" .
                             "<p>مبلغ پرداخت شده: " . number_format($adjustedAmount, 0, '.', ',') . " تومان</p>";
            }
        }
        if ($success) {
            $this->logInfo('Success page displayed', ['trade_no' => $tradeNo]);
            return view('success', compact('orderInfo'));
        } else {
            $this->logInfo('Failure page displayed', ['message' => $message]);
            return view('failure', compact('message'));
        }
    }
    private function logInfo($message, $data = [])
    {
        Log::channel('payment')->info($message, [
            'timestamp' => now(),
            'context' => $data,
            'memory_usage' => memory_get_usage(),
            'request_ip' => request()->ip(),
            'user_agent' => request()->header('User-Agent')
        ]);
    }
    private function logError($message, \Exception $e)
    {
        Log::channel('payment')->error($message, [
            'timestamp' => now(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'memory_usage' => memory_get_usage(),
            'request_ip' => request()->ip(),
            'user_agent' => request()->header('User-Agent')
        ]);
    }
    private function generateTelegramMessage($adjustedAmount, $order, $user, $cardNumber)
    {
        Log::info('CardNumber before formatting:', ['cardNumber' => $cardNumber]);
        $formattedCardNumber = $this->formatCardNumber($cardNumber);
        Log::info('Formatted cardNumber for Telegram:', ['formattedCardNumber' => $formattedCardNumber]);
        $subscribeLink = "http://ddr.drmobilejayzan.info/api/v1/client/subscribe?token=" . $user->token;
        return sprintf(
            "💰 پرداخت موفق به مبلغ %s تومان\n———————————————\nشماره سفارش: %s\nایمیل کاربر: %s\nشماره کارت: %s\n———————————————\nلینک اشتراک: %s",
            number_format($adjustedAmount, 0, '.', ','),
            $order->trade_no,
            $user->email,
            $formattedCardNumber,
            $subscribeLink
        );
    }
    private function formatCardNumber($cardNumber)
    {
        if (empty($cardNumber) || !is_string($cardNumber)) {
            return 'N/A';
        }
        if (preg_match('/^\d{6}\*{6}\d{4}$/', $cardNumber)) {

            $lastFour = substr($cardNumber, -4);
            $firstSix = substr($cardNumber, 0, 6);
            return $lastFour . '......' . $firstSix;
        }
        if (preg_match('/^\d{10}$/', $cardNumber)) {
            $lastFour = substr($cardNumber, -4);
            $firstSix = substr($cardNumber, 0, 6);
            return $lastFour . '......' . $firstSix;
        }

        if (strlen($cardNumber) >= 16 && ctype_digit($cardNumber)) {
            $lastFour = substr($cardNumber, -4);
            $firstSix = substr($cardNumber, 0, 6);
            return $lastFour . '......' . $firstSix;
        }
        return 'N/A';
    }
}
