<?php

/**
 * 自己写别抄，抄NMB抄
 */
namespace App\Payments;

class AlipayF2F {
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function form()
    {
        return [
            'app_id' => [
                'label' => __('payment.alipay_app_id'),
                'description' => '',
                'type' => 'input',
            ],
            'private_key' => [
                'label' => __('payment.alipay_private_key'),
                'description' => '',
                'type' => 'input',
            ],
            'public_key' => [
                'label' => __('payment.alipay_public_key'),
                'description' => '',
                'type' => 'input',
            ],
            'product_name' => [
                'label' => __('payment.custom_product_name'),
                'description' => __('payment.alipay_product_help'),
                'type' => 'input'
            ]
        ];
    }

    public function pay($order)
    {
        try {
            $gateway = new \Library\AlipayF2F();
            $gateway->setMethod('alipay.trade.precreate');
            $gateway->setAppId($this->config['app_id']);
            $gateway->setPrivateKey($this->config['private_key']); // 可以是路径，也可以是密钥内容
            $gateway->setAlipayPublicKey($this->config['public_key']); // 可以是路径，也可以是密钥内容
            $gateway->setNotifyUrl($order['notify_url']);
            $gateway->setBizContent([
                'subject' => $this->config['product_name'] ?? (config('v2board.app_name', 'V2Board') . ' - ' . __('payment.subscription_product')),
                'out_trade_no' => $order['trade_no'],
                'total_amount' => $order['total_amount'] / 100
            ]);
            $gateway->send();
            return [
                'type' => 0, // 0:qrcode 1:url
                'data' => $gateway->getQrCodeUrl()
            ];
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }
    }

    public function notify($params)
    {
        if ($params['trade_status'] !== 'TRADE_SUCCESS') return false;
        $gateway = new \Library\AlipayF2F();
        $gateway->setAppId($this->config['app_id']);
        $gateway->setPrivateKey($this->config['private_key']); // 可以是路径，也可以是密钥内容
        $gateway->setAlipayPublicKey($this->config['public_key']); // 可以是路径，也可以是密钥内容
        try {
            if ($gateway->verify($params)) {
                /**
                 * Payment is successful
                 */
                $notification = [
                    'trade_no' => $params['out_trade_no'],
                    'callback_no' => $params['trade_no']
                ];
                if (isset($params['total_amount'])) {
                    $notification['amount'] = (int)round(((float)$params['total_amount']) * 100);
                    $notification['currency'] = 'CNY';
                }
                return $notification;
            } else {
                /**
                 * Payment is not successful
                 */
                return false;
            }
        } catch (\Exception $e) {
            /**
             * Payment is not successful
             */
            return false;
        }
    }
}
