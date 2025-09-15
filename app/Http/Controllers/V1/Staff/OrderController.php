<?php

namespace App\Http\Controllers\V1\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\OrderAssign;
use App\Models\Order;
use App\Models\User;
use App\Models\Plan;
use App\Models\Staff;
use App\Services\OrderService;
use App\Services\UserService;
use App\Utils\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Get orders list with filters and pagination
     */
    public function fetch(Request $request)
    {
        $staffUserId = $request->input('user.id');
        
        $current = $request->input('page', 1);
        $pageSize = $request->input('limit', 10);
        $pageSize = $pageSize >= 10 ? $pageSize : 10;
        
        $orderModel = Order::select('*')
            ->where('invite_user_id', $staffUserId)
            ->orderBy($request->input('sort', 'created_at'), 
                     in_array($request->input('sort_type'), ['ASC', 'DESC']) ? $request->input('sort_type') : 'DESC');

        // Filters
        if ($request->has('status') && $request->input('status') !== '') {
            $orderModel->where('status', $request->input('status'));
        }

        if ($request->has('user_id')) {
            $orderModel->where('user_id', $request->input('user_id'));
        }

        if ($request->has('trade_no')) {
            $orderModel->where('trade_no', 'like', '%' . $request->input('trade_no') . '%');
        }

        // Date range filter
        if ($request->has('start_date')) {
            $orderModel->where('created_at', '>=', strtotime($request->input('start_date')));
        }
        if ($request->has('end_date')) {
            $orderModel->where('created_at', '<=', strtotime($request->input('end_date') . ' 23:59:59'));
        }

        $total = $orderModel->count();
        $orders = $orderModel->forPage($current, $pageSize)->get();

        // Load related data
        $userIds = $orders->pluck('user_id')->unique()->toArray();
        $planIds = $orders->pluck('plan_id')->unique()->toArray();
        
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');
        $plans = Plan::whereIn('id', $planIds)->get()->keyBy('id');

        foreach ($orders as $order) {
            $order->user_email = isset($users[$order->user_id]) ? $users[$order->user_id]->email : null;
            $order->plan_name = isset($plans[$order->plan_id]) ? $plans[$order->plan_id]->name : null;
        }

        return response()->json([
            'data' => $orders,
            'total' => $total,
            'current' => (int)$current,
            'pageSize' => (int)$pageSize
        ]);
    }

    /**
     * Get order statistics
     */
    public function stat(Request $request)
    {
        $staffUserId = $request->input('user.id');
        
        // Today stats
        $todayStart = strtotime(date('Y-m-d'));
        $todayEnd = time();
        
        $todayOrders = Order::where('invite_user_id', $staffUserId)
            ->where('created_at', '>=', $todayStart)
            ->where('created_at', '<=', $todayEnd)
            ->whereNotIn('status', [0, 2]); // Exclude unpaid and cancelled
            
        $todayStats = [
            'count' => $todayOrders->count(),
            'amount' => $todayOrders->sum('total_amount')
        ];

        // This month stats
        $monthStart = strtotime(date('Y-m-01'));
        
        $monthOrders = Order::where('invite_user_id', $staffUserId)
            ->where('created_at', '>=', $monthStart)
            ->where('created_at', '<=', $todayEnd)
            ->whereNotIn('status', [0, 2]);
            
        $monthStats = [
            'count' => $monthOrders->count(),
            'amount' => $monthOrders->sum('total_amount')
        ];

        // Total stats
        $totalOrders = Order::where('invite_user_id', $staffUserId)
            ->whereNotIn('status', [0, 2]);
            
        $totalStats = [
            'count' => $totalOrders->count(),
            'amount' => $totalOrders->sum('total_amount')
        ];

        // Pending orders
        $pendingCount = Order::where('invite_user_id', $staffUserId)
            ->where('status', 0)
            ->count();

        // Commission stats
        $totalCommission = Order::where('invite_user_id', $staffUserId)
            ->whereNotIn('status', [0, 2])
            ->sum('commission_balance');

        return response()->json([
            'today' => $todayStats,
            'month' => $monthStats,
            'total' => $totalStats,
            'pending_count' => $pendingCount,
            'total_commission' => $totalCommission
        ]);
    }

    /**
     * Get single order details
     */
    public function detail(Request $request)
    {
        $orderId = $request->input('id');
        if (!$orderId) {
            return response()->json(['message' => 'Order ID is required'], 400);
        }

        $staffUserId = $request->input('user.id');
        
        $order = Order::where('id', $orderId)
            ->where('invite_user_id', $staffUserId)
            ->first();
            
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Load related data
        $user = User::find($order->user_id);
        $plan = Plan::find($order->plan_id);
        
        $order->user_email = $user ? $user->email : null;
        $order->plan_name = $plan ? $plan->name : null;

        return response()->json([
            'data' => $order
        ]);
    }

    /**
     * Get order status summary
     */
    public function summary(Request $request)
    {
        $staffUserId = $request->input('user.id');
        
        $summary = Order::where('invite_user_id', $staffUserId)
            ->select('status', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as amount'))
            ->groupBy('status')
            ->get();

        $statusMap = [
            0 => 'pending',
            1 => 'processing', 
            2 => 'cancelled',
            3 => 'completed',
            4 => 'discounted'
        ];

        $result = [];
        foreach ($summary as $item) {
            $statusName = isset($statusMap[$item->status]) ? $statusMap[$item->status] : 'unknown';
            $result[$statusName] = [
                'count' => $item->count,
                'amount' => $item->amount ?: 0
            ];
        }

        return response()->json([
            'data' => $result
        ]);
    }

    /**
     * Assign order to user (staff can only assign orders for plans they have access to)
     */
    public function assign(OrderAssign $request)
    {
        $staffUserId = $request->input('user.id');
        $plan = Plan::find($request->input('plan_id'));
        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            abort(500, 'User does not exist');
        }

        if (!$plan) {
            abort(500, 'Plan does not exist');
        }

        // Get staff info to check plan access
        $staff = Staff::where('user_id', $staffUserId)->first();
        if (!$staff) {
            abort(500, 'Staff not found');
        }

        // Check if staff has access to this plan
        $staffPlanIds = $staff->plan_id ?: [];
        if (!empty($staffPlanIds) && !in_array($plan->id, $staffPlanIds)) {
            abort(500, 'You do not have permission to assign this plan');
        }

        // Validate period has pricing
        $period = $request->input('period');
        if ($plan[$period] === null || $plan[$period] <= 0) {
            abort(500, 'This plan does not have pricing for the selected period');
        }

        $userService = new UserService();
        if ($userService->isNotCompleteOrderByUserId($user->id)) {
            abort(500, 'This user has pending orders, cannot assign new order');
        }

        DB::beginTransaction();
        $order = new Order();
        $orderService = new OrderService($order);
        $order->user_id = $user->id;
        $order->plan_id = $plan->id;
        $order->period = $period;
        $order->trade_no = Helper::guid();
        
        // Auto-calculate total_amount from plan period pricing
        $order->total_amount = $plan[$period];

        // Set order type based on user status and plan
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
            abort(500, 'Failed to create order');
        }

        DB::commit();

        return response([
            'data' => $order->trade_no
        ]);
    }
}
