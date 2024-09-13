<?php
namespace App\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Order;

class Aghayehpardakht
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function form()
    {
        return [
            'aghayeh_pin' => [
                'label' => 'کد پین درگاه',
                'description' => '',
                'type' => 'input',
            ],
            'aghayeh_callback' => [
                'label' => 'آدرس بازگشت',
                'description' => '',
                'type' => 'input',
            ],
        ];
    }

    public function pay($order)
    {
        $params = [
            'pin' => $this->config['aghayeh_pin'],
            'amount' => $order['total_amount'],
            'callback' => $order['notify_url'],
            'invoice_id' => $order['trade_no'],
            'description' => 'پرداخت سفارش ' . $order['trade_no'],
        ];

        try {
            $response = Http::post('https://panel.aqayepardakht.ir/api/v2/create', $params);
            $result = $response->json();
            
            Log::info('Aghayehpardakht payment request:', $params);
            Log::info('Aghayehpardakht payment response:', $result);
            
            if ($response->successful() && isset($result['status']) && $result['status'] === 'success') {
                return [
                    'type' => 1, // 1: URL
                    'data' => 'https://panel.aqayepardakht.ir/startpay/' . $result['transid'],
                ];
            } else {
                Log::error('Aghayehpardakht payment error: ', $result);
                throw new \Exception('Error creating transaction: ' . ($result['code'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error('Aghayehpardakht payment error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function notify($params)
    {
        Log::info('Aghayehpardakht notify received:', $params);

        $requiredParams = ['transid', 'invoice_id', 'status'];
        foreach ($requiredParams as $param) {
            if (!isset($params[$param])) {
                Log::error('Aghayehpardakht notify: Missing required parameter', ['missing' => $param]);
                return false;
            }
        }

        if ($params['status'] !== '1') {
            Log::error('Aghayehpardakht notify: Transaction was not successful', ['status' => $params['status']]);
            return false;
        }

        $order = Order::where('trade_no', $params['invoice_id'])->first();
        if (!$order) {
            Log::error('Aghayehpardakht notify: Order not found', ['invoice_id' => $params['invoice_id']]);
            return false;
        }

        $verifyParams = [
            'pin' => $this->config['aghayeh_pin'],
            'amount' => $order->total_amount,
            'transid' => $params['transid']
        ];

        try {
            $response = Http::post('https://panel.aqayepardakht.ir/api/v2/verify', $verifyParams);
            $result = $response->json();
            
            Log::info('Aghayehpardakht verify response:', $result);
            
            if ($response->successful() && isset($result['status']) && $result['status'] === 'success' && $result['code'] === '1') {
                return [
                    'trade_no' => $params['invoice_id'],
                    'callback_no' => $params['transid'],
                    'amount' => $order->total_amount
                ];
            } else {
                Log::error('Aghayehpardakht verify failed: ', $result);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Aghayehpardakht verify error: ' . $e->getMessage());
            return false;
        }
    }
}
