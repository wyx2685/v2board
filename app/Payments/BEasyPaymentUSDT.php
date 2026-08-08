<?php

namespace App\Payments;

use \Curl\Curl;

class BEasyPaymentUSDT {
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function form()
    {
        return [
            'bepusdt_url' => [
                'label' => __('payment.endpoint_url'),
                'description' => __('payment.bepusdt_api_url_help'),
                'type' => 'input',
            ],
            'bepusdt_apitoken' => [
                'label' => 'API Token',
                'description' => __('payment.bepusdt_token_help'),
                'type' => 'input',
            ],
            'bepusdt_trade_type' => [
                'label' => __('payment.transaction_type'),
                'description' => __('payment.bepusdt_transaction_type_help'),
                'type' => 'input',
            ]
        ];
    }

    public function pay($order)
    {
        $params = [
            'amount' => $order['total_amount'] / 100,
            'trade_type' => $this->config['bepusdt_trade_type'],
            'notify_url' => $order['notify_url'],
            'order_id' => $order['trade_no'],
            'redirect_url' => $order['return_url']
        ];
        ksort($params);
        reset($params);
        $str = stripslashes(urldecode(http_build_query($params))) . $this->config['bepusdt_apitoken'];
        $params['signature'] = md5($str);

        $curl = new Curl();
        $curl->setUserAgent('BEPUSDT');
        $curl->setOpt(CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        $curl->post($this->config['bepusdt_url'] . '/api/v1/order/create-transaction', json_encode($params));
        $result = $curl->response;
        $curl->close();

        if (!isset($result->status_code) || $result->status_code != 200) {
            abort(500, __('payment.create_order_error', ['error' => $result->message]));
        }

        $paymentURL = $result->data->payment_url;
        return [
            'type' => 1, // 0:qrcode 1:url
            'data' => $paymentURL
        ];
    }

    public function notify($params)
    {
        $sign = $params['signature'];
        unset($params['signature']);
        ksort($params);
        reset($params);
        $str = stripslashes(urldecode(http_build_query($params))) . $this->config['bepusdt_apitoken'];
        $generateSignature = md5($str);
        if (!hash_equals($generateSignature, $sign)) {
            return('cannot pass verification');
        }
        $status = $params['status'];
        // 1: pending 2: success 3: expired
        if ($status != 2) {
            return('failed');
        }
        $notification = [
            'trade_no' => $params['order_id'],
            'callback_no' => $params['trade_id'],
            'custom_result' => 'ok'
        ];
        if (isset($params['amount'])) {
            $notification['amount'] = (int)round(((float)$params['amount']) * 100);
            $notification['currency'] = config('v2board.currency', 'CNY');
        }
        return $notification;
    }
}
