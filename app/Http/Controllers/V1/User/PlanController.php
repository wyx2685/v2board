<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Models\Staff;
use App\Services\PlanService;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function fetch(Request $request)
    {
        $user = User::find($request->user['id']);

        $domain = $request->header('host');
        $staff = Staff::where('domain', $domain)->first();

        $isWebCon = $staff && !empty($staff->plan_id);

        if ($request->input('id')) {
            $planId = intval($request->input('id'));
            $plan = Plan::where('id', $planId)->first();
            if (!$plan) {
                abort(500, __('Subscription plan does not exist'));
            }

            if ($isWebCon) {
                if (!in_array($plan->id, $staff->plan_id)) {
                    abort(500, __('Subscription plan does not exist'));
                }
            } else {
                if ((!$plan->show && !$plan->renew) || (!$plan->show && $user->plan_id !== $plan->id)) {
                    abort(500, __('Subscription plan does not exist'));
                }
            }

            return response([
                'data' => $plan
            ]);
        }

        $counts = PlanService::countActiveUsers();

        if ($isWebCon) {
            $plans = Plan::whereIn('id', $staff->plan_id)
                ->orderBy('sort', 'ASC')
                ->get()->map(function ($plan) {
                    $plan->show = 1;
                    return $plan;
                });
        } else {
            $plans = Plan::where('show', 1)
                ->orderBy('sort', 'ASC')
                ->get();
        }

        foreach ($plans as $k => $v) {
            if ($plans[$k]->capacity_limit === NULL) continue;
            if (!isset($counts[$plans[$k]->id])) continue;
            $plans[$k]->capacity_limit = $plans[$k]->capacity_limit - $counts[$plans[$k]->id]->count;
        }

        return response([
            'data' => $plans
        ]);
    }
}
