<?php
namespace App\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Order;

class ZibalPayment
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function form()
    {
        return [
            'zibal_merchant' => [
                'label' => 'کد مرچنت زیبال',
                'description' => '',
                'type' => 'input',
            ],
            'zibal_callback' => [
                'label' => 'آدرس بازگشت',
                'description' => '',
                'type' => 'input',
            ],
        ];
    }

    public function pay($order)
    {
        $params = [
            'merchant' => $this->config['zibal_merchant'],
            'amount' => $order['total_amount'] * 10, // تبدیل تومان به ریال
            'callbackUrl' => 'https://drmobjay.com/zibal_transit.php', // آدرس ترانزیت
            'orderId' => $order['trade_no'],
            'description' => 'پرداخت سفارش ' . $order['trade_no'],
        ];

        try {
            $response = Http::post('https://gateway.zibal.ir/v1/request', $params);
            $result = $response->json();
            
            Log::info('Zibal payment request:', $params);
            Log::info('Zibal payment response:', $result);
            
            if ($response->successful() && isset($result['result']) && $result['result'] === 100) {
                // تراکنش با موفقیت ایجاد شده است
                return [
                    'type' => 1, // 1: URL
                    'data' => 'https://gateway.zibal.ir/start/' . $result['trackId'],
                ];
            } else {
                Log::error('Zibal payment error: ', $result);
                throw new \Exception('Error creating transaction: ' . ($result['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error('Zibal payment error: ' . $e->getMessage());
            return false; // بازگردانی false در صورت خطا
        }
    }

    public function notify($params)
    {
        Log::info('Zibal notify received:', $params);

        $requiredParams = ['trackId', 'orderId', 'success'];
        foreach ($requiredParams as $param) {
            if (!isset($params[$param])) {
                Log::error('Zibal notify: Missing required parameter', ['missing' => $param]);
                return false;
            }
        }

        if ($params['success'] != 1) {
            Log::error('Zibal notify: Transaction was not successful', ['success' => $params['success']]);
            return false;
        }

        $order = Order::where('trade_no', $params['orderId'])->first();
        if (!$order) {
            Log::error('Zibal notify: Order not found', ['orderId' => $params['orderId']]);
            return false;
        }

        $verifyParams = [
            'merchant' => $this->config['zibal_merchant'],
            'trackId' => $params['trackId']
        ];

        try {
            $response = Http::post('https://gateway.zibal.ir/v1/verify', $verifyParams);
            $result = $response->json();
            
            Log::info('Zibal verify response:', $result);
            
            if ($response->successful() && isset($result['result']) && $result['result'] === 100) {
                return [
                    'trade_no' => $params['orderId'],
                    'callback_no' => $params['trackId'],
                    'amount' => $order->total_amount
                ];
            } else {
                Log::error('Zibal verify failed: ', $result);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Zibal verify error: ' . $e->getMessage());
            return false;
        }
    }
}
