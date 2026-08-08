<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Services\OrderService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class OrderServiceTest extends TestCase
{
    public function testOneTimePurchaseReplacesOldQuotaInsteadOfAccumulatingIt(): void
    {
        $gigabyte = 1073741824;
        $order = new Order();
        $plan = new Plan();
        $plan->forceFill([
            'group_id' => 3,
            'transfer_enable' => 100,
            'device_limit' => 2,
        ]);
        $plan->id = 8;
        $user = new User();
        $user->forceFill([
            'u' => 5 * $gigabyte,
            'd' => 10 * $gigabyte,
            'transfer_enable' => 50 * $gigabyte,
            'expired_at' => null,
        ]);

        $service = new OrderService($order);
        $service->user = $user;

        $method = new ReflectionMethod(OrderService::class, 'buyByOneTime');
        $method->setAccessible(true);
        $method->invoke($service, $order, $plan);

        $this->assertSame(100 * $gigabyte, $user->transfer_enable);
        $this->assertSame(0, $user->u);
        $this->assertSame(0, $user->d);
        $this->assertSame(8, $user->plan_id);
        $this->assertSame(3, $user->group_id);
        $this->assertSame(2, $user->device_limit);
        $this->assertNull($user->expired_at);
    }
}
