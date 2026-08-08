<?php

namespace App\Payments;

class Coinbase {
    public function __construct($config) {
        $this->config = $config;
    }

    public function form()
    {
        return [
            'coinbase_url' => [
                'label' => __('payment.endpoint_url'),
                'description' => '',
                'type' => 'input',
            ],
            'coinbase_api_key' => [
                'label' => 'API KEY',
                'description' => '',
                'type' => 'input',
            ],
            'coinbase_webhook_key' => [
                'label' => 'WEBHOOK KEY',
                'description' => '',
                'type' => 'input',
            ],
        ];
    }

    public function pay($order) {

        $params = [
            'name' => __('payment.subscription_plan'),
            'description' => __('payment.order_number', ['trade_no' => $order['trade_no']]),
            'pricing_type' => 'fixed_price',
            'local_price' => [
                'amount' => sprintf('%.2f', $order['total_amount'] / 100),
                'currency' => 'CNY'
            ],
            'metadata' => [
                "outTradeNo" => $order['trade_no'],
            ],
        ];

        $params_string = http_build_query($params);
        
        $ret_raw = self::_curlPost($this->config['coinbase_url'], $params_string);

        $ret = @json_decode($ret_raw, true);
        
        if(empty($ret['data']['hosted_url'])) {
            abort(500, __('payment.generic_error'));
        }
        return [
            'type' => 1,
            'data' => $ret['data']['hosted_url'],
        ];
    }

    public function notify($params) {
        
        $payload = trim(request()->getContent() ?: json_encode($_POST));
        $json_param = json_decode($payload, true); 


        $signatureHeader = (string)request()->header('X-Cc-Webhook-Signature', '');
        $computedSignature = \hash_hmac('sha256', $payload, $this->config['coinbase_webhook_key']);

        if (!self::hashEqual($signatureHeader, $computedSignature)) {
            abort(400, __('payment.signature_mismatch'));
        }

        if (($json_param['event']['type'] ?? '') !== 'charge:confirmed') {
            return [
                'ignored' => true,
                'custom_result' => 'success',
            ];
        }
        
        $out_trade_no = $json_param['event']['data']['metadata']['outTradeNo'];
        $pay_trade_no=$json_param['event']['id'];
        $notification = [
            'trade_no' => $out_trade_no,
            'callback_no' => $pay_trade_no
        ];
        $localPrice = $json_param['event']['data']['pricing']['local'] ?? null;
        if (is_array($localPrice) && isset($localPrice['amount'])) {
            $notification['amount'] = (int)round(((float)$localPrice['amount']) * 100);
            $notification['currency'] = strtoupper((string)($localPrice['currency'] ?? 'CNY'));
        }
        return $notification;
    }


    private function _curlPost($url,$params=false){
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt(
            $ch, CURLOPT_HTTPHEADER, array('X-CC-Api-Key:' .$this->config['coinbase_api_key'], 'X-CC-Version: 2018-03-22')
        );
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }


    /**
     * @param string $str1
     * @param string $str2
     * @return bool
     */
    public function hashEqual($str1, $str2)
    {
        if (function_exists('hash_equals')) {
            return \hash_equals($str1, $str2);
        }

        if (strlen($str1) != strlen($str2)) {
            return false;
        } else {
            $res = $str1 ^ $str2;
            $ret = 0;

            for ($i = strlen($res) - 1; $i >= 0; $i--) {
                $ret |= ord($res[$i]);
            }
            return !$ret;
        }
    }
    
}
