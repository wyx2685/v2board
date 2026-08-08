<?php

namespace App\Payments;

class CoinPayments {
    public function __construct($config) {
        $this->config = $config;
    }

    public function form()
    {
        return [
            'coinpayments_merchant_id' => [
                'label' => 'Merchant ID',
                'description' => __('payment.merchant_id_help'),
                'type' => 'input',
            ],
            'coinpayments_ipn_secret' => [
                'label' => 'IPN Secret',
                'description' => __('payment.ipn_secret_help'),
                'type' => 'input',
            ],
            'coinpayments_currency' => [
                'label' => __('payment.currency_code'),
                'description' => __('payment.currency_code_help'),
                'type' => 'input',
            ]
        ];
    }

    public function pay($order)
    {

        // IPN notifications are slow, when the transaction is successful, we should return to the user center to avoid user confusion
        $parseUrl = parse_url($order['return_url']);
        $port = isset($parseUrl['port']) ? ":{$parseUrl['port']}" : '';
        $successUrl = "{$parseUrl['scheme']}://{$parseUrl['host']}{$port}";

        $params = [
            'cmd' => '_pay_simple',
            'reset' => 1,
            'merchant' => $this->config['coinpayments_merchant_id'],
            'item_name' => $order['trade_no'],
            'item_number' => $order['trade_no'],
            'want_shipping' => 0,
            'currency' => $this->config['coinpayments_currency'],
            'amountf' => sprintf('%.2f', $order['total_amount'] / 100),
            'success_url' => $successUrl,
            'cancel_url' => $order['return_url'],
            'ipn_url' => $order['notify_url']
        ];

        $params_string = http_build_query($params);

        return [
            'type' => 1, // Redirect to url
            'data' =>  'https://www.coinpayments.net/index.php?' . $params_string
        ];
    }

    public function notify($params)
    {

        if (!isset($params['merchant']) || $params['merchant'] != trim($this->config['coinpayments_merchant_id'])) {
            abort(500, __('payment.invalid_merchant_id'));
        }

        $headers = getallheaders();

        ksort($params);
        reset($params);
        $request = stripslashes(http_build_query($params));

        $headerName = 'Hmac';
        $signHeader = isset($headers[$headerName]) ? $headers[$headerName] : '';

        $hmac = hash_hmac("sha512", $request, trim($this->config['coinpayments_ipn_secret']));

        // if ($hmac != $signHeader) { <-- Use this if you are running a version of PHP below 5.6.0 without the hash_equals function
        //     abort(400, 'HMAC signature does not match');
        // }

        if (!hash_equals($hmac, $signHeader)) {
            abort(400, __('payment.signature_mismatch'));
        }

        // HMAC Signature verified at this point, load some variables.
        $status = $params['status'];
        if ($status >= 100 || $status == 2) {
            // payment is complete or queued for nightly payout, success
            $notification = [
                'trade_no' => $params['item_number'],
                'callback_no' => $params['txn_id'],
                'custom_result' => 'IPN OK'
            ];
            if (isset($params['amount1'])) {
                $notification['amount'] = (int)round(((float)$params['amount1']) * 100);
                $notification['currency'] = strtoupper((string)($params['currency1'] ?? $this->config['coinpayments_currency']));
            }
            return $notification;
        } else if ($status < 0) {
            //payment error, this is usually final but payments will sometimes be reopened if there was no exchange rate conversion or with seller consent
            abort(500, __('payment.payment_timeout_or_error'));
        } else {
            //payment is pending, you can optionally add a note to the order page
            return('IPN OK: pending');
        }
    }
}
