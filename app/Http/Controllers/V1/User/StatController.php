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

        $builder = StatUser::selectRaw(
            'u * server_rate AS u',
            'd * server_rate AS d',
            'record_at',
            'user_id',
            DB::raw('1 AS server_rate')
        )
        ->where('user_id', $userId)
        ->where('record_at', '>=', strtotime(date('Y-m-1')))
        ->orderBy('record_at', 'DESC');

        $data = $builder->get();
        return response(['data' => $data]);
    }
}
