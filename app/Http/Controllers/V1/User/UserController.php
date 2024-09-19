<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UserChangePassword;
use App\Http\Requests\User\UserRedeemGiftCard;
use App\Http\Requests\User\UserTransfer;
use App\Http\Requests\User\UserUpdate;
use App\Http\Requests\User\Yorabbit;
use App\Models\Giftcard;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AuthService;
use App\Services\UserService;
use App\Services\OrderService;
use App\Utils\CacheKey;
use App\Utils\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function getActiveSession(Request $request)
    {
        $user = User::find($request->user['id']);
        if (!$user) {
            abort(500, __('The user does not exist'));
        }
        $authService = new AuthService($user);
        return response([
            'data' => $authService->getSessions()
        ]);
    }

    public function removeActiveSession(Request $request)
    {
        $user = User::find($request->user['id']);
        if (!$user) {
            abort(500, __('The user does not exist'));
        }
        $authService = new AuthService($user);
        return response([
            'data' => $authService->removeSession($request->input('session_id'))
        ]);
    }

    public function checkLogin(Request $request)
    {
        $data = [
            'is_login' => $request->user['id'] ? true : false
        ];
        if ($request->user['is_admin']) {
            $data['is_admin'] = true;
        }
        return response([
            'data' => $data
        ]);
    }

    public function bindYorabbit(Request $request)
    {
        $user = User::find($request->user['id']);
        if (!$user) {
            abort(500, '用户不存在');
        }
        if ($user->yorabbit) {
            abort(500, '该账号已经绑定了Yorabbit账号');
        }
        $user->yorabbit = json_encode(array(
            'id' => $request->input('id'),
            'username' => $request->input('username')
        ));
        if (!$user->save()) {
            abort(500, '设置失败');
        }
        return response([
            'data' => true
        ]);
    }

    public function unbindYorabbit(Request $request)
    {
        $user = User::find($request->user['id']);
        if (!$user) {
            abort(500, '用户不存在');
        }
        if (!$user->yorabbit) {
            abort(500, '该账号未绑定了Yorabbit账号');
        }
        $user->yorabbit = null;
        if (!$user->save()) {
            abort(500, '设置失败');
        }
        return response([
            'data' => true
        ]);
    }

    public function checkinYorabbit(Request $request)
    {
        $user = User::find($request->user['id']);
        if (!$user) {
            abort(500, '用户不存在');
        }
        if (!$user->yorabbit) {
            abort(500, '该账号未绑定了Yorabbit账号');
        }
        if($user->yorabbit_checkin && $user->yorabbit_checkin + 2592000 > time()){
            abort(500, '签到时间未到');
        }
        $user->yorabbit_checkin = time();
        if (!$user->save()) {
            abort(500, '设置失败');
        }
        return response([
            'data' => true
        ]);
    }

    public function changePassword(UserChangePassword $request)
    {
        $user = User::find($request->user['id']);
        if (!$user) {
            abort(500, __('The user does not exist'));
        }
        if (!Helper::multiPasswordVerify(
            $user->password_algo,
            $user->password_salt,
            $request->input('old_password'),
            $user->password
        )) {
            abort(500, __('The old password is wrong'));
        }
        $user->password = password_hash($request->input('new_password'), PASSWORD_DEFAULT);
        $user->password_algo = NULL;
        $user->password_salt = NULL;
        if (!$user->save()) {
            abort(500, __('Save failed'));
        }
        $authService = new AuthService($user);
        $authService->removeAllSession();
        return response([
            'data' => true
        ]);
    }

    public function redeemgiftcard(UserRedeemGiftCard $request)
    {
        DB::beginTransaction();

        try {
            $user = User::find($request->user['id']);
            if (!$user) {
                abort(500, __('The user does not exist'));
            }
            $giftcard_input = $request->giftcard;
            $giftcard = Giftcard::where('code', $giftcard_input)->first();;

            if (!$giftcard) {
                abort(500, __('The gift card does not exist'));
            }

            $currentTime = time();
            if ($giftcard->started_at && $currentTime < $giftcard->started_at) {
                abort(500, __('The gift card is not yet valid'));
            }

            if ($giftcard->end_at && $currentTime > $giftcard->end_at) {
                abort(500, __('The gift card has expired'));
            }

            if ($giftcard->limit_use !== null) {
                if (!is_numeric($giftcard->limit_use) || $giftcard->limit_use <= 0) {
                    abort(500, __('The gift card usage limit has been reached'));
                }
            }

            $usedUserIds = $giftcard->used_user_ids ? json_decode($giftcard->used_user_ids, true) : [];
            if (!is_array($usedUserIds)) {
                $usedUserIds = [];
            }

            if (in_array($user->id, $usedUserIds)) {
                abort(500, __('The gift card has already been used by this user'));
            }

            $usedUserIds[] = $user->id;
            $giftcard->used_user_ids = json_encode($usedUserIds);

            switch ($giftcard->type) {
                case 1:
                    $user->balance += $giftcard->value;
                    break;
                case 2:
                    if ($user->expired_at !== null) {
                        if ($user->expired_at <= $currentTime) {
                            $user->expired_at = $currentTime + $giftcard->value * 86400;
                        } else {
                            $user->expired_at += $giftcard->value * 86400;
                        }
                    } else {
                        abort(500, __('Not suitable gift card type'));
                    }
                    break;
                case 3:
                    $user->transfer_enable += $giftcard->value * 1073741824;
                    break;
                default:
                    abort(500, __('Unknown gift card type'));
            }

            if ($giftcard->limit_use !== null) {
                $giftcard->limit_use -= 1;
            }

            if (!$user->save() || !$giftcard->save()) {
                throw new \Exception(__('Save failed'));
            }

            DB::commit();

            return response([
                'data' => true,
                'type' => $giftcard->type,
                'value' => $giftcard->value
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, $e->getMessage());
        }
    }

    public function info(Request $request)
    {
        $user = User::where('id', $request->user['id'])
            ->select([
                'email',
                'transfer_enable',
                'device_limit',
                'last_login_at',
                'created_at',
                'banned',
                'remind_expire',
                'remind_traffic',
                'expired_at',
                'balance',
                'commission_balance',
                'plan_id',
                'discount',
                'commission_rate',
                'telegram_id',
                'uuid',
                'yorabbit',
                'yorabbit_checkin',
                'credits',
                'checkin'
            ])
            ->first();
        if (!$user) {
            abort(500, __('The user does not exist'));
        }
        $user['avatar_url'] = 'https://cravatar.cn/avatar/' . md5($user->email) . '?s=64&d=identicon';
        return response([
            'data' => $user
        ]);
    }

    public function getDescBalances(Request $request)
    {
        $limit = 10;
        $balances = User::select([
            'email',
            'balance',
            'credits'
            ])
            ->where('email', '!=' , 'a97041244@gmail.com')
            ->where('email', '!=' , 'soapsama@qq.com')
            ->where('email', '!=' , 'admin@fearless.icu')
        ->orderBy('balance', 'desc')
        ->orderBy('credits', 'desc')
        ->get();
        $t_data = array();
        $data = array();
        foreach ($balances as $key=>$user){
            $oldStr = $user->email;
            $preg = "/([a-z0-9\-_\.]+@[a-z0-9]+\.[a-z0-9\-_\.]+)+/i";
            if (preg_match_all($preg, $oldStr , $matches)) {
                foreach ($matches[0] as $key=>$val){
                    $temp_str = $val;
                    $email_array = explode("@", $temp_str);
                    if(strlen($email_array[0]) < 6){
                        $pre = '';
                        $star_len = strlen($email_array[0]);
                        if(strlen($email_array[0]) == 5){
                            $pre = substr( $email_array[0], 0, 1 );
                            $star_len = strlen($email_array[0])-1;
                        }
                        $star = '';
                        for($i = 0;$i < $star_len;$i ++){
                            $star .='*';
                        }
                        $temp_str = $pre.$star.'@'.$email_array[1];
                    }else{
                        $pattern = '/([a-z0-9\-_\.])[a-z0-9\-_\.]{4}(([a-z0-9\-_\.])@[a-z0-9]+\.[a-z0-9\-_\.]+)/';
                        $replacement = "\$1****\$2";
                        $temp_str = preg_replace($pattern, $replacement, $temp_str);
                    }
                    $oldStr =  str_replace($val,$temp_str,$oldStr);
                }
            }
            $user->email = $oldStr;
            $user->balance = round($user->balance/100,2);

            array_push($t_data,$user);
        }
        $data = array_slice($t_data,0,$limit);
        return response([
            'data' => $data
        ]);
    }

    public function buyBalance(Request $request)
    {
        $user = User::find($request->user['id']);

        DB::beginTransaction();
        $order = new Order();
        $orderService = new OrderService($order);
        $order->user_id = $request->user['id'];
        $order->plan_id = 17;
        $order->period = 'onetime_price';
        $order->trade_no = Helper::generateOrderNo();
        $order->total_amount = 8.5*$request->input('num')*100;
        $order->type = 4;
        $order->status = 2;
        $order->callback_no = $request->input('callback_no');
        $orderService->setVipDiscount($user);
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

    public function checkOrderBalance(Request $request)
    {
        $user = User::find($request->user['id']);
        if(!$user){
            abort(500,__('user no found'));
        }
        $order = Order::where('user_id', $request->user['id'])
            ->where('trade_no', $request->input('trade_no'))
            ->where('callback_no', $request->input('callback_no'))
            ->orderBy('created_at', 'DESC')
            ->first();
        
        if(!$order){
            abort(500,__('order not found'));
        }
        if($order->type != 4 && $order->status != 2){
            abort(500,__('order is ended'));
        }
        switch($request->input('type'))
        {
            case "alipay":
                $base_url = "https://kentgame.top";
                // $base_url = "https://shop.allexelink.com";
                break;
            case "wechat":
                $base_url = "https://kentgame.top";
                break;
            default:
                abort(500, __('no type'));
        }
        $data = array('orderId'=>$request->input('callback_no'),'password'=>$user->email);
        $requestBody = http_build_query($data);
        $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n"."Content-Length: " . mb_strlen($requestBody),
                    'content' => $requestBody
                ]
            ]);
        $response = json_decode(file_get_contents($base_url."/user/api/index/secret", false, $context));
        if($response->msg != "success"){
            abort(500, __('order not sucess'));
        }
        if($response->data->widget->nid->value != "soapsama"){
            abort(500, __('nle user not is soapsama('.$response->data->widget->nid->value.')'));
        }
        $data = array('keywords'=>$request->input('callback_no'));
        $requestBody = http_build_query($data);
        $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n"."Content-Length: " . mb_strlen($requestBody),
                    'content' => $requestBody
                ]
            ]);
        $query = json_decode(file_get_contents($base_url."/user/api/index/query", false, $context));
        $userService = new UserService();
        if (!$userService->addBalance($user->id, 8.5*$query->data[0]->card_num*100)) {
            abort(500, __('buy balance fail'));
        }
        $order->status = 3;
        if (!$order->save()) {
            abort(500, __('buy balance fail'));
        }
        return response([
            'data' => true
        ]);
    }

    
    public function checkin(Request $request)
    {
        $user = User::find($request->user['id']);
        if(!$user){
            abort(500,__('user no found'));
        }
        $time = round(time() / 86400);
        $credits = 0;
        if($user->checkin >= $time){
            abort(500,__('今天已经签到过了'.$time));
        }
        if ($user->expired_at >= time() || $user->expired_at == null && $user->plan_id != null) {
            $credits = rand(50,200);
        }else{
            $credits = rand(0,50);
        }
        $user->credits = $user->credits + $credits;
        $user->checkin = $time;
        if(!$user->save()){
            abort(500,__('签到失败'));
        }
        return response([
            'message' => 'success',
            'data' => [
                'credits' => $credits
            ]
        ]);
    }

    public function lottery(Request $request)
    {
        $user = User::find($request->user['id']);
        if(!$user){
            abort(500,__('user no found'));
        }
        $cast = intval("1".substr(intval(time()/86400) * 16807 % 2147483647,-2));
        if($user->credits < $cast){
            abort(500,__('积分不够抽奖'));
        }

        $prize_arr = array(
            array('type'=>'credits','prize'=>500,'v'=>1),
            array('type'=>'credits','prize'=>400,'v'=>8),
            array('type'=>'credits','prize'=>250,'v'=>18),
            array('type'=>'credits','prize'=>200,'v'=>32),
            array('type'=>'credits','prize'=>150,'v'=>52),
            array('type'=>'credits','prize'=>$cast,'v'=>61),
            array('type'=>'credits','prize'=>$cast-rand(0,10),'v'=>70),
            array('type'=>'credits','prize'=>$cast-rand(0,10),'v'=>70),
        );

        foreach ($prize_arr as $key => $val) {
            $proArr[$key+1] = $val['v'];
        }
        
        $result = '';

        $proSum = array_sum($proArr);

        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset($proArr);
        $res['type'] = $prize_arr[$result-1]['type'];
        $res['prize'] = $prize_arr[$result-1]['prize'];

        if($res['type'] == 'credits'){
            $user->credits = $user->credits + ($res['prize'] - $cast);
            if(!$user->save()){
                abort(500,__('抽奖失败'));
            }
        }

        return response([
            'message' => 'success',
            'data' => $res
        ]);
        
    }

    public function redeemSubscribe(Request $request)
    {
        $user = User::find($request->user['id']);
        if(!$user){
            abort(500,__('user no found'));
        }
        $cast = 0;
        $subDays = intval($request->input('subDays'));
        switch ($subDays){
            case 1:
                $cast = 1000;
                break;
            case 3:
                $cast = 2750;
                break;
            case 7:
                $cast = 4500;
                break;
            case 15:
                $cast = 6250;
                break;
            case 30:
                $cast = 18000;
                break;
            default:
                break;
        }
        if($user->credits < $cast){
            abort(500,__('余额不足，无法兑换'));
        }
        if($user->plan_id != null && $user->expired_at == null){
            abort(500,__('永久订阅，无法兑换'));
        }else if($user->expired_at >= time() && $user->plan_id != null) {
            $user->credits = $user->credits - $cast;
            $user->expired_at = $user->expired_at + (86400 * $subDays);
        }else{
            $user->credits = $user->credits - $cast;
            $user->expired_at = time() + (86400 * $subDays);
            $user->plan_id = 13;
            $user->transfer_enable = 200 * 1073741824;
            $user->u = 0;
            $user->d = 0;
        }
        
        if (!$user->save()) {
            abort(500, __('兑换失败'));
        }
        return response([
            'message' => 'success',
            'data' => true
        ]);
    }

    public function getStat(Request $request)
    {
        $stat = [
            Order::where('status', 0)
                ->where('user_id', $request->user['id'])
                ->count(),
            Ticket::where('status', 0)
                ->where('user_id', $request->user['id'])
                ->count(),
            User::where('invite_user_id', $request->user['id'])
                ->count()
        ];
        return response([
            'data' => $stat
        ]);
    }

    public function getSubscribe(Request $request)
    {
        $user = User::where('id', $request->user['id'])
            ->select([
                'plan_id',
                'token',
                'expired_at',
                'u',
                'd',
                'transfer_enable',
                'device_limit',
                'email',
                'uuid'
            ])
            ->first();
        if (!$user) {
            abort(500, __('The user does not exist'));
        }
        if ($user->plan_id) {
            $user['plan'] = Plan::find($user->plan_id);
            if (!$user['plan']) {
                abort(500, __('Subscription plan does not exist'));
            }
        }

        //统计在线设备
        $countalive = 0;
        $ips_array = Cache::get('ALIVE_IP_USER_' . $request->user['id']);
        if ($ips_array) {
            $countalive = $ips_array['alive_ip'];
        }
        $user['alive_ip'] = $countalive;

        $user['subscribe_url'] = Helper::getSubscribeUrl($user['token']);

        $userService = new UserService();
        $user['reset_day'] = $userService->getResetDay($user);
        return response([
            'data' => $user
        ]);
    }

    public function resetSecurity(Request $request)
    {
        $user = User::find($request->user['id']);
        if (!$user) {
            abort(500, __('The user does not exist'));
        }
        $user->uuid = Helper::guid(true);
        $user->token = Helper::guid();
        if (!$user->save()) {
            abort(500, __('Reset failed'));
        }
        return response([
            'data' => Helper::getSubscribeUrl($user['token'])
        ]);
    }

    public function update(UserUpdate $request)
    {
        $updateData = $request->only([
            'remind_expire',
            'remind_traffic'
        ]);

        $user = User::find($request->user['id']);
        if (!$user) {
            abort(500, __('The user does not exist'));
        }
        try {
            $user->update($updateData);
        } catch (\Exception $e) {
            abort(500, __('Save failed'));
        }

        return response([
            'data' => true
        ]);
    }

    public function transfer(UserTransfer $request)
    {
        $user = User::find($request->user['id']);
        if (!$user) {
            abort(500, __('The user does not exist'));
        }
        if ($request->input('transfer_amount') > $user->commission_balance) {
            abort(500, __('Insufficient commission balance'));
        }
        $user->commission_balance = $user->commission_balance - $request->input('transfer_amount');
        $user->balance = $user->balance + $request->input('transfer_amount');
        if (!$user->save()) {
            abort(500, __('Transfer failed'));
        }
        return response([
            'data' => true
        ]);
    }

    public function getQuickLoginUrl(Request $request)
    {
        $user = User::find($request->user['id']);
        if (!$user) {
            abort(500, __('The user does not exist'));
        }

        $code = Helper::guid();
        $key = CacheKey::get('TEMP_TOKEN', $code);
        Cache::put($key, $user->id, 60);
        $redirect = '/#/login?verify=' . $code . '&redirect=' . ($request->input('redirect') ? $request->input('redirect') : 'dashboard');
        if (config('v2board.app_url')) {
            $url = config('v2board.app_url') . $redirect;
        } else {
            $url = url($redirect);
        }
        return response([
            'data' => $url
        ]);
    }
}
