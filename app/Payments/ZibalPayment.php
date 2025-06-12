<?php
namespace App\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\Rule;
use App\Models\Order;
use Exception;
use InvalidArgumentException;

/**
 * کلاس پرداخت زیبال بهینه شده
 * 
 * @version 3.0
 * @author Your Name
 */
class ZibalPayment
{
    private array $config;
    
    // کنستانت‌های درگاه زیبال
    private const ZIBAL_REQUEST_URL = 'https://gateway.zibal.ir/v1/request';
    private const ZIBAL_VERIFY_URL = 'https://gateway.zibal.ir/v1/verify';
    private const ZIBAL_START_URL = 'https://gateway.zibal.ir/start/';
    private const ZIBAL_HEALTH_URL = 'https://gateway.zibal.ir/health';
    
    // کدهای وضعیت زیبال
    private const ZIBAL_SUCCESS_CODE = 100;
    private const ZIBAL_DUPLICATE_CODE = 201;
    
    // تنظیمات HTTP
    private const HTTP_TIMEOUT = 30;
    private const HTTP_RETRY_TIMES = 3;
    private const HTTP_RETRY_DELAY = 1000;
    
    // تنظیمات کش
    private const CACHE_CONFIG_TTL = 3600;  // 1 ساعت
    private const CACHE_ORDER_TTL = 300;    // 5 دقیقه
    private const CACHE_PAYMENT_TTL = 60;   // 1 دقیقه
    
    // تنظیمات Rate Limiting
    private const RATE_LIMIT_MAX_ATTEMPTS = 5;
    private const RATE_LIMIT_WINDOW = 300; // 5 دقیقه
    
    // تنظیمات Circuit Breaker
    private const CIRCUIT_BREAKER_THRESHOLD = 5;
    private const CIRCUIT_BREAKER_TIMEOUT = 300;

    /**
     * سازنده کلاس
     * 
     * @param array $config تنظیمات زیبال
     * @throws ZibalConfigException
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->validateConfig();
    }

    /**
     * اعتبارسنجی تنظیمات اولیه
     * 
     * @throws ZibalConfigException
     */
    private function validateConfig(): void
    {
        $required = ['zibal_merchant', 'zibal_callback'];
        $missing = array_diff($required, array_keys($this->config));
        
        if (!empty($missing)) {
            $missingKeys = implode(', ', $missing);
            Log::error('Zibal config validation failed', [
                'missing_keys' => $missing,
                'provided_keys' => array_keys($this->config)
            ]);
            throw new ZibalConfigException("کلیدهای لازم در تنظیمات زیبال موجود نیست: {$missingKeys}");
        }
        
        // اعتبارسنجی کد مرچنت
        if (!$this->isValidMerchantId($this->config['zibal_merchant'])) {
            Log::error('Invalid merchant ID', [
                'merchant_id' => $this->maskSensitiveValue($this->config['zibal_merchant'])
            ]);
            throw new ZibalConfigException('کد مرچنت زیبال معتبر نیست');
        }
        
        // اعتبارسنجی URL بازگشت
        if (!filter_var($this->config['zibal_callback'], FILTER_VALIDATE_URL)) {
            Log::error('Invalid callback URL', [
                'callback_url' => $this->config['zibal_callback']
            ]);
            throw new ZibalConfigException('آدرس بازگشت معتبر نیست');
        }

        Log::info('Zibal config validation successful');
    }

    /**
     * بررسی معتبر بودن کد مرچنت
     */
    private function isValidMerchantId(string $merchantId): bool
    {
        return preg_match('/^[a-zA-Z0-9\-]{10,50}$/', $merchantId);
    }

    /**
     * دریافت فرم تنظیمات
     * 
     * @return array
     */
    public function form(): array
    {
        return [
            'zibal_merchant' => [
                'label' => 'کد مرچنت زیبال',
                'description' => 'کد مرچنت دریافتی از زیبال (10-50 کاراکتر)',
                'type' => 'input',
                'validation' => 'required|string|min:10|max:50|regex:/^[a-zA-Z0-9\-]+$/',
            ],
            'zibal_callback' => [
                'label' => 'آدرس بازگشت',
                'description' => 'آدرس کامل صفحه بازگشت از درگاه پرداخت',
                'type' => 'input',
                'validation' => 'required|url|max:255',
            ],
            'zibal_sandbox' => [
                'label' => 'حالت تست',
                'description' => 'فعال‌سازی حالت تست (Sandbox)',
                'type' => 'checkbox',
                'validation' => 'boolean',
            ],
        ];
    }

