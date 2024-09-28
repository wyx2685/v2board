<?php

namespace App\Services;

use App\Models\Order; // اضافه کردن مدل Order
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public $method;
    protected $class;
    protected $config;
    protected $payment;

    public function __construct($method, $id = null, $trade_no = null) // تغییر از uuid به trade_no
    {
        $this->method = $method;
        $this->class = '\\App\\Payments\\' . $this->method;
        
        // بررسی اینکه آیا کلاس وجود دارد یا نه
        if (!class_exists($this->class)) {
            abort(500, 'gate is not found');
        }

        $order = null;
        
        // بررسی بر اساس ID
        if ($id) {
            $order = Order::find($id); // جستجو در جدول v2_order
        }

        // بررسی بر اساس trade_no به جای uuid
        if ($trade_no) {
            $order = Order::where('trade_no', $trade_no)->first(); // تغییر از uuid به trade_no
        }

        // بررسی برای وجود رکورد در پایگاه داده
        if (!$order) {
            Log::error('Order not found for trade_no: ' . $trade_no); // تغییر از uuid به trade_no
            abort(500, 'Order not found for trade_no: ' . $trade_no); // تغییر از uuid به trade_no
        }

        // مقادیر فرضی برای تست
        $this->config = [
            'config' => [
                'zibal_merchant' => '66f199916f3803001c1fe39b',
                'zibal_callback' => 'https://drmobjay.com/zibal_transit.php',
            ],
            'enable' => 1,
            'trade_no' => $trade_no ?? 'dummy-trade_no', // مقدار فرضی برای trade_no به جای uuid
            'notify_domain' => null,
            'id' => $id ?? 'dummy-id' // مقدار فرضی برای ID
        ];

        // ایجاد نمونه از کلاس پرداخت
        $this->payment = new $this->class($this->config['config']);
    }

    // متد notify برای دریافت پاسخ از درگاه پرداخت
    public function notify($params)
    {
        if (!$this->config['enable']) {
            abort(500, 'gate is not enabled');
        }

        return $this->payment->notify($params);
    }

    // متد pay برای آغاز فرآیند پرداخت
    public function pay($order)
    {
        // ایجاد URL سفارشی برای notify
        $notifyUrl = url("/api/v1/guest/payment/notify/{$this->method}/{$this->config['trade_no']}"); // استفاده از trade_no
        
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

    // متد form برای نمایش فرم پرداخت
    public function form()
    {
        $form = $this->payment->form();
        $keys = array_keys($form);

        foreach ($keys as $key) {
            if (isset($this->config[$key])) {
                $form[$key]['value'] = $this->config[$key];
            }
        }

        return $form;
    }
}
