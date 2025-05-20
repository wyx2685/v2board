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
        if (!isset($this->config['zibal_merchant'], $this->config['zibal_callback'])) {
            Log::error('Zibal config is missing required keys');
            throw new \Exception('تنظیمات زیبال ناقص است.');
        }

        $params = [
            'merchant' => $this->config['zibal_merchant'],
            'amount' => $order['total_amount'] * 10,
            'callbackUrl' => $this->config['zibal_callback'],
            'orderId' => $order['trade_no'],
            'description' => 'پرداخت سفارش ' . $order['trade_no'],
        ];

        try {
            $response = Http::post('https://gateway.zibal.ir/v1/request', $params);
            $result = $response->json();

            Log::info('Zibal payment request:', $this->filterLogData($params));
            Log::info('Zibal payment response:', $this->filterLogData($result));

            if ($response->successful() && ($result['result'] ?? 0) === 100) {
                return [
                    'type' => 1,
                    'data' => 'https://gateway.zibal.ir/start/' . $result['trackId'],
                ];
            }

            Log::error('Zibal payment failed', $result);
            throw new \Exception($result['message'] ?? 'خطای نامشخص از زیبال');

        } catch (\Exception $e) {
            Log::error('Zibal payment error: ' . $e->getMessage());
            return false;
        }
    }

    public function notify($params)
    {
        Log::info('Zibal notify received:', $this->filterLogData($params));

        $requiredParams = ['trackId', 'orderId', 'success'];
        foreach ($requiredParams as $param) {
            if (!isset($params[$param])) {
                Log::error('Missing required parameter: ' . $param);
                return false;
            }
        }

        if ($params['success'] != 1) {
            Log::error('Transaction failed with status: ' . $params['success']);
            return false;
        }

        $order = Order::where('trade_no', $params['orderId'])->first();
        if (!$order) {
            Log::error('Order not found: ' . $params['orderId']);
            return false;
        }

        try {
            $response = Http::post('https://gateway.zibal.ir/v1/verify', [
                'merchant' => $this->config['zibal_merchant'],
                'trackId' => $params['trackId']
            ]);

            $result = $response->json();
            Log::info('Zibal verify response:', $this->filterLogData($result));

            if (($result['result'] ?? 0) !== 100 || $result['amount'] != ($order->total_amount * 10)) {
                Log::error('Payment verification failed', $result);
                return false;
            }

            Log::info('Raw cardNumber from Zibal:', ['cardNumber' => $result['cardNumber'] ?? 'N/A']);

            $cardNumber = isset($result['cardNumber']) ? $this->maskCardNumber($result['cardNumber']) : 'N/A';

            return [
                'trade_no' => $params['orderId'],
                'callback_no' => $params['trackId'],
                'amount' => $order->total_amount,
                'card_number' => $cardNumber
            ];
        } catch (\Exception $e) {
            Log::error('Zibal verify error: ' . $e->getMessage());
            return false;
        }
    }
    private function filterLogData($data)
    {
        return array_map(function ($item) {
            if (is_array($item)) {
                return $this->filterLogData($item);
            }
            if (is_string($item)) {
                $item = preg_replace('/token=([^&]+)/', 'token=****', $item);
                $item = preg_replace('/"cardNumber":"[^"]+"/', '"cardNumber":"****"', $item);
            }
            return $item;
        }, $data);
    }
    private function maskCardNumber($cardNumber)
    {
        if (empty($cardNumber) || !is_string($cardNumber)) {
            return 'N/A';
        }
        if (preg_match('/^\d{6}\*{6}\d{4}$/', $cardNumber)) {
            return $cardNumber;
        }
        if (preg_match('/^\d{10}$/', $cardNumber)) {
            return substr($cardNumber, 0, 6) . '******' . substr($cardNumber, -4);
        }
        if (strlen($cardNumber) >= 16 && ctype_digit($cardNumber)) {
            return substr($cardNumber, 0, 6) . '******' . substr($cardNumber, -4);
        }
        return 'N/A';
    }
}
