<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Protocols\General;
use App\Services\ServerService;
use App\Services\UserService;
use App\Utils\Helper;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function subscribe(Request $request)
    {
        $flag = $request->input('flag')
            ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $flag = strtolower($flag);
        $user = $request->user;
        // account not expired and is not banned.
        $userService = new UserService();
        if ($userService->isAvailable($user)) {
            $serverService = new ServerService();
            $servers = $serverService->getAvailableServers($user);
            
            // MeT 20240823 Start
            $subInfoBlacklist = ['surge', 'surfboard', 'stash', 'shadowrocket', 'quantumultx', 'loon', 'clashmeta', 'clash', 'sing-box', 'singbox', 'sing'];
            $showSub = true;
            foreach ($subInfoBlacklist as $subName) {
                if (strpos($flag, $subName) !== false) {
                    $showSub = false;
                    break;
                }
            }
            if ($showSub) {
                $this->setSubscribeInfoToServers($servers, $user);
            }
            // MeT 20240823 End
            
            if ($flag) {
                foreach (array_reverse(glob(app_path('Protocols') . '/*.php')) as $file) {
                    $file = 'App\\Protocols\\' . basename($file, '.php');
                    $class = new $file($user, $servers);
                    if (strpos($flag, $class->flag) !== false) {
                        return $class->handle();
                    }
                }
            }
            $class = new General($user, $servers);
            return $class->handle();
        }
    }
    private function setSubscribeInfoToServers(&$servers, $user)
    {
        if (!isset($servers[0])) return;
        if (!(int)config('v2board.show_info_to_server_enable', 0)) return;
        $useTraffic = $user['u'] + $user['d'];
        $totalTraffic = $user['transfer_enable'];
        $remainingTraffic = Helper::trafficConvert($totalTraffic - $useTraffic);
        $expiredDate = $user['expired_at'] ? date('Y-m-d', $user['expired_at']) : '长期有效';
        $userService = new UserService();
        $resetDay = $userService->getResetDay($user);
        array_unshift($servers, array_merge($servers[0], [
            'name' => "🚀套餐到期：{$expiredDate}",
            'host' => '127.0.0.1',
            'port' => 10086,
        ]));
        if ($resetDay) {
            array_unshift($servers, array_merge($servers[0], [
                'name' => "🚀距离下次重置剩余：{$resetDay} 天",
                'host' => '127.0.0.1',
                'port' => 10086,
            ]));
        }
        array_unshift($servers, array_merge($servers[0], [
            'name' => "🚀剩余流量：{$remainingTraffic}",
            'host' => '127.0.0.1',
            'port' => 10086,
        ]));
        array_unshift($servers, array_merge($servers[0], [
            'name' => "🚀FassCloud | https://dash.metc.uk/",
            'host' => '127.0.0.1',
            'port' => 10086,
        ]));
    }
}
