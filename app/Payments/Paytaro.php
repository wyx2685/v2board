<?php
/**
 * Paytaro 支付接口（V2Board / Xboard）
 * 预置。后台「支付配置」添加时接口选择 Paytaro；
 * 文档：https://v3.paytaro.com/#/docs/v2board   客服：https://t.me/smogate
 */

namespace App\Payments;

class Paytaro
{
    const GATEWAY_URL = 'https://v3.paytaro.com';

    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function form()
    {
        return [
            'pid' => [
                'label' => 'App ID',
                'description' => 'Paytaro 应用的 App ID；',
                'type' => 'input',
            ],
            'key' => [
                'label' => 'App Secret',
                'description' => 'Paytaro 应用的 App Secret；',
                'type' => 'input',
            ],
            'alert1' => [
                'type' => 'alert',
                'content' => '开户 / 开通支付方式请联系：<a href="https://t.me/smogate" target="_blank">@smogate</a>',
            ],
        ];
    }

    private function appId()
    {
        return !empty($this->config['pid']) ? trim($this->config['pid']) : '';
    }

    private function appSecret()
    {
        return !empty($this->config['key']) ? trim($this->config['key']) : '';
    }

    private function sign(array $params)
    {
        unset($params['sign'], $params['sign_type']);
        $params = array_filter($params, function ($v) { return $v !== '' && $v !== null; });
        ksort($params);
        $pairs = [];
        foreach ($params as $k => $v) {
            $pairs[] = $k . '=' . $v;
        }
        return md5(implode('&', $pairs) . $this->appSecret());
    }

    public function pay($order)
    {
        $params = [
            'pid' => $this->appId(),
            'type' => 'alipay',
            'out_trade_no' => $order['trade_no'],
            'notify_url' => $order['notify_url'],
            'return_url' => $order['return_url'],
            'name' => $order['trade_no'],
            'money' => number_format($order['total_amount'] / 100, 2, '.', ''),
        ];
        $params = array_filter($params, function ($v) { return $v !== '' && $v !== null; });
        $params['sign'] = $this->sign($params);
        $params['sign_type'] = 'MD5';

        return [
            'type' => 1, // 0: qrcode, 1: url
            'data' => self::GATEWAY_URL . '/submit.php?' . http_build_query($params),
        ];
    }

    public function notify($params)
    {
        if (empty($params['sign']) || strtolower($params['sign']) !== $this->sign($params)) {
            return false;
        }
        if (($params['trade_status'] ?? '') !== 'TRADE_SUCCESS') {
            return false;
        }
        return [
            'trade_no' => $params['out_trade_no'],
            'callback_no' => $params['trade_no'],
            'custom_result' => 'success',
        ];
    }
}