<?php

namespace App\Payments;

use Omnipay\Omnipay;
use Omnipay\WechatPay\Helper;

class WechatPayNative {
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function form()
    {
        return [
            'app_id' => [
                'label' => 'APPID',
                'description' => __('payment.wechat_app_id_help'),
                'type' => 'input',
            ],
            'mch_id' => [
                'label' => __('payment.merchant_number'),
                'description' => __('payment.wechat_merchant_number_help'),
                'type' => 'input',
            ],
            'api_key' => [
                'label' => 'APIKEY(v1)',
                'description' => '',
                'type' => 'input',
            ]
        ];
    }

    public function pay($order)
    {
        $gateway = Omnipay::create('WechatPay_Native');
        $gateway->setAppId($this->config['app_id']);
        $gateway->setMchId($this->config['mch_id']);
        $gateway->setApiKey($this->config['api_key']);
        $gateway->setNotifyUrl($order['notify_url']);

        $params = [
            'body'              => $order['trade_no'],
            'out_trade_no'      => $order['trade_no'],
            'total_fee'         => $order['total_amount'],
            'spbill_create_ip'  => '0.0.0.0',
            'fee_type'          => 'CNY'
        ];

        $request  = $gateway->purchase($params);
        $response = $request->send();
        $response = $response->getData();
        if ($response['return_code'] !== 'SUCCESS') {
            abort(500, $response['return_msg']);
        }
        return [
            'type' => 0,
            'data' => $response['code_url'],
            'custom_result' => '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>'
        ];
    }

    public function notify($params)
    {
        $data = Helper::xml2array(request()->getContent() ?: json_encode($_POST));
        $gateway = Omnipay::create('WechatPay');
        $gateway->setAppId($this->config['app_id']);
        $gateway->setMchId($this->config['mch_id']);
        $gateway->setApiKey($this->config['api_key']);
        $response = $gateway->completePurchase([
            'request_params' => request()->getContent() ?: json_encode($_POST)
        ])->send();

        if (!$response->isPaid()) {
            return('FAIL');
        }

        $notification = [
            'trade_no' => $data['out_trade_no'],
            'callback_no' => $data['transaction_id']
        ];
        if (isset($data['total_fee'])) {
            $notification['amount'] = (int)$data['total_fee'];
            $notification['currency'] = strtoupper((string)($data['fee_type'] ?? 'CNY'));
        }
        return $notification;
    }
}
