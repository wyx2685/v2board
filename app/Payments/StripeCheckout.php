<?php
/**
 * 基于https://github.com/linusxiong/Xboard的StripeCheckout.php文件修改而。更新 paymentIntents API、checkout关闭手机号码，推送用户邮箱
 * https://raw.githubusercontent.com/linusxiong/Xboard/dev/app/Payments/StripeCheckout.php
 */
namespace App\Payments;

use App\Exceptions\ApiException;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Models\User;

class StripeCheckout {
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function form()
    {
        return [
            'currency' => [
                'label' => '货币单位',
                'description' => '',
                'type' => 'input',
            ],
            'stripe_sk_live' => [
                'label' => 'SK_LIVE',
                'description' => 'API 密钥',
                'type' => 'input',
            ],
            'stripe_pk_live' => [
                'label' => 'PK_LIVE',
                'description' => 'API 公钥',
                'type' => 'input',
            ],
            'stripe_webhook_key' => [
                'label' => 'WebHook 密钥签名',
                'description' => '',
                'type' => 'input',
            ]
        ];
    }

    public function pay($order)
    {
        $currency = $this->config['currency'];
        $exchange = $this->exchange('CNY', strtoupper($currency));
        if (!$exchange) {
            throw new abort('Currency conversion has timed out, please try again later', 500);
        }
        
        $userEmail = $this->getUserEmail($order['user_id']);
        
        $params = [
            'success_url' => $order['return_url'],
            'cancel_url' => $order['return_url'],
            'client_reference_id' => $order['trade_no'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => $order['trade_no']
                        ],
                        'unit_amount' => floor($order['total_amount'] * $exchange)
                    ],
                    'quantity' => 1
                ]
            ],
            'mode' => 'payment',
            'invoice_creation' => ['enabled' => true],
            'phone_number_collection' => ['enabled' => false],
            'customer_email' => $userEmail, 
        ];

        Stripe::setApiKey($this->config['stripe_sk_live']);
        try {
            $session = Session::create($params);
        } catch (\Exception $e) {
            info($e);
            throw new abort("Failed to create order. Error: {$e->getMessage}");
        }
        return [
            'type' => 1, // 0:qrcode 1:url
            'data' => $session->url
        ];
    }

    public function notify($params)
    {
       try {
            $event = \Stripe\Webhook::constructEvent(
                request()->getContent() ?: json_encode($_POST),
                $_SERVER['HTTP_STRIPE_SIGNATURE'],
                $this->config['stripe_webhook_key']
            );
        } catch (\Stripe\Error\SignatureVerification $e) {
            abort(400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $object = $event->data->object;
                if ($object->payment_status === 'paid') {
                    return [
                        'trade_no' => $object->client_reference_id,
                        'callback_no' => $object->payment_intent
                    ];
                }
                break;
            case 'checkout.session.async_payment_succeeded':
                $object = $event->data->object;
                return [
                    'trade_no' => $object->client_reference_id,
                    'callback_no' => $object->payment_intent
                ];
                break;
            default:
                throw new abort('event is not support');
        }
        die('success');
    }

    private function exchange($from, $to)
{
    $from = strtolower($from);
    $to = strtolower($to);
    
    // 使用第一个API进行货币转换
    try {
        $result = file_get_contents("https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/" . $from . ".min.json");
        $result = json_decode($result, true);
        
        // 如果转换成功，返回结果
        if (isset($result[$from][$to])) {
            return $result[$from][$to];
        } else {
            throw new \Exception("Primary currency API failed.");
        }
    } catch (\Exception $e) {
        // 如果第一个API不可用，调用备用API
        return $this->backupExchange($from, $to);
    }
}

// 备用货币转换 API 方法
private function backupExchange($from, $to)
{
    try {
        $url = "https://api.exchangerate-api.com/v4/latest/{$from}";
        $result = file_get_contents($url);
        $result = json_decode($result, true);
        
        // 如果转换成功，返回结果
        if (isset($result['rates'][$to])) {
            return $result['rates'][$to];
        } else {
            throw new \Exception("Backup currency API failed.");
        }
    } catch (\Exception $e) {
        // 如果备用API也失败，调用第二个备用API
        return $this->thirdExchange($from, $to);
    }
}

// 第二个备用货币转换 API 方法
private function thirdExchange($from, $to)
{
    try {
        $url = "https://api.frankfurter.app/latest?from={$from}&to={$to}";
        $result = file_get_contents($url);
        $result = json_decode($result, true);
        
        // 如果转换成功，返回结果
        if (isset($result['rates'][$to])) {
            return $result['rates'][$to];
        } else {
            throw new \Exception("Third currency API failed.");
        }
    } catch (\Exception $e) {
        // 如果所有API都失败，抛出异常
        throw new \Exception("All currency conversion APIs failed.");
    }
}

    private function getUserEmail($userId)
    {
        $user = User::find($userId);
        return $user ? $user->email : null;
    }
}