    /**
     * پردازش پرداخت
     * 
     * @param array $order اطلاعات سفارش
     * @return array|false
     * @throws ZibalPaymentException
     */
    public function pay(array $order): array|false
    {
        $startTime = microtime(true);
        
        try {
            // بررسی Rate Limiting
            $this->checkRateLimit($order['trade_no'] ?? 'unknown');
            
            // بررسی Circuit Breaker
            $this->checkCircuitBreaker();
            
            // اعتبارسنجی و آماده‌سازی درخواست
            $paymentRequest = $this->preparePaymentRequest($order);
            
            // ارسال درخواست با Circuit Breaker
            $result = $this->callWithCircuitBreaker(function () use ($paymentRequest) {
                return $this->sendPaymentRequest($paymentRequest);
            });
            
            // پردازش پاسخ
            $paymentResponse = $this->processPaymentResponse($result, $paymentRequest);
            
            // ثبت موفقیت
            $this->recordSuccess($paymentRequest, $result, microtime(true) - $startTime);
            
            return $paymentResponse;
            
        } catch (Exception $e) {
            $this->recordFailure($e, $order, microtime(true) - $startTime);
            
            if ($e instanceof ZibalException) {
                throw $e;
            }
            
            return false;
        }
    }

    /**
     * آماده‌سازی درخواست پرداخت
     * 
     * @param array $order
     * @return array
     * @throws ZibalPaymentException
     */
    private function preparePaymentRequest(array $order): array
    {
        // اعتبارسنجی سفارش
        $this->validateOrder($order);
        
        return [
            'merchant' => $this->config['zibal_merchant'],
            'amount' => (int)($order['total_amount'] * 10), // تبدیل به ریال
            'callbackUrl' => $this->config['zibal_callback'],
            'orderId' => $order['trade_no'],
            'description' => $order['trade_no'],
            'mobile' => $order['mobile'] ?? null,
            'allowedCards' => $order['allowed_cards'] ?? null,
        ];
    }

