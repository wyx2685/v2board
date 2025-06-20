<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Models\StatUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatController extends Controller
{
    public function getTrafficLog(Request $request)
    {
        $userId = $request->user['id'];
    
        $data = StatUser::where('user_id', $userId)
            ->where('record_at', '>=', strtotime(date('Y-m-1')))
            ->orderBy('record_at', 'DESC')
            ->selectRaw('
                CAST(u * server_rate AS UNSIGNED)   AS u,
                CAST(d * server_rate AS UNSIGNED)   AS d,
                record_at,
                user_id,
                1                                   AS server_rate
            ')
            ->get();
    
        return response(['data' => $data]);
    }

}
