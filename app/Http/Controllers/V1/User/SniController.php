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

        $configPath = config_path('sni.php');

        if (!file_exists($configPath)) {
            $defaultSni = [
                [
                    'id' => 1,
                    'name_sni' => 'Default SNI',
                    'network_settings' => 'Default Setting',
                    'content' => 'Default Content for SNI'
                ]
            ];

            $configContent = "<?php\n\nreturn " . var_export(['sni' => $defaultSni], true) . ";\n";

            file_put_contents($configPath, $configContent);
        }

        $sniData = config('sni.sni', []);

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