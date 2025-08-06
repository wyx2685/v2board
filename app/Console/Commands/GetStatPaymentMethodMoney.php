<?php
namespace App\Console\Commands;

use DateTime;
use DateTimeZone;
use App\Models\Order;
use App\Models\Payment;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class GetStatPaymentMethodMoney extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customFunction:GetStatPaymentMethodMoney';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '统计各个支付通道当天的收款总额';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        ini_set('memory_limit', -1);
        $timezone = new DateTimeZone('Asia/Shanghai');
        $yesterday = new DateTime('yesterday', $timezone);
        $startOfDay = $yesterday->setTime(0, 0, 0)->getTimestamp();
        $endOfDay = $yesterday->setTime(23, 59, 59)->getTimestamp();

        $orders = Order::whereIn('status', [3, 4])
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->orderBy('created_at', 'ASC')
            ->get();

        $paymentTotals = [];
        $paymentCounts = [];
        $totalMoney = 0;  // 初始化总额

        foreach ($orders as $order) {
            $money = $order->total_amount / 100;
            $paymentID = $order->payment_id;
            $payment = Payment::where('id', $paymentID)->first();
            if ($payment) {
                $paymentName = $payment->name;

                // 计算每种支付方式的总金额
                if (isset($paymentTotals[$paymentName])) {
                    $paymentTotals[$paymentName] += $money;
                } else {
                    $paymentTotals[$paymentName] = $money;
                }

                // 统计每种支付方式的收款笔数
                if (isset($paymentCounts[$paymentName])) {
                    $paymentCounts[$paymentName]++;
                } else {
                    $paymentCounts[$paymentName] = 1;
                }

                // 计算总金额
                $totalMoney += $money;
            }
        }

        // 构建消息内容
        $message = "昨天的收款:`{$totalMoney}`元\n\n";
        foreach ($paymentTotals as $paymentName => $totalMoneyForPayment) {
            $paymentCount = $paymentCounts[$paymentName];
            $message .= "`{$paymentName}` 收款 `{$paymentCount}` 笔,共计:`{$totalMoneyForPayment}`元\n";
        }

        // 发送消息到 Telegram
        $telegramService = new TelegramService();
        $telegramService->sendMessageWithAdmin($message);
    }
}