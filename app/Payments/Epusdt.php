<?php

namespace App\Payments;

use \Curl\Curl;

class Epusdt
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function form()
    {
        return [
            'epusdt_url' => [
                'label' => 'API 地址',
                'description' => 'Epusdt API 接口地址(例如: https://xxx.com)',
                'type' => 'input',
            ],
            'epusdt_pid' => [
                'label' => 'PID',
                'description' => 'Epusdt 后台的 pid',
                'type' => 'input',
            ],
            'epusdt_token' => [
                'label' => 'Token',
                'description' => 'Epusdt 后台的 secret_key',
                'type' => 'input',
            ],
            'epusdt_currency' => [
                'label' => '法币',
                'description' => '默认 cny',
                'type' => 'input',
            ],
            'epusdt_asset' => [
                'label' => '代币',
                'description' => '默认 usdt',
                'type' => 'input',
            ],
            'epusdt_network' => [
                'label' => '网络',
                'description' => '留空时进入 GMPay 选择链路界面，填写时按该网络直接发起订单',
                'type' => 'input',
            ],
        ];
    }

    public function pay($order)
    {
        $network = strtolower(trim((string) ($this->config['epusdt_network'] ?? '')));
        $token = empty($this->config['epusdt_asset']) ? 'usdt' : strtolower(trim((string) $this->config['epusdt_asset']));
        $params = [
            'pid' => trim((string) ($this->config['epusdt_pid'] ?? '')),
            'order_id' => (string) $order['trade_no'],
            'currency' => empty($this->config['epusdt_currency']) ? 'cny' : strtolower(trim((string) $this->config['epusdt_currency'])),
            'token' => $token,
            'network' => $network === '' ? 'tron' : $network,
            'amount' => round($order['total_amount'] / 100, 2),
            'notify_url' => $order['notify_url'],
            'redirect_url' => $order['return_url'],
        ];

        $params['signature'] = $this->makeSignature($params, trim((string) ($this->config['epusdt_token'] ?? '')));

        $curl = new Curl();
        $curl->setUserAgent('epusdt');
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, 0);
        $curl->setOpt(CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $curl->post(
            rtrim((string) $this->config['epusdt_url'], '/') . '/payments/gmpay/v1/order/create-transaction',
            json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        $result = $curl->response;
        $curl->close();

        if (!isset($result->status_code) || (int) $result->status_code !== 200) {
            $message = isset($result->message) ? $result->message : 'epusdt create order failed';
            abort(500, $message);
        }

        $paymentUrl = $result->data->payment_url ?? null;

        if ($network !== '') {
            if (!isset($result->data->trade_id) || $result->data->trade_id === '') {
                abort(500, 'epusdt create order response missing trade_id');
            }

            $switchParams = [
                'trade_id' => (string) $result->data->trade_id,
                'token' => $token,
                'network' => $network,
            ];

            $curl = new Curl();
            $curl->setUserAgent('epusdt');
            $curl->setOpt(CURLOPT_SSL_VERIFYPEER, 0);
            $curl->setOpt(CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            $curl->post(
                rtrim((string) $this->config['epusdt_url'], '/') . '/pay/switch-network',
                json_encode($switchParams, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
            $switchResult = $curl->response;
            $curl->close();

            if (!isset($switchResult->status_code) || (int) $switchResult->status_code !== 200) {
                $message = isset($switchResult->message) ? $switchResult->message : 'epusdt switch network failed';
                abort(500, $message);
            }

            $paymentUrl = $switchResult->data->payment_url ?? $paymentUrl;
        }

        if (empty($paymentUrl)) {
            abort(500, 'epusdt payment url missing');
        }

        return [
            'type' => 1,
            'data' => $paymentUrl,
        ];
    }

    public function notify($params)
    {
        if (!isset($params['signature'])) {
            return false;
        }

        $signature = strtolower((string) $params['signature']);
        unset($params['signature']);

        if (!hash_equals($this->makeSignature($params, trim((string) ($this->config['epusdt_token'] ?? ''))), $signature)) {
            return false;
        }

        if (!isset($params['status']) || (int) $params['status'] !== 2) {
            return 'failed';
        }

        return [
            'trade_no' => $params['order_id'],
            'callback_no' => $params['trade_id'],
            'custom_result' => 'ok',
        ];
    }

    private function makeSignature($params, $token)
    {
        ksort($params);

        $pairs = [];
        foreach ($params as $key => $value) {
            if ($key === 'signature' || $value === '' || $value === null) {
                continue;
            }

            if (is_float($value) || is_int($value)) {
                $value = rtrim(rtrim(sprintf('%.12F', $value), '0'), '.');
                $value = $value === '' ? '0' : $value;
            }

            $pairs[] = $key . '=' . (string) $value;
        }

        return strtolower(md5(implode('&', $pairs) . $token));
    }
}
