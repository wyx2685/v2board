<?php

namespace App\Http\Controllers\V1\Staff;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\StaffAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlanController extends Controller
{
    private $access;

    public function __construct(StaffAccessService $access)
    {
        $this->access = $access;
    }

    public function fetch(Request $request)
    {
        $counts = $this->access->users($request->input('user.id'))->select(
            DB::raw("plan_id"),
            DB::raw("count(*) as count")
        )
            ->where('plan_id', '!=', NULL)
            ->where(function ($query) {
                $query->where('expired_at', '>=', time())
                    ->orWhere('expired_at', NULL);
            })
            ->groupBy("plan_id")
            ->get();
        $plans = Plan::orderBy('sort', 'ASC')->get();
        foreach ($plans as $k => $v) {
            $plans[$k]->count = 0;
            foreach ($counts as $kk => $vv) {
                if ($plans[$k]->id === $counts[$kk]->plan_id) $plans[$k]->count = $counts[$kk]->count;
            }
        }
        return response([
            'data' => $plans
        ]);
    }
}
