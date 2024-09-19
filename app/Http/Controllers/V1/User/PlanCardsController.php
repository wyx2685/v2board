<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PlanCards;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanCardsController extends Controller
{

    public function redeemCard(Request $request)
    {
        $user = User::find($request->user['id']);
        $card = PlanCards::where('code',$request->input('card'))->first();
        if (!$card) {
            abort(500, __('这个订阅卡不存在'));
        }
        if ($card->status != 0){
            abort(500, __('这个订阅卡已使用'));
        }
        if (!$user) {
            abort(500, __('用户不存在'));
        }
        if ($user->expired_at >= time() || $user->expired_at == null && $user->plan_id != null) {
            abort(500, __('用户已经有订阅了'));
        }

        $plan = Plan::find($card->plan_id);

        if (!$plan) {
            abort(500, __('这个订阅不存在'));
        }
        
        $card->status = 3;
        $card->active_user_id = $user->id;
        $user->plan_id = $card->plan_id;
        $user->expired_at = time() + $card->time * 3600;
        $user->transfer_enable = $plan->transfer_enable * 1073741824;
        $user->u = 0;
        $user->d = 0;
        if (!$card->save()) {
            abort(500, __('兑换失败，请重新尝试'));
        }
        if (!$user->save()) {
            abort(500, __('兑换失败，请重新尝试'));
        }
        return response([
            'message' => 'success',
            'data' => [
                'name' => $plan->name,
                'transfer_enable' => $plan->transfer_enable,
                'time' => $card->time
                
            ]
        ]);
    }
}