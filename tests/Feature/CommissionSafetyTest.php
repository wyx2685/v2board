<?php

namespace Tests\Feature;

use App\Console\Commands\CheckCommission;
use App\Models\CommissionLog;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CommissionSafetyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'v2board.commission_distribution_enable' => 0,
            'v2board.withdraw_close_enable' => 0,
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        Schema::connection('sqlite')->create('v2_user', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('invite_user_id')->nullable();
            $table->integer('balance')->default(0);
            $table->integer('commission_balance')->default(0);
            $table->integer('created_at')->nullable();
            $table->integer('updated_at')->nullable();
        });

        Schema::connection('sqlite')->create('v2_order', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('invite_user_id')->nullable();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('plan_id')->default(1);
            $table->string('trade_no')->unique();
            $table->integer('total_amount')->default(0);
            $table->integer('balance_amount')->default(0);
            $table->integer('status')->default(0);
            $table->integer('commission_status')->default(0);
            $table->integer('commission_balance')->default(0);
            $table->integer('actual_commission_balance')->nullable();
            $table->integer('created_at')->nullable();
            $table->integer('updated_at')->nullable();
        });

        Schema::connection('sqlite')->create('v2_commission_log', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('invite_user_id');
            $table->unsignedInteger('user_id');
            $table->string('trade_no');
            $table->integer('order_amount');
            $table->integer('get_amount');
            $table->integer('created_at')->nullable();
            $table->integer('updated_at')->nullable();
        });
    }

    public function testCommissionPayoutIsCompletedOnlyOnce(): void
    {
        $inviter = User::create([
            'balance' => 0,
            'commission_balance' => 0,
        ]);
        $buyer = User::create([
            'invite_user_id' => $inviter->id,
            'balance' => 0,
            'commission_balance' => 0,
        ]);
        $order = Order::create([
            'invite_user_id' => $inviter->id,
            'user_id' => $buyer->id,
            'trade_no' => 'completed-order',
            'total_amount' => 1000,
            'status' => 3,
            'commission_status' => 1,
            'commission_balance' => 100,
        ]);

        $command = new CheckCommission();
        $command->autoPayCommission();
        $command->autoPayCommission();

        $this->assertSame(100, (int)$inviter->fresh()->commission_balance);
        $this->assertSame(1, CommissionLog::count());
        $this->assertSame(2, (int)$order->fresh()->commission_status);
        $this->assertSame(100, (int)$order->fresh()->actual_commission_balance);
    }

    public function testCommissionIsNotPaidForCancelledOrder(): void
    {
        $inviter = User::create([
            'balance' => 0,
            'commission_balance' => 0,
        ]);
        $buyer = User::create([
            'invite_user_id' => $inviter->id,
            'balance' => 0,
            'commission_balance' => 0,
        ]);
        Order::create([
            'invite_user_id' => $inviter->id,
            'user_id' => $buyer->id,
            'trade_no' => 'cancelled-order',
            'total_amount' => 1000,
            'status' => 2,
            'commission_status' => 1,
            'commission_balance' => 100,
        ]);

        $command = new CheckCommission();
        $command->autoPayCommission();

        $this->assertSame(0, (int)$inviter->fresh()->commission_balance);
        $this->assertSame(0, CommissionLog::count());
    }

    public function testCancellingPendingOrderInvalidatesItsCommission(): void
    {
        $order = Order::create([
            'user_id' => 1,
            'trade_no' => 'pending-order',
            'status' => 0,
            'commission_status' => 0,
            'commission_balance' => 100,
            'balance_amount' => 0,
        ]);

        $this->assertTrue((new OrderService($order))->cancel());

        $order->refresh();
        $this->assertSame(2, (int)$order->status);
        $this->assertSame(3, (int)$order->commission_status);
    }
}
