<?php

namespace App\Payments;

use \Curl\Curl;

/**
 * V2Board EPay MAPI adapter.
 *
 * Creates an order through /mapi.php and returns QR/deep-link data directly,
 * instead of redirecting the customer to /submit.php cashier.
 */
class EPayQrcode
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function form()
    {
        return [
            'url' => [
                'label' => 'URL',
                'description' => '支付站点根地址，例如：https://pay.xxxxxx.com',
                'type' => 'input',
            ],
            'pid' => [
                'label' => 'PID',
                'description' => '商户 ID',
                'type' => 'input',
            ],
            'key' => [
                'label' => 'KEY',
                'description' => '商户密钥',
                'type' => 'input',
            ],
            'type' => [
                'label' => 'TYPE',
                'description' => '必填：alipay 或 wxpay；MAPI 不允许留空',
                'type' => 'input',
            ],
        ];
    }

    public function pay($order)
    {
        $this->validateConfig();

        $params = [
            'pid' => (string) $this->config['pid'],
            'type' => (string) $this->config['type'],
            'out_trade_no' => (string) $order['trade_no'],
            'notify_url' => (string) $order['notify_url'],
            'return_url' => (string) ($order['return_url'] ?? ''),
            'name' => (string) $order['trade_no'],
            'money' => number_format(((int) $order['total_amount']) / 100, 2, '.', ''),
            'clientip' => $this->clientIp(),
            'device' => $this->deviceType(),
        ];
        $params['sign'] = $this->sign($params);
        $params['sign_type'] = 'MD5';

        $curl = new Curl();
        $curl->setUserAgent('V2Board-EPay-MAPI/1.0');
        $curl->setHeader('Accept', 'application/json');
        $curl->setHeader('Content-Type', 'application/x-www-form-urlencoded');
        $curl->setOpt(CURLOPT_CONNECTTIMEOUT, 10);
        $curl->setOpt(CURLOPT_TIMEOUT, 30);
        $curl->post($this->mapiUrl(), http_build_query($params));

        $result = $curl->response;
        if (is_string($result)) {
            $result = json_decode($result);
        }

        if (!$result) {
            $curl->close();
            abort(500, '支付接口网络异常或返回内容无法解析');
        }

        if ($curl->error || !isset($result->code) || (int) $result->code !== 1) {
            $message = isset($result->msg) ? (string) $result->msg : 'API 下单失败';
            $curl->close();
            abort(500, $message);
        }
        $curl->close();

        $paymentData = null;
        foreach (['qrcode', 'urlscheme', 'payurl'] as $field) {
            if (!empty($result->{$field})) {
                $paymentData = (string) $result->{$field};
                break;
            }
        }

        if ($paymentData === null) {
            abort(500, '接口下单成功，但没有返回 qrcode、urlscheme 或 payurl');
        }

        return [
            // V2Board: 0 = render data as QR code, 1 = open data as URL.
            // Desktop always displays a QR code; mobile opens the returned
            // QR scheme, URL scheme, or payment URL directly.
            'type' => $this->isMobile() ? 1 : 0,
            'data' => $paymentData,
        ];
    }

    public function notify($params)
    {
        if (!is_array($params) || empty($params['sign'])) {
            return false;
        }

        $receivedSign = strtolower((string) $params['sign']);
        if (!hash_equals($this->sign($params), $receivedSign)) {
            return false;
        }

        if ((string) ($params['pid'] ?? '') !== (string) ($this->config['pid'] ?? '')) {
            return false;
        }

        if (($params['trade_status'] ?? '') !== 'TRADE_SUCCESS') {
            return false;
        }

        if (empty($params['out_trade_no']) || empty($params['trade_no'])) {
            return false;
        }

        return [
            'trade_no' => (string) $params['out_trade_no'],
            'callback_no' => (string) $params['trade_no'],
        ];
    }

    /** Documented signing: sort, omit sign/sign_type/empty, no URL encoding. */
    private function sign($params)
    {
        unset($params['sign'], $params['sign_type']);
        $params = array_filter($params, static function ($value) {
            return $value !== '' && $value !== null;
        });
        ksort($params);

        $pairs = [];
        foreach ($params as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }

        return strtolower(md5(implode('&', $pairs) . $this->config['key']));
    }

    private function mapiUrl()
    {
        $baseUrl = trim((string) $this->config['url']);
        $baseUrl = preg_replace('#/(?:submit|mapi)\.php(?:\?.*)?$#i', '', $baseUrl);
        return rtrim($baseUrl, '/') . '/mapi.php';
    }

    private function validateConfig()
    {
        foreach (['url', 'pid', 'key', 'type'] as $key) {
            if (!isset($this->config[$key]) || trim((string) $this->config[$key]) === '') {
                abort(500, 'EPay 配置不完整：' . $key);
            }
        }

        if (!filter_var($this->mapiUrl(), FILTER_VALIDATE_URL)) {
            abort(500, 'EPay URL 格式错误');
        }
    }

    private function clientIp()
    {
        if (function_exists('request')) {
            $ip = request()->ip();
            if (is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return '127.0.0.1';
    }

    private function deviceType()
    {
        $ua = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if (strpos($ua, 'micromessenger') !== false) {
            return 'wechat';
        }
        if (strpos($ua, 'alipayclient') !== false) {
            return 'alipay';
        }
        if (strpos($ua, 'qq/') !== false) {
            return 'qq';
        }
        return $this->isMobile() ? 'mobile' : 'pc';
    }

    private function isMobile()
    {
        return preg_match(
            '/mobile|android|iphone|ipad/i',
            (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')
        ) === 1;
    }
}
