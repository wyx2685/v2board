<?php

namespace App\Http\Controllers\V1\Staff;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Plan;
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
}
