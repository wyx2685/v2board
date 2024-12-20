<?php

namespace App\Console\Commands;

use App\Services\MailService;
use App\Services\PlanService;
use App\Services\OrderService;
use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Order;
use App\Utils\Helper;
use Illuminate\Support\Facades\DB;

use Exception;

class CheckRenewal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:renewal';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '自动续费';

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
        $users = User::all();

        //$mailService = new MailService();
        foreach ($users as $user) {
            if ($user->auto_renewal && $user->plan_id !== NULL && $user->expired_at !== NULL && $user->expired_at > time() && $user->expired_at - time() < 86400 * 2) {
                try {
                    $latestOrder = Order::where('user_id', $user->id)
                        ->where('period', '!=', 'reset_price')
                        ->where('period', '!=', 'onetime_price')
                        ->where('period', '!=', 'deposit')
                        ->where('status', 3)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    if (!$latestOrder) {
                        throw new Exception("No valid order");
                    }
                    $latestPeriod = $latestOrder->period;

                    $planService = new PlanService($user->plan_id);
                    $plan = $planService->plan;
                    if (!$plan) {
                        throw new Exception("No such plan");
                    }
                    if (!$plan->renew) {
                        throw new Exception('This subscription cannot be renewed');
                    }
                    if($user->balance < $plan[$latestPeriod]) {
                        throw new Exception('No enough balance');
                    }

                    DB::beginTransaction();
                    $order = new Order();
                    $orderService = new OrderService($order);
                    $order->user_id = $user->id;
                    $order->plan_id = $plan->id;
                    $order->period = $latestPeriod;
                    $order->trade_no = Helper::generateOrderNo();
                    $order->balance_amount = $plan[$latestPeriod];
                    $order->total_amount = 0;
                    $orderService->setVipDiscount($user);
                    $order->type = 2;
                    
                    $user->balance = $user->balance - $plan[$latestPeriod];
                    $user->expired_at = $this->getTime($latestPeriod, $user->expired_at);
                    if (!$user->save()) {
                        DB::rollback();
                        throw new Exception('自动续费失败');
                    }
                    $order->status = 3;
                    if (!$order->save()) {
                        DB::rollback();
                        throw new Exception('自动续费失败');
                    }
                    DB::commit();
                    //$mailService->remindAutorenewal($user);
                } catch (\Exception $e) {
                    $user->auto_renewal = 0;
                    if(!$user->save()){
                        info('用户自动续费失败,调整设置失败', [$e->getMessage() , $user]);
                    };
                }
            }
        }
    }

    private function getTime($str, $timestamp)
    {
        if ($timestamp < time()) {
            $timestamp = time();
        }
        switch ($str) {
            case 'month_price':
                return strtotime('+1 month', $timestamp);
            case 'quarter_price':
                return strtotime('+3 month', $timestamp);
            case 'half_year_price':
                return strtotime('+6 month', $timestamp);
            case 'year_price':
                return strtotime('+12 month', $timestamp);
            case 'two_year_price':
                return strtotime('+24 month', $timestamp);
            case 'three_year_price':
                return strtotime('+36 month', $timestamp);
        }
    }
}
