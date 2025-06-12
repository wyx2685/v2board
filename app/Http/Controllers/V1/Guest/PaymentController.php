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
        $requestData = $request->all();

        try {
            // بررسی اولیه وضعیت تراکنش قبل از verification
            $success = $requestData['success'] ?? null;
            $status = $requestData['status'] ?? null;

            // اگر success=0 یعنی پرداخت کنسل/ناموفق است
            if ($success === '0' || $success === 0) {
                // لغو خودکار سفارش
                $this->cancelOrder($requestData['orderId'] ?? null, 'user_cancelled');
                
                // فقط لاگ کنسل شدن - بدون جزئیات اضافی
                $this->logInfo('Transaction cancelled by user', [
                    'orderId' => $requestData['orderId'] ?? 'N/A',
                    'trackId' => $requestData['trackId'] ?? 'N/A'
                ]);
                
                return $this->renderPaymentResult(false, 'پرداخت توسط کاربر لغو شد یا ناموفق بود.', $requestData['orderId'] ?? null);
            }

            $paymentService = new PaymentService($method, null, $uuid);
            $verificationResult = $paymentService->notify($requestData);

            if ($verificationResult === false) {
                // لغو خودکار سفارش در صورت عدم تایید
                $this->cancelOrder($requestData['orderId'] ?? null, 'verification_failed');
                throw new \Exception('Transaction verification failed');
            }
            
            $cardNumber = $verificationResult['card_number'] ?? 'N/A';

            if (!$this->handleOrder($verificationResult['trade_no'], $verificationResult['callback_no'], $cardNumber)) {
                // لغو خودکار سفارش در صورت خطا در پردازش
                $this->cancelOrder($verificationResult['trade_no'], 'processing_failed');
                throw new \Exception('Order processing failed');
            }

            return $this->renderPaymentResult(true, 'پرداخت با موفقیت انجام شد.', $verificationResult['trade_no']);
            
        } catch (\Exception $e) {
            // لغو خودکار سفارش برای سایر خطاها
            $this->cancelOrder($requestData['orderId'] ?? null, 'system_error');
            
            $this->logError('Payment notification error', $e);
            return $this->renderPaymentResult(false, 'خطا در پردازش پرداخت.');
        }
    }

    /**
     * لغو خودکار سفارش
     */
    private function cancelOrder($tradeNo, $reason = 'unknown')
    {
        if (empty($tradeNo)) {
            return false;
        }

        try {
            // یافتن سفارش
            $order = Cache::remember("order_{$tradeNo}", 30, function() use ($tradeNo) {
                return Order::select('id', 'trade_no', 'status', 'user_id', 'total_amount')
                           ->where('trade_no', $tradeNo)
                           ->first();
            });

            if (!$order) {
                $this->logError('Order not found for cancellation', new \Exception("Order not found: {$tradeNo}"));
                return false;
            }

            // فقط سفارشات در انتظار پرداخت را لغو کن
            if ($order->status !== 0) {
                return true; // سفارش قبلاً پردازش شده
            }

            // لغو سفارش با استفاده از OrderService
            $orderService = new OrderService($order);
            
            // تلاش برای لغو با retry
            for ($attempt = 1; $attempt <= 2; $attempt++) {
                try {
                    if ($orderService->cancel()) {
                        // پاک کردن کش‌های مرتبط
                        Cache::forget("order_{$tradeNo}");
                        Cache::forget("order_info_{$tradeNo}");
                        
                        $this->logInfo('Order cancelled automatically', [
                            'trade_no' => $tradeNo,
                            'reason' => $reason,
                            'user_id' => $order->user_id
                        ]);
                        
                        // اعلان تلگرام در صورت نیاز
                        $this->sendCancellationNotification($order, $reason);
                        
                        return true;
                    }
                    
                    if ($attempt < 2) {
                        usleep(200000); // 0.2 ثانیه انتظار
                        continue;
                    }
                    
                } catch (\Exception $e) {
                    if ($attempt < 2) {
                        usleep(200000);
                        continue;
                    }
                    
                    $this->logError('Order cancellation failed', $e);
                    return false;
                }
            }

            // اگر OrderService->cancel() موجود نیست، به صورت مستقیم لغو کن
            return $this->directCancelOrder($order, $reason);
            
        } catch (\Exception $e) {
            $this->logError('Order cancellation exception', $e);
            return false;
        }
    }

    /**
     * لغو مستقیم سفارش (fallback)
     */
    private function directCancelOrder($order, $reason)
    {
        try {
            $updated = Order::where('id', $order->id)
                          ->where('status', 0)
                          ->update([
                              'status' => 2, // وضعیت لغو شده
                              'updated_at' => now(),
                              'cancelled_at' => now()
                          ]);

            if ($updated) {
                // پاک کردن کش‌ها
                Cache::forget("order_{$order->trade_no}");
                Cache::forget("order_info_{$order->trade_no}");
                
                $this->logInfo('Order cancelled directly', [
                    'trade_no' => $order->trade_no,
                    'reason' => $reason,
                    'user_id' => $order->user_id
                ]);
                
                // اعلان تلگرام
                $this->sendCancellationNotification($order, $reason);
                
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            $this->logError('Direct order cancellation failed', $e);
            return false;
        }
    }

    /**
     * ارسال اعلان لغو سفارش
     */
    private function sendCancellationNotification($order, $reason)
    {
        try {
            // فقط برای مبالغ بالا یا دلایل مهم اعلان بفرست
            if ($order->total_amount >= 50000 || in_array($reason, ['system_error', 'processing_failed'])) {
                
                $user = Cache::remember("user_{$order->user_id}", 120, function() use ($order) {
                    return User::select('id', 'email')->find($order->user_id);
                });

                if ($user) {
                    $reasonTexts = [
                        'user_cancelled' => 'لغو توسط کاربر',
                        'verification_failed' => 'عدم تایید پرداخت',
                        'processing_failed' => 'خطا در پردازش',
                        'system_error' => 'خطای سیستم',
                        'timeout' => 'انقضای زمان',
                        'unknown' => 'دلیل نامشخص'
                    ];

                    $reasonText = $reasonTexts[$reason] ?? 'دلیل نامشخص';
                    
                    $message = sprintf(
                        "❌ لغو سفارش\n———————————————\nشماره سفارش: %s\nایمیل کاربر: %s\nمبلغ: %s تومان\nدلیل: %s\n———————————————\nزمان: %s",
                        $order->trade_no,
                        $user->email,
                        number_format($order->total_amount, 0, '.', ','),
                        $reasonText,
                        now()->format('Y-m-d H:i:s')
                    );

                    $telegramService = new TelegramService();
                    $telegramService->sendMessageWithAdmin($message);
                }
            }
            
        } catch (\Exception $e) {
            // خطای تلگرام نباید مانع لغو سفارش شود
            $this->logError('Cancellation notification failed', $e);
        }
    }

    private function handleOrder($tradeNo, $transactionId, $cardNumber = 'N/A')
    {
        // بهینه‌سازی کش با TTL کوتاه‌تر برای سرعت
        $order = Cache::remember("order_{$tradeNo}", 30, function() use ($tradeNo) {
            return Order::select('id', 'trade_no', 'status', 'total_amount', 'balance_amount', 'user_id')
                       ->where('trade_no', $tradeNo)
                       ->first();
        });

        if (!$order) {
            $this->logError('Order not found', new \Exception("Order not found: {$tradeNo}"));
            return false;
        }

        if ($order->status !== 0) {
            return true; // سفارش قبلاً پردازش شده - بدون لاگ
        }

        if ($order->total_amount == 0 && $order->balance_amount > 0) {
            // بهینه‌سازی update برای سرعت
            $updated = Order::where('id', $order->id)
                          ->where('status', 0)
                          ->update([
                              'status' => 3,
                              'paid_at' => now(),
                              'updated_at' => now()
                          ]);

            if ($updated) {
                Cache::forget("order_{$tradeNo}");
                return true;
            }
            $this->logError('Balance payment update failed', new \Exception("Failed to update order: {$tradeNo}"));
            return false;
        }

        // بهینه‌سازی بازیابی کاربر با کش
        $user = Cache::remember("user_{$order->user_id}", 120, function() use ($order) {
            return User::select('id', 'email', 'token')->find($order->user_id);
        });

        if (!$user) {
            $this->logError('User not found', new \Exception("User not found: {$order->user_id}"));
            return false;
        }

        $orderService = new OrderService($order);

        // بهینه‌سازی retry برای سرعت
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                if ($orderService->paid($transactionId)) {
                    Cache::forget("order_{$tradeNo}");
                    break;
                }
                
                if ($attempt < 2) {
                    usleep(200000); // 0.2 ثانیه
                    continue;
                }
                
                $this->logError('Order status update failed', new \Exception("Failed to update order status: {$tradeNo}"));
                return false;
                
            } catch (\Exception $e) {
                if ($attempt < 2) {
                    usleep(200000);
                    continue;
                }
                
                $this->logError('Order update exception', $e);
                return false;
            }
        }

        $adjustedAmount = $order->total_amount;
        $message = $this->generateTelegramMessage($adjustedAmount, $order, $user, $cardNumber);

        // بهینه‌سازی ارسال تلگرام - عدم انتظار برای پاسخ
        try {
            $telegramService = new TelegramService();
            $telegramService->sendMessageWithAdmin($message);
        } catch (\Exception $e) {
            $this->logError('Telegram send failed', $e);
        }

        return true;
    }

    private function renderPaymentResult($success, $message, $tradeNo = null)
    {
        $orderInfo = '';
        
        if ($success && $tradeNo) {
            // بهینه‌سازی بازیابی اطلاعات سفارش
            $order = Cache::remember("order_info_{$tradeNo}", 30, function() use ($tradeNo) {
                return Order::select('trade_no', 'total_amount', 'balance_amount')
                          ->where('trade_no', $tradeNo)
                          ->first();
            });
            
            if ($order) {
                $adjustedAmount = ($order->total_amount > 0) ? $order->total_amount : $order->balance_amount;
                $orderInfo = "<p>شماره سفارش: {$order->trade_no}</p>" .
                             "<p>مبلغ پرداخت شده: " . number_format($adjustedAmount, 0, '.', ',') . " تومان</p>";
            }
        }

        // اضافه کردن اطلاعات بیشتر برای پرداخت‌های کنسل شده
        if (!$success && $tradeNo) {
            $order = Cache::remember("order_info_{$tradeNo}", 30, function() use ($tradeNo) {
                return Order::select('trade_no', 'total_amount', 'balance_amount', 'status')
                          ->where('trade_no', $tradeNo)
                          ->first();
            });
            
            if ($order) {
                $adjustedAmount = ($order->total_amount > 0) ? $order->total_amount : $order->balance_amount;
                $orderInfo = "<p>شماره سفارش: {$order->trade_no}</p>" .
                             "<p>مبلغ سفارش: " . number_format($adjustedAmount, 0, '.', ',') . " تومان</p>" .
                             "<p>وضعیت سفارش: " . $this->getOrderStatusText($order->status) . "</p>";
            }
        }

        if ($success) {
            return view('success', compact('orderInfo'));
        } else {
            return view('failure', compact('message', 'orderInfo'));
        }
    }

    private function logInfo($message, $data = [])
    {
        // فقط لاگ‌های مهم (کنسل، موفقیت کلی)
        Log::channel('payment')->info($message, [
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'context' => $data,
            'ip' => request()->ip()
        ]);
    }

    private function logError($message, $data)
    {
        if ($data instanceof \Exception) {
            Log::channel('payment')->error($message, [
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'error' => $data->getMessage(),
                'file' => basename($data->getFile()),
                'line' => $data->getLine(),
                'ip' => request()->ip(),
                'trade_no' => $this->extractTradeNoFromTrace($data->getTraceAsString())
            ]);
        } else {
            Log::channel('payment')->error($message, [
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'context' => $data,
                'ip' => request()->ip()
            ]);
        }
    }

    private function generateTelegramMessage($adjustedAmount, $order, $user, $cardNumber)
    {
        $formattedCardNumber = $this->formatCardNumber($cardNumber);
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

    /**
     * متن وضعیت سفارش
     */
    private function getOrderStatusText($status)
    {
        $statusTexts = [
            0 => 'در انتظار پرداخت',
            1 => 'در حال پردازش', 
            2 => 'لغو شده',
            3 => 'پرداخت شده',
            4 => 'تکمیل شده'
        ];
        
        return $statusTexts[$status] ?? 'نامشخص';
    }

    /**
     * استخراج trade_no از stack trace برای لاگ بهتر
     */
    private function extractTradeNoFromTrace($trace)
    {
        if (preg_match('/(\d{19,25})/', $trace, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
