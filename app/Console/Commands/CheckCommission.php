<?php

namespace App\Console\Commands;

use App\Models\CommissionLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class CheckCommission extends LocalizedCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:commission';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $descriptionKey = 'console.descriptions.check_commission';

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
        $this->autoCheck();
        $this->autoPayCommission();
    }

    public function autoCheck()
    {
        if ((int)config('v2board.commission_auto_check_enable', 1)) {
            Order::where('commission_status', 0)
                ->where('invite_user_id', '!=', NULL)
                ->whereIn('status', [3, 4])
                ->where('updated_at', '<=', strtotime('-3 day', time()))
                ->update([
                    'commission_status' => 1
                ]);
        }
    }

    public function autoPayCommission()
    {
        $orderIds = Order::where('commission_status', 1)
            ->where('invite_user_id', '!=', NULL)
            ->whereIn('status', [3, 4])
            ->pluck('id');

        foreach ($orderIds as $orderId) {
            try {
                DB::transaction(function () use ($orderId) {
                    $order = Order::where('id', $orderId)
                        ->lockForUpdate()
                        ->first();

                    if (
                        !$order
                        || (int) $order->commission_status !== 1
                        || !in_array((int) $order->status, [3, 4], true)
                        || !$order->invite_user_id
                    ) {
                        return;
                    }

                    if (!$this->payHandle($order->invite_user_id, $order)) {
                        throw new RuntimeException('Unable to pay order commission.');
                    }

                    $order->commission_status = 2;
                    if (!$order->save()) {
                        throw new RuntimeException('Unable to finalize order commission.');
                    }
                }, 3);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    public function payHandle($inviteUserId, Order $order)
    {
        if ((int)config('v2board.commission_distribution_enable', 0)) {
            $commissionShareLevels = [
                0 => (int)config('v2board.commission_distribution_l1'),
                1 => (int)config('v2board.commission_distribution_l2'),
                2 => (int)config('v2board.commission_distribution_l3')
            ];
        } else {
            $commissionShareLevels = [
                0 => 100
            ];
        }
        $visitedInviterIds = [];
        foreach (array_slice($commissionShareLevels, 0, 3) as $commissionShare) {
            if (!$inviteUserId || isset($visitedInviterIds[$inviteUserId])) break;
            $visitedInviterIds[$inviteUserId] = true;

            $inviter = User::find($inviteUserId);
            if (!$inviter) break;

            $nextInviteUserId = $inviter->invite_user_id;
            $commissionBalance = (int)floor($order->commission_balance * ($commissionShare / 100));
            if (!$commissionBalance) {
                $inviteUserId = $nextInviteUserId;
                continue;
            }

            $balanceColumn = (int)config('v2board.withdraw_close_enable', 0)
                ? 'balance'
                : 'commission_balance';
            if (User::whereKey($inviter->id)->increment($balanceColumn, $commissionBalance) !== 1) {
                return false;
            }

            if (!CommissionLog::create([
                'invite_user_id' => $inviteUserId,
                'user_id' => $order->user_id,
                'trade_no' => $order->trade_no,
                'order_amount' => $order->total_amount,
                'get_amount' => $commissionBalance
            ])) {
                return false;
            }
            $inviteUserId = $nextInviteUserId;
            // update order actual commission balance
            $order->actual_commission_balance = (int)$order->actual_commission_balance + $commissionBalance;
        }
        return true;
    }

}