    /**
     * اعتبارسنجی داده‌های سفارش
     * 
     * @param array $order
     * @throws ZibalPaymentException
     */
    private function validateOrder(array $order): void
    {
        $validator = Validator::make($order, [
            'trade_no' => 'required|string|max:100',
            'total_amount' => 'required|numeric|min:1000|max:500000000', // 1000 ریال تا 500 میلیون ریال
            'mobile' => 'nullable|string|regex:/^09[0-9]{9}$/',
            'allowed_cards' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            throw new ZibalPaymentException('داده‌های سفارش نامعتبر: ' . $validator->errors()->first());
        }
    }

    /**
     * ارسال درخواست پرداخت به زیبال
     * 
     * @param array $request
     * @return array
     * @throws ZibalPaymentException
     */
    private function sendPaymentRequest(array $request): array
    {
        $cacheKey = "zibal_payment_" . md5(serialize($request));
        
        // بررسی کش
        if (Cache::tags(['zibal', 'payments'])->has($cacheKey)) {
            return Cache::tags(['zibal', 'payments'])->get($cacheKey);
        }

        $client = $this->getHttpClient();
        $response = $client->post(self::ZIBAL_REQUEST_URL, array_filter($request));

        if (!$response->successful()) {
            throw new ZibalPaymentException(
                "خطای HTTP: {$response->status()} - " . $this->sanitizeErrorMessage($response->body())
            );
        }

        $result = $response->json();
        
        if (!is_array($result)) {
            throw new ZibalPaymentException('پاسخ نامعتبر از سرور زیبال');
        }

        // ذخیره در کش در صورت موفقیت
        if (($result['result'] ?? 0) === self::ZIBAL_SUCCESS_CODE) {
            Cache::tags(['zibal', 'payments'])->put($cacheKey, $result, self::CACHE_PAYMENT_TTL);
        }

        return $result;
    }

    /**
     * پردازش پاسخ درخواست پرداخت
     * 
     * @param array $result
     * @param array $request
     * @return array
     * @throws ZibalPaymentException
     */
    private function processPaymentResponse(array $result, array $request): array
    {
        $resultCode = $result['result'] ?? 0;
        
        if ($resultCode === self::ZIBAL_SUCCESS_CODE) {
            if (empty($result['trackId'])) {
                throw new ZibalPaymentException('trackId در پاسخ زیبال موجود نیست');
            }
            
            return [
                'type' => 1, // redirect
                'data' => self::ZIBAL_START_URL . $result['trackId'],
                'track_id' => $result['trackId'],
            ];
        }
        
        $errorMessage = $this->getZibalErrorMessage($resultCode, $result['message'] ?? null);
        throw new ZibalPaymentException($errorMessage, $resultCode);
    }

    /**
     * پردازش Callback از زیبال
     * 
     * @param array $params
     * @return array|false
     */
    public function notify(array $params): array|false
    {
        try {
            // اعتبارسنجی پارامترها
            $this->validateNotifyParams($params);
            
            // بررسی وضعیت تراکنش
            if ((int)$params['success'] !== 1) {
                Log::warning('Transaction failed', [
                    'order_id' => $params['orderId'],
                    'track_id' => $params['trackId'],
                    'success_status' => $params['success']
                ]);
                return false;
            }

            // یافتن سفارش
            $order = $this->findOrder($params['orderId']);
            if (!$order) {
                return false;
            }

            // تایید تراکنش
            $verificationResult = $this->verifyTransaction($params, $order);
            
            if ($verificationResult) {
                $this->recordVerificationSuccess($params, $order);
            }
            
            return $verificationResult;

        } catch (Exception $e) {
            $this->logNotifyError($e, $params);
            return false;
        }
    }

    /**
     * اعتبارسنجی پارامترهای notify
     * 
     * @param array $params
     * @throws ZibalPaymentException
     */
    private function validateNotifyParams(array $params): void
    {
        $validator = Validator::make($params, [
            'trackId' => 'required|string|max:50',
            'orderId' => 'required|string|max:100',
            'success' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            throw new ZibalPaymentException('پارامترهای notify نامعتبر: ' . $validator->errors()->first());
        }
    }

    /**
     * یافتن سفارش
     * 
     * @param string $orderId
     * @return Order|null
     */
    private function findOrder(string $orderId): ?Order
    {
        $cacheKey = "order_" . md5($orderId);
        
        $order = Cache::tags(['orders'])->remember($cacheKey, self::CACHE_ORDER_TTL, function () use ($orderId) {
            return Order::where('trade_no', $orderId)->first();
        });

        if (!$order) {
            Log::error('Order not found in notify', [
                'order_id' => $orderId,
                'context' => 'zibal_notify'
            ]);
        }

        return $order;
    }

    /**
     * تایید تراکنش با زیبال
     * 
     * @param array $params
     * @param Order $order
     * @return array|false
     */
    private function verifyTransaction(array $params, Order $order): array|false
    {
        try {
            $verifyParams = [
                'merchant' => $this->config['zibal_merchant'],
                'trackId' => $params['trackId']
            ];

            $result = $this->callWithCircuitBreaker(function () use ($verifyParams) {
                return $this->sendVerifyRequest($verifyParams);
            });

            return $this->processVerifyResponse($result, $params, $order);

        } catch (Exception $e) {
            Log::error('Transaction verification failed', [
                'error' => $e->getMessage(),
                'track_id' => $params['trackId'],
                'order_id' => $params['orderId']
            ]);
            return false;
        }
    }

    /**
     * ارسال درخواست تایید
     * 
     * @param array $request
     * @return array
     */
    private function sendVerifyRequest(array $request): array
    {
        $client = $this->getHttpClient();
        $response = $client->post(self::ZIBAL_VERIFY_URL, $request);

        if (!$response->successful()) {
            throw new ZibalVerificationException(
                "HTTP Error در تایید: {$response->status()} - " . $this->sanitizeErrorMessage($response->body())
            );
        }

        $result = $response->json();
        
        if (!is_array($result)) {
            throw new ZibalVerificationException('پاسخ نامعتبر در تایید تراکنش');
        }

        return $result;
    }

    /**
     * پردازش پاسخ تایید
     * 
     * @param array $result
     * @param array $params
     * @param Order $order
     * @return array|false
     */
    private function processVerifyResponse(array $result, array $params, Order $order): array|false
    {
        // بررسی نتیجه تایید
        if (($result['result'] ?? 0) !== self::ZIBAL_SUCCESS_CODE) {
            Log::error('Payment verification failed', [
                'result_code' => $result['result'] ?? 0,
                'message' => $result['message'] ?? 'Unknown error',
                'track_id' => $params['trackId'],
                'order_id' => $params['orderId']
            ]);
            return false;
        }

        // بررسی مبلغ
        $expectedAmount = (int)($order->total_amount * 10);
        $receivedAmount = (int)($result['amount'] ?? 0);
        
        if ($receivedAmount !== $expectedAmount) {
            Log::error('Amount mismatch in verification', [
                'expected' => $expectedAmount,
                'received' => $receivedAmount,
                'track_id' => $params['trackId'],
                'order_id' => $params['orderId']
            ]);
            return false;
        }

        return [
            'trade_no' => $params['orderId'],
            'callback_no' => $params['trackId'],
            'amount' => $order->total_amount,
            'card_number' => $this->extractCardNumber($result),
            'ref_number' => $result['refNumber'] ?? null,
            'wage' => $result['wage'] ?? null,
        ];
    }

    /**
     * بررسی سلامت سرویس زیبال
     * 
     * @return array
     */
    public function healthCheck(): array
    {
        try {
            $startTime = microtime(true);
            $response = Http::timeout(5)->get(self::ZIBAL_HEALTH_URL);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($response->successful()) {
                return [
                    'status' => 'healthy',
                    'response_time_ms' => $responseTime,
                    'gateway' => 'zibal'
                ];
            }
            
            return [
                'status' => 'unhealthy',
                'error' => "HTTP {$response->status()}",
                'response_time_ms' => $responseTime,
                'gateway' => 'zibal'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'gateway' => 'zibal'
            ];
        }
    }

    /**
     * دریافت HTTP Client بهینه
     * 
     * @return \Illuminate\Http\Client\PendingRequest
     */
    private function getHttpClient()
    {
        return Http::timeout(self::HTTP_TIMEOUT)
            ->retry(self::HTTP_RETRY_TIMES, self::HTTP_RETRY_DELAY)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'Laravel-ZibalPayment/3.0',
            ]);
    }

