<?php

namespace App\Http\Controllers\V1\externalAPI;

use App\Http\Controllers\Controller;
use App\Services\ServerService;
use App\Models\User;
use App\Models\InviteCode;
use Illuminate\Http\Request;
class externalAPIController extends Controller
{
        /*public function Invitationcode(Request $request)
        {
            $email = $request->query('Email');
            $user = User::where('email', $email)->first();
            if (!$user) {
                abort(500, '用户不存在');
            }
            $userInvitationcode = InviteCode::where('user_id', $user->id)->first();
            if ($userInvitationcode) {
                return response()->json([
                    'data' => [
                        'code' => $userInvitationcode->code,
                    ]
                ]);
            } else {
                return response()->json([
                    'data' => [
                        'code' => null,
                    ]
                ]);
            }
    }*/

    public function Accountbanquery(Request $request)
    {
        $email = $request->query('Email');
        $user = User::where('email', $email)->first();
        if (!$user) {
            abort(500, '用户不存在');
        }
        $Accountbanquery = User::where('email', $user->email)->first(); 
        if ($Accountbanquery->banned==1) 
        {
            if(empty($Accountbanquery->remarks))
            {
                return response()->json([
                    'data' => [
                        'Reasonforaccountsuspension' =>'违规严重,不给予解禁.',
                    ]
                ]);
            }
            if (!empty($Accountbanquery->Unban) && $Accountbanquery->Unban != 0) 
            {
                
                if($Accountbanquery->bantime)
                {

                $Accountbanquery->Unban = $Accountbanquery->Unban - 1;
                $Accountbanquery->banned = 0;
                $Accountbanquery->remarks = '';
                $Accountbanquery->bantime = null;
                $Accountbanquery->save();
                        return response()->json([
                            'data' => [
                                'Reasonforaccountsuspension' =>'已解除封禁,请珍惜账户',
                            ]
                        ]);
                    }
            }
            else
            {
                return response()->json([
                    'data' => [
                        'Reasonforaccountsuspension' => $Accountbanquery->remarks . "\n重复违规,无法解禁.",
                    ]
                ]);
            }
           // $userInvitationcode = InviteCode::where('user_id', $user->id)->first();
          /*  return response()->json([
                'data' => [
                    'ban' => $Accountbanquery->banned,//封禁状态
                    'Reasonforaccountsuspension' => $Accountbanquery->remarks,//封禁理由
                    'Invitationcode' => $userInvitationcode ? '邀请码记录: ' . $userInvitationcode->code : '没有邀请码记录',//邀请码

                ]
            ]);*/
        } 
        else {
            return response()->json([
                'data' => [
                    'Reasonforaccountsuspension' => '正常',//正常
                ]
                 ]);
  }
}
 
}
