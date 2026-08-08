<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderAssign;
use App\Http\Requests\Admin\OrderFetch;
use App\Http\Requests\Admin\OrderUpdate;
use App\Models\CommissionLog;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Services\OrderService;
use App\Services\UserService;
use App\Support\AdminFilter;
use App\Utils\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    private function filter(Request $request, &$builder)
    {
        if ($request->input('filter')) {
            foreach ($request->input('filter') as $filter) {
                $condition = AdminFilter::normalizeCondition($filter['condition']);
                $value = AdminFilter::prepareValue($condition, $filter['value']);

                if (in_array($filter['key'], ['email', 'invite_user_email'], true)) {
                    $foreignKey = $filter['key'] === 'email' ? 'user_id' : 'invite_user_id';
                    $builder->whereIn(
                        $foreignKey,
                        User::query()->select('id')->where('email', $condition, $value)
                    );
                    continue;
                }

                $builder->where($filter['key'], $condition, $value);
            }
        }
    }

    public function detail(Request $request)
    {
        $order = Order::find($request->input('id'));
        if (!$order) abort(500, __('Order does not exist'));
        $order['commission_log'] = CommissionLog::where('trade_no', $order->trade_no)->get();
        if ($order->surplus_order_ids) {
            $order['surplus_orders'] = Order::whereIn('id', $order->surplus_order_ids)->get();
        }
        return response([
            'data' => $order
        ]);
    }

    public function fetch(OrderFetch $request)
    {
        $current = $request->input('current') ? $request->input('current') : 1;
        $pageSize = $request->input('pageSize') >= 10 ? $request->input('pageSize') : 10;
        $orderModel = Order::orderBy('created_at', 'DESC');
        if ($request->input('is_commission')) {
            $orderModel->where('invite_user_id', '!=', NULL);
            $orderModel->whereNotIn('status', [0, 2]);
            $orderModel->where('commission_balance', '>', 0);
        }
        $this->filter($request, $orderModel);
        $total = $orderModel->count();
        $res = $orderModel->forPage($current, $pageSize)
            ->get();
        $plan = Plan::get();
        for ($i = 0; $i < count($res); $i++) {
            for ($k = 0; $k < count($plan); $k++) {
                if ($plan[$k]['id'] == $res[$i]['plan_id']) {
                    $res[$i]['plan_name'] = $plan[$k]['name'];
                }
            }
        }
        return response([
            'data' => $res,
            'total' => $total
        ]);
    }

    public function paid(Request $request)
    {
        $order = Order::where('trade_no', $request->input('trade_no'))
            ->first();
        if (!$order) {
            abort(500, __('Order does not exist'));
        }
        if ($order->status !== 0) abort(500, __('Only pending orders can be processed'));

        $orderService = new OrderService($order);
        if (!$orderService->paid('manual_operation')) {
            abort(500, __('Update failed'));
        }
        return response([
            'data' => true
        ]);
    }

    public function cancel(Request $request)
    {
        $order = Order::where('trade_no', $request->input('trade_no'))
            ->first();
        if (!$order) {
            abort(500, __('Order does not exist'));
        }
        if ($order->status !== 0) abort(500, __('Only pending orders can be processed'));

        $orderService = new OrderService($order);
        if (!$orderService->cancel()) {
            abort(500, __('Update failed'));
        }
        return response([
            'data' => true
        ]);
    }

    public function update(OrderUpdate $request)
    {
        $params = $request->only([
            'commission_status'
        ]);

        $order = Order::where('trade_no', $request->input('trade_no'))
            ->first();
        if (!$order) {
            abort(500, __('Order does not exist'));
        }

        try {
            $order->update($params);
        } catch (\Exception $e) {
            abort(500, __('Update failed'));
        }

        return response([
            'data' => true
        ]);
    }

    public function assign(OrderAssign $request)
    {
        $plan = Plan::find($request->input('plan_id'));
        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            abort(500, __('The user does not exist'));
        }

        if (!$plan) {
            abort(500, __('Subscription plan does not exist'));
        }

        $userService = new UserService();
        if ($userService->isNotCompleteOrderByUserId($user->id)) {
            abort(500, __('The user has a pending order and cannot be assigned a subscription'));
        }

        DB::beginTransaction();
        $order = new Order();
        $orderService = new OrderService($order);
        $order->user_id = $user->id;
        $order->plan_id = $plan->id;
        $order->period = $request->input('period');
        $order->trade_no = Helper::guid();
        $order->total_amount = $request->input('total_amount');

        if ($order->period === 'reset_price') {
            $order->type = 4;
        } else if ($user->plan_id !== NULL && $order->plan_id !== $user->plan_id) {
            $order->type = 3;
        } else if ($user->expired_at > time() && $order->plan_id == $user->plan_id) {
            $order->type = 2;
        } else {
            $order->type = 1;
        }

        $orderService->setInvite($user);

        if (!$order->save()) {
            DB::rollback();
            abort(500, __('Failed to create order'));
        }

        DB::commit();

        return response([
            'data' => $order->trade_no
        ]);
    }
}