    /**
     * بررسی Rate Limiting
     * 
     * @param string $identifier
     * @throws ZibalPaymentException
     */
    private function checkRateLimit(string $identifier): void
    {
        $key = "zibal_rate_limit_" . md5($identifier);
        $attempts = Cache::get($key, 0);
        
        if ($attempts >= self::RATE_LIMIT_MAX_ATTEMPTS) {
            throw new ZibalPaymentException('تعداد درخواست‌ها بیش از حد مجاز است. لطفاً کمی صبر کنید.');
        }
        
        Cache::put($key, $attempts + 1, self::RATE_LIMIT_WINDOW);
    }

    /**
     * بررسی Circuit Breaker
     * 
     * @throws ZibalPaymentException
     */
    private function checkCircuitBreaker(): void
    {
        $failureKey = 'zibal_circuit_breaker_failures';
        $timeoutKey = 'zibal_circuit_breaker_timeout';
        
        if (Cache::has($timeoutKey)) {
            throw new ZibalPaymentException('سرویس زیبال موقتاً در دسترس نیست. لطفاً بعداً تلاش کنید.');
        }
        
        $failures = Cache::get($failureKey, 0);
        if ($failures >= self::CIRCUIT_BREAKER_THRESHOLD) {
            Cache::put($timeoutKey, true, self::CIRCUIT_BREAKER_TIMEOUT);
            throw new ZibalPaymentException('سرویس زیبال به دلیل خطاهای متعدد غیرفعال شده است.');
        }
    }

    /**
     * اجرای عملیات با Circuit Breaker
     * 
     * @param callable $callback
     * @return mixed
     */
    private function callWithCircuitBreaker(callable $callback)
    {
        $failureKey = 'zibal_circuit_breaker_failures';
        
        try {
            $result = $callback();
            
            // Reset failures on success
            Cache::forget($failureKey);
            
            return $result;
            
        } catch (Exception $e) {
            // Increment failure count
            $failures = Cache::get($failureKey, 0) + 1;
            Cache::put($failureKey, $failures, self::CIRCUIT_BREAKER_TIMEOUT);
            
            throw $e;
        }
    }

    /**
     * تبدیل کد خطای زیبال به پیام فارسی
     * 
     * @param int $code
     * @param string|null $message
     * @return string
     */
    private function getZibalErrorMessage(int $code, ?string $message = null): string
    {
        $errorMessages = [
            102 => 'کد مرچنت ارسالی معتبر نیست',
            103 => 'کد مرچنت فعال نیست',
            104 => 'کد مرچنت نامعتبر است',
            105 => 'مبلغ بایستی بزرگتر از 1000 ریال باشد',
            106 => 'آدرس بازگشت نامعتبر است',
            113 => 'مبلغ تراکنش از حد مجاز بیشتر است',
            201 => 'قبلاً تراکنش تایید شده است',
            202 => 'سفارش پرداخت نشده یا ناموفق بوده است',
            203 => 'trackId نامعتبر است',
        ];

        return $errorMessages[$code] ?? ($message ?: "خطای نامشخص زیبال (کد: {$code})");
    }

    /**
     * استخراج شماره کارت
     * 
     * @param array $result
     * @return string
     */
    private function extractCardNumber(array $result): string
    {
        if (empty($result['cardNumber'])) {
            return 'N/A';
        }

        return $this->maskCardNumber($result['cardNumber']);
    }

