<?php

namespace Tests\Feature;

use App\Http\Controllers\V1\Guest\PaymentController;
use App\Jobs\OrderHandleJob;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PaymentSafetyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Model::unguard();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'v2board.currency' => 'CNY',
            'v2board.telegram_bot_enable' => 0,
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        Schema::connection('sqlite')->create('v2_payment', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid')->unique();
            $table->string('payment');
            $table->string('name');
            $table->text('config');
            $table->string('notify_domain')->nullable();
            $table->integer('enable')->default(1);
            $table->integer('created_at')->nullable();
            $table->integer('updated_at')->nullable();
        });

        Schema::connection('sqlite')->create('v2_order', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('payment_id')->nullable();
            $table->string('trade_no')->unique();
            $table->string('callback_no')->nullable();
            $table->integer('total_amount');
            $table->integer('handling_amount')->nullable();
            $table->integer('status')->default(0);
            $table->integer('paid_at')->nullable();
            $table->integer('created_at')->nullable();
            $table->integer('updated_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Model::reguard();
        parent::tearDown();
    }

    public function testNotificationGatewayMustMatchStoredPaymentMethod(): void
    {
        $payment = $this->createPayment('EPay', 'epay-uuid');

        $this->expectException(HttpException::class);

        (new PaymentService('MGate', null, $payment->uuid))->notify([]);
    }

    public function testPaymentNotificationValidatesMethodAmountCurrencyAndReplay(): void
    {
        Bus::fake();
        $payment = $this->createPayment('EPay', 'epay-uuid');
        $otherPayment = $this->createPayment('EPay', 'other-uuid');
        $order = Order::create([
            'payment_id' => $payment->id,
            'trade_no' => 'order-one',
            'total_amount' => 1000,
            'handling_amount' => 20,
            'status' => 0,
        ]);

        $this->assertFalse($this->handle([
            'trade_no' => $order->trade_no,
            'callback_no' => 'provider-transaction-one',
            'amount' => 1020,
            'currency' => 'CNY',
        ], $otherPayment->id));
        $this->assertSame(0, (int)$order->fresh()->status);

        $this->assertFalse($this->handle([
            'trade_no' => $order->trade_no,
            'callback_no' => 'provider-transaction-one',
            'amount' => 1019,
            'currency' => 'CNY',
        ], $payment->id));
        $this->assertFalse($this->handle([
            'trade_no' => $order->trade_no,
            'callback_no' => 'provider-transaction-one',
            'amount' => 1020,
            'currency' => 'USD',
        ], $payment->id));

        $notification = [
            'trade_no' => $order->trade_no,
            'callback_no' => 'provider-transaction-one',
            'amount' => 1020,
            'currency' => 'CNY',
        ];
        $this->assertTrue($this->handle($notification, $payment->id));
        $this->assertTrue($this->handle($notification, $payment->id));

        $order->refresh();
        $this->assertSame(1, (int)$order->status);
        $this->assertSame('provider-transaction-one', $order->callback_no);
        Bus::assertDispatchedTimes(OrderHandleJob::class, 1);

        $secondOrder = Order::create([
            'payment_id' => $payment->id,
            'trade_no' => 'order-two',
            'total_amount' => 1000,
            'handling_amount' => 20,
            'status' => 0,
        ]);
        $this->assertFalse($this->handle([
            'trade_no' => $secondOrder->trade_no,
            'callback_no' => 'provider-transaction-one',
            'amount' => 1020,
            'currency' => 'CNY',
        ], $payment->id));
        $this->assertSame(0, (int)$secondOrder->fresh()->status);
    }

    private function createPayment(string $method, string $uuid): Payment
    {
        return Payment::create([
            'uuid' => $uuid,
            'payment' => $method,
            'name' => $method,
            'config' => ['key' => 'secret'],
            'enable' => 1,
        ]);
    }

    private function handle(array $notification, int $paymentId): bool
    {
        $method = new ReflectionMethod(PaymentController::class, 'handle');
        $method->setAccessible(true);

        return $method->invoke(new PaymentController(), $notification, $paymentId);
    }
}
