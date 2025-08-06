<?php

namespace App\Http\Controllers\V1\Guest;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Plan;
use App\Models\Coupon;
use App\Models\Payment;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function notify($method, $uuid, Request $request)
    {
        try {
            $paymentService = new PaymentService($method, null, $uuid);
            $verify = $paymentService->notify($request->input());
            if (!$verify) abort(500, 'verify error');
            if (!$this->handle($verify['trade_no'], $verify['callback_no'])) {
                abort(500, 'handle error');
            }
            return(isset($verify['custom_result']) ? $verify['custom_result'] : 'success');
        } catch (\Exception $e) {
            abort(500, 'fail');
        }
    }

    /**
     * 处理支付回调
     *
     * @param string $tradeNo 交易订单号
     * @param string $callbackNo 回调订单号
     * @return bool
     */
    private function handle($tradeNo, $callbackNo)
    {
        $order = Order::where('trade_no', $tradeNo)->first();
        if (!$order) {
            abort(500, 'order is not found');
        }
        if ($order->status !== 0) return true;

        $orderService = new OrderService($order);
        if (!$orderService->paid($callbackNo)) {
            return false;
        }

        // 获取详细信息用于Telegram通知
        $this->sendDetailedNotification($order);

        return true;
    }

    /**
     * 发送详细的Telegram通知
     *
     * @param Order $order 订单对象
     * @return void
     */
    private function sendDetailedNotification($order)
    {
        // 获取用户信息
        $user = User::find($order->user_id);

        // 获取套餐信息
        $plan = Plan::find($order->plan_id);

        // 获取优惠券信息
        $coupon = $order->coupon_id ? Coupon::find($order->coupon_id) : null;

        // 获取支付方式信息
        $payment = $order->payment_id ? Payment::find($order->payment_id) : null;

        // 获取邀请人信息
        $inviter = $user && $user->invite_user_id ? User::find($user->invite_user_id) : null;

        // 计算今日总收入
        $todayIncome = Order::where('created_at', '>=', strtotime(date('Y-m-d')))
            ->where('created_at', '<', time())
            ->whereNotIn('status', [0, 2])
            ->sum('total_amount');

        // 获取套餐周期名称
        $periodMap = [
            'month_price' => '月付',
            'quarter_price' => '季付',
            'half_year_price' => '半年付',
            'year_price' => '年付',
            'two_year_price' => '两年付',
            'three_year_price' => '三年付',
            'onetime_price' => '一次性',
            'reset_price' => '重置包',
            'deposit'=>'余额充值'
        ];
        $periodName = $periodMap[$order->period] ?? $order->period;

        // 获取支付渠道信息
        $paymentChannel = $this->getPaymentChannel($payment);

        // 格式化注册日期
        $registerDate = $user ? date('Y-m-d H:i:s', $user->created_at) : '未知';

        // 获取来源网址
        $sourceUrl = $_SERVER['HTTP_REFERER'] ?? $_SERVER['HTTP_HOST'] ?? config('app.url', '未知');

        // 构建消息
        $message = sprintf(
            "💰 成功收款%s元\n" .
            "———————————————\n" .
            "🌐 支付接口：%s\n" .
            "🏦 支付渠道：%s\n" .
            "📧 用户邮箱：%s\n" .
            "📦 购买套餐：%s\n" .
            "📅 套餐周期：%s\n" .
            "🎫 优  惠  券：%s\n" .
            "👥 邀  请  人：%s\n" .
            "🆔 订  单  号：%s\n" .
            "🌐 来源网址：%s\n" .
            "📅 注册日期：%s\n" .
            "———————————————\n" .
            "💵 今日总收入：%s元",
            number_format($order->total_amount / 100, 2),
            $payment ? $payment->name : '未知',
            $paymentChannel,
            $user ? $user->email : '未知',
            $plan ? $plan->name : '未知套餐',
            $periodName,
            $coupon ? $coupon->name : '无',
            $inviter ? $inviter->email : '无',
            $order->trade_no,
            $sourceUrl,
            $registerDate,
            number_format($todayIncome / 100, 2)
        );

        $telegramService = new TelegramService();
        $telegramService->sendMessageWithAdmin($message);
    }

    /**
     * 获取支付渠道信息
     *
     * @param Payment|null $payment 支付对象
     * @return string
     */
    private function getPaymentChannel($payment)
    {
        if (!$payment) {
            return '未知';
        }

        // 根据支付接口类型返回对应的渠道名称
        $channelMap = [
            'AlipayF2F' => '支付宝',
            'WechatPayNative' => '微信支付',
            'EPay' => '微信/支付宝(推荐支付宝)',
            'StripeAlipay' => '支付宝(Stripe)',
            'StripeWepay' => '微信(Stripe)',
            'StripeCredit' => '信用卡(Stripe)',
            'StripeCheckout' => 'Stripe',
            'StripeALL' => 'Stripe全能',
            'BTCPay' => '比特币',
            'Coinbase' => 'Coinbase',
            'CoinPayments' => 'CoinPayments',
            'BEasyPaymentUSDT' => 'USDT',
            'MGate' => 'MGate'
        ];

        return $channelMap[$payment->payment] ?? $payment->payment;
    }
}
