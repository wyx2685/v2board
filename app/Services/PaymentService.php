<?php

namespace App\Services;


use App\Models\Payment;

class PaymentService
{
    public $method;
    protected $class;
    protected $config;
    protected $payment;
    protected $paymentRecord;

    public function __construct($method, $id = NULL, $uuid = NULL)
    {
        $this->method = $method;
        $this->class = '\\App\\Payments\\' . $this->method;
        if (!class_exists($this->class)) abort(500, __('Payment gateway not found'));
        $payment = null;
        if ($id) $payment = Payment::find($id);
        if ($uuid) $payment = Payment::where('uuid', $uuid)->first();
        if (($id || $uuid) && !$payment) abort(500, __('Payment method does not exist'));

        $this->paymentRecord = $payment;
        $this->config = [];
        if ($payment) {
            $this->config = $payment->config;
            $this->config['enable'] = $payment->enable;
            $this->config['id'] = $payment->id;
            $this->config['uuid'] = $payment->uuid;
            $this->config['notify_domain'] = $payment->notify_domain;
        };
        $this->payment = new $this->class($this->config);
    }

    public function notify($params)
    {
        if (
            !$this->paymentRecord
            || !hash_equals((string)$this->paymentRecord->payment, (string)$this->method)
        ) {
            abort(500, __('Payment gateway does not match the configured method'));
        }
        if (!$this->config['enable']) abort(500, __('Payment gateway is disabled'));
        return $this->payment->notify($params);
    }

    public function getPaymentId()
    {
        return $this->paymentRecord ? $this->paymentRecord->id : null;
    }

    public function pay($order)
    {
        // custom notify domain name
        $notifyUrl = url("/api/v1/guest/payment/notify/{$this->method}/{$this->config['uuid']}");
        if ($this->config['notify_domain']) {
            $parseUrl = parse_url($notifyUrl);
            $notifyUrl = $this->config['notify_domain'] . $parseUrl['path'];
        }

        return $this->payment->pay([
            'notify_url' => $notifyUrl,
            'return_url' => url('/#/order/' . $order['trade_no']),
            'trade_no' => $order['trade_no'],
            'total_amount' => $order['total_amount'],
            'user_id' => $order['user_id'],
            'stripe_token' => $order['stripe_token']
        ]);
    }

    public function form()
    {
        $form = $this->payment->form();
        $keys = array_keys($form);
        foreach ($keys as $key) {
            if (isset($this->config[$key])) $form[$key]['value'] = $this->config[$key];
        }
        return $form;
    }
}
