<?php

namespace App\Http\Controllers\V1\externalAPI;

use App\Http\Controllers\Controller;
use App\Services\ServerService;
use App\Models\User;
use App\Models\InviteCode;
use Illuminate\Http\Request;
class externalAPIController extends Controller
{
        public function Invitationcode(Request $request)
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
    }

    public function Accountbanquery(Request $request)
    {
        $email = $request->query('Email');
        $user = User::where('email', $email)->first();
        if (!$user) {
            abort(500, '用户不存在');
        }
        $Accountbanquery = User::where('email', $user->email)->first();
        if ($Accountbanquery) {
            return response()->json([
                'data' => [
                    '封号原因' => $Accountbanquery->remarks,
                ]
            ]);
        } else {
            return response()->json([
                'data' => [
                    '封号原因' => null,
                ]
            ]);
        }
}
 
}