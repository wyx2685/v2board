<?php

namespace Tests\Unit;

use App\Jobs\OrderHandleJob;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'v2board.deposit_bounus' => [],
        ]);
        DB::purge('sqlite');

        Schema::create('v2_user', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->bigInteger('balance')->default(0);
            $table->unsignedInteger('created_at')->nullable();
            $table->unsignedInteger('updated_at')->nullable();
        });
        Schema::create('v2_order', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->string('trade_no');
            $table->unsignedTinyInteger('status')->default(0);
            $table->unsignedTinyInteger('type')->default(1);
            $table->bigInteger('total_amount')->default(0);
            $table->bigInteger('balance_amount')->default(0);
            $table->unsignedInteger('paid_at')->nullable();
            $table->string('callback_no')->nullable();
            $table->unsignedInteger('created_at')->nullable();
            $table->unsignedInteger('updated_at')->nullable();
        });
    }

    public function testCancelRefundsBalanceOnlyOnceForStaleOrderModels()
    {
        $this->createUser();
        $this->createOrder(['balance_amount' => 100]);
        $firstOrder = Order::find(1);
        $secondOrder = Order::find(1);

        $this->assertTrue((new OrderService($firstOrder))->cancel());
        $this->assertTrue((new OrderService($secondOrder))->cancel());

        $this->assertSame(2, (int)Order::find(1)->status);
        $this->assertSame(100, (int)User::find(1)->balance);
    }

    public function testPaidQueuesOrderOnlyOnceForStaleOrderModels()
    {
        Queue::fake();
        $this->createUser();
        $this->createOrder();
        $firstOrder = Order::find(1);
        $secondOrder = Order::find(1);

        $this->assertTrue((new OrderService($firstOrder))->paid('callback-1'));
        $this->assertTrue((new OrderService($secondOrder))->paid('callback-2'));

        $order = Order::find(1);
        $this->assertSame(1, (int)$order->status);
        $this->assertSame('callback-1', $order->callback_no);
        Queue::assertPushed(OrderHandleJob::class, 1);
    }

    public function testOpenCreditsDepositOnlyOnceForStaleOrderModels()
    {
        $this->createUser();
        $this->createOrder([
            'status' => 1,
            'type' => 9,
            'total_amount' => 100,
        ]);
        $firstOrder = Order::find(1);
        $secondOrder = Order::find(1);

        $this->assertTrue((new OrderService($firstOrder))->open());
        $this->assertTrue((new OrderService($secondOrder))->open());

        $this->assertSame(3, (int)Order::find(1)->status);
        $this->assertSame(100, (int)User::find(1)->balance);
    }

    private function createUser(array $attributes = []): void
    {
        DB::table('v2_user')->insert(array_merge([
            'id' => 1,
            'balance' => 0,
            'created_at' => time(),
            'updated_at' => time(),
        ], $attributes));
    }

    private function createOrder(array $attributes = []): void
    {
        DB::table('v2_order')->insert(array_merge([
            'id' => 1,
            'user_id' => 1,
            'trade_no' => 'test-order',
            'status' => 0,
            'type' => 1,
            'total_amount' => 0,
            'balance_amount' => 0,
            'created_at' => time(),
            'updated_at' => time(),
        ], $attributes));
    }
}