    /**
     * ماسک کردن شماره کارت
     * 
     * @param mixed $cardNumber
     * @return string
     */
    private function maskCardNumber($cardNumber): string
    {
        if (empty($cardNumber) || !is_string($cardNumber)) {
            return 'N/A';
        }

        // اگر قبلاً ماسک شده است
        if (preg_match('/^\d{6}\*+\d{4}$/', $cardNumber)) {
            return $cardNumber;
        }

        // حذف کاراکترهای غیر عددی
        $cleanCard = preg_replace('/\D/', '', $cardNumber);
        
        if (strlen($cleanCard) < 16 || strlen($cleanCard) > 19) {
            return 'N/A';
        }

        return substr($cleanCard, 0, 6) . str_repeat('*', strlen($cleanCard) - 10) . substr($cleanCard, -4);
    }

    /**
     * ماسک کردن مقادیر حساس
     */
    private function maskSensitiveValue(string $value): string
    {
        $length = strlen($value);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }
        
        return substr($value, 0, 2) . str_repeat('*', $length - 4) . substr($value, -2);
    }

    /**
     * پاک‌سازی پیام‌های خطا برای لاگ
     * 
     * @param string $message
     * @return string
     */
    private function sanitizeErrorMessage(string $message): string
    {
        // حذف اطلاعات حساس احتمالی
        $patterns = [
            '/merchant["\']?\s*[:=]\s*["\']?[^"\'}\s,]+/i',
            '/token["\']?\s*[:=]\s*["\']?[^"\'}\s,]+/i',
            '/password["\']?\s*[:=]\s*["\']?[^"\'}\s,]+/i',
        ];
        
        return preg_replace($patterns, '[FILTERED]', substr($message, 0, 500));
    }

    /**
     * ثبت موفقیت عملیات
     */
    private function recordSuccess(array $request, array $result, float $duration): void
    {
        $this->recordMetric('zibal.payment.success', [
            'order_id' => $request['orderId'],
            'amount' => $request['amount'],
            'duration_ms' => round($duration * 1000, 2),
        ]);

        Log::info('Zibal payment successful', [
            'order_id' => $request['orderId'],
            'track_id' => $result['trackId'] ?? null,
            'amount' => $request['amount'],
            'duration_ms' => round($duration * 1000, 2),
        ]);
    }

    /**
     * ثبت شکست عملیات
     */
    private function recordFailure(Exception $e, array $order, float $duration): void
    {
        $this->recordMetric('zibal.payment.failure', [
            'order_id' => $order['trade_no'] ?? 'unknown',
            'error' => $e->getMessage(),
            'duration_ms' => round($duration * 1000, 2),
        ]);

        Log::error('Zibal payment failed', [
            'error' => $e->getMessage(),
            'order_id' => $order['trade_no'] ?? 'unknown',
            'amount' => $order['total_amount'] ?? 0,
            'duration_ms' => round($duration * 1000, 2),
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
        ]);
    }

    /**
     * ثبت متریک
     */
    private function recordMetric(string $event, array $data): void
    {
        try {
            Event::dispatch('zibal.metric', [$event, $data]);
        } catch (Exception $e) {
            Log::debug('Failed to record metric', ['event' => $event, 'error' => $e->getMessage()]);
        }
    }

    /**
     * ثبت موفقیت تایید
     */
    private function recordVerificationSuccess(array $params, Order $order): void
    {
        $this->recordMetric('zibal.verification.success', [
            'order_id' => $params['orderId'],
            'track_id' => $params['trackId'],
            'amount' => $order->total_amount,
        ]);
    }

    /**
     * لاگ خطای notify
     */
    private function logNotifyError(Exception $e, array $params): void
    {
        Log::error('Zibal notify failed', [
            'error' => $e->getMessage(),
            'params' => array_intersect_key($params, array_flip(['trackId', 'orderId', 'success'])),
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
        ]);
    }
}

// کلاس‌های Exception

/**
 * کلاس اصلی Exception زیبال
 */
class ZibalException extends Exception
{
    protected string $gatewayCode;

    public function __construct(string $message = "", int $gatewayCode = 0, ?Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->gatewayCode = (string)$gatewayCode;
    }

    public function getGatewayCode(): string
    {
        return $this->gatewayCode;
    }
}

/**
 * Exception تنظیمات زیبال
 */
class ZibalConfigException extends ZibalException
{
    //
}

/**
 * Exception پرداخت زیبال
 */
class ZibalPaymentException extends ZibalException
{
    //
}

/**
 * Exception تایید زیبال
 */
class ZibalVerificationException extends ZibalException
{
    //
}
