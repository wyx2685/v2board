<?php

namespace App\Http\Controllers\V1\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Plan;
use App\Models\Order;
use App\Models\Staff;
use App\Utils\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function info(Request $request)
    {
        $id = $request->input('user.id');
        if (!$id) {
            return response()->json(['message' => 'ID not provided'], 400);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Check if user is staff
        if (!$user->is_staff) {
            return response()->json(['message' => 'Access denied: Not a staff member'], 403);
        }

        // Check if staff record exists and is active
        $staff = Staff::where('user_id', $user->id)->where('status', 1)->first();
        if (!$staff) {
            return response()->json(['message' => 'Staff account not activated'], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'is_staff' => $user->is_staff,
                'balance' => $user->balance,
                'commission_balance' => $user->commission_balance,
                'commission_rate' => $user->commission_rate ?? config('v2board.invite_commission', 10),
                'discount' => $user->discount ?? "0",
                'staff' => [
                    'domain' => $staff->domain,
                    'title' => $staff->title,
                    'status' => $staff->status
                ]
            ]
        ]);
    }

    public function stat(Request $request)
    {
        $id = $request->input('user.id');
        return response()->json([
            'today_income' => Order::where('created_at', '>=', strtotime(date('Y-m-d')))
                    ->where('created_at', '<', time())
                    ->whereNotIn('status', [0, 2])
                    ->where('invite_user_id', $id)
                    ->sum('total_amount'),
            'month_income' => Order::where('created_at', '>=', strtotime(date('Y-m-1')))
                    ->where('created_at', '<', time())
                    ->whereNotIn('status', [0, 2])
                    ->where('invite_user_id', $id)
                    ->sum('total_amount'),
            'new_users' => (int)User::where('invite_user_id', $request->user['id'])->count(),
        ]);
    }
    
    public function config(Request $request)
    {
        $userId = $request->input('user.id');
        if (!$userId) {
            return response()->json(['message' => 'ID not provided'], 400);
        }

        $staff = Staff::where('user_id', $userId)->first();
        if (!$staff) {
            return response()->json(['message' => 'Staff not found'], 404);
        }
        $User = User::where('id', $userId)->first();

        return response()->json([
            'email' => $User->email,
            'domain' => $staff->domain,
            'title' => $staff->title,
            'description' => $staff->description,
            'logo' => $staff->logo,
            'background_url' => $staff->background_url,
            'custom_html' => $staff->custom_html,
        ]);
    }

    public function configSave(Request $request)
    {
        $userId = $request->input('user.id');
        if (!$userId) {
            return response()->json(['message' => 'ID not provided'], 400);
        }

        $staff = Staff::where('user_id', $userId)->first();
        if (!$staff) {
            return response()->json(['message' => 'Staff not found'], 404);
        }

        $data = $request->only([
            'title',
            'description',
            'logo',
            'background_url',
            'custom_html',
        ]);

        $validator = \Validator::make($data, [
            'logo' => 'nullable|url',
            'background_url' => 'nullable|url',
        ], [
            'logo.url' => 'Logo phải là một URL hợp lệ.',
            'background_url.url' => 'Hình nền phải là một URL hợp lệ.',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 400);
        }
    
        $staff->update($data);
    
        return response()->json(['status' => 'success']);
    }
    
    public function finduser(Request $request)
    {
        $userId = $request->input('user.id');
        if (!$userId) {
            return response()->json(['message' => 'ID not provided'], 400);
        }
    
        $current = $request->input('page') ? $request->input('page') : 1;
        $pageSize = $request->input('limit') >= 10 ? $request->input('limit') : 10;
        $sortType = in_array($request->input('sort_type'), ['ASC', 'DESC']) ? $request->input('sort_type') : 'DESC';
        $sort = $request->input('sort') ? $request->input('sort') : 'created_at';
    
        $userModel = User::select('*')
            ->where('invite_user_id', $userId)
            ->orderBy($sort, $sortType);

        if ($request->has('email')) {
            $email = $request->input('email');
            $userModel->where('email', 'like', '%' . $email . '%');
        }
    
        if ($request->has('id')) {
            $searchId = $request->input('id');
            $userModel->where('id', $searchId);
        }
    
        $total = $userModel->count();
        $res = $userModel->forPage($current, $pageSize)->get();
    
        $plan = Plan::get();
    
        for ($i = 0; $i < count($res); $i++) {
            for ($k = 0; $k < count($plan); $k++) {
                if ($plan[$k]['id'] == $res[$i]['plan_id']) {
                    $res[$i]['plan_name'] = $plan[$k]['name'];
                }
            }
    
            $countalive = 0;
            $ips = [];
            $ips_array = Cache::get('ALIVE_IP_USER_' . $res[$i]['id']);
            if ($ips_array) {
                $countalive = $ips_array['alive_ip'] ?? 0;
                foreach ($ips_array as $nodetypeid => $data) {
                    if (!is_int($data) && isset($data['aliveips'])) {
                        foreach ($data['aliveips'] as $ip_NodeId) {
                            $ip = explode("_", $ip_NodeId)[0];
                            $ips[] = $ip . '_' . $nodetypeid;
                        }
                    }
                }
            }
    
            $res[$i]['alive_ip'] = $countalive;
            $res[$i]['ips'] = implode(', ', $ips);
            $res[$i]['subscribe_url'] = Helper::getSubscribeUrl($res[$i]['token']);
        }
    
        return response()->json([
            'data' => $res,
            'success' => true,
            'total' => $total,
            'current' => (int)$current,
            'pageSize' => (int)$pageSize,
        ]);
    }


}
