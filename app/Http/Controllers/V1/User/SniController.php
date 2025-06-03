<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class SniController extends Controller
{
    public function fetchSni(Request $request)
    {
        $current = $request->input('current') ? $request->input('current') : 1;
        $pageSize = 7;

        $sniData = [
            [
                'id' => 1,
                'name_sni' => 'SNI 1',
                'network_settings' => 'Setting 1',
                'content' => 'Content for SNI 1'
            ],
            [
                'id' => 2,
                'name_sni' => 'SNI 2',
                'network_settings' => 'Setting 2',
                'content' => 'Content for SNI 2'
            ],
            [
                'id' => 3,
                'name_sni' => 'SNI 3',
                'network_settings' => 'Setting 3',
                'content' => 'Content for SNI 3'
            ],
            [
                'id' => 4,
                'name_sni' => 'SNI 4',
                'network_settings' => 'Setting 4',
                'content' => 'Content for SNI 4'
            ]
        ];

        $total = count($sniData);
        $offset = ($current - 1) * $pageSize;
        $res = array_slice($sniData, $offset, $pageSize);

        return response([
            'data' => $res,
            'total' => $total
        ]);
    }
    public function changeSNI(Request $request)
    {
        
        $dname_sni = $request->input('name_sni');
        $network_settings = $request->input('network_settings');

        $user = User::find($request->user['id']);
        if (!$user) {
            abort(500, __('The user does not exist'));
        }

        
        $user->name_sni = $dname_sni;
        $user->network_settings = $network_settings;
        if (!$user->save()) {
            abort(500, __('Update failed'));
        }

        return response([
            'data' => true,
            'message' => __('Cập Nhật SNI Thành Công')
        ]);
    }
}