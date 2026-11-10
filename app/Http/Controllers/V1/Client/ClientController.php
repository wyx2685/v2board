<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Protocols\Clash;
use App\Protocols\ClashMeta;
use App\Protocols\ClashNyanpasu;
use App\Protocols\ClashVerge;
use App\Protocols\General;
use App\Protocols\Singbox\Singbox;
use App\Protocols\Singbox\SingboxOld;
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
            $protocol = null;
            if ($flag) {
                if (!strpos($flag, 'sing')) {
                    $this->setSubscribeInfoToServers($servers, $user);
                    foreach (array_reverse(glob(app_path('Protocols') . '/*.php')) as $file) {
                        $file = 'App\\Protocols\\' . basename($file, '.php');
                        if (strpos($flag, get_class_vars($file)['flag']) !== false) {
                            $protocol = $file;
                            break;
                        }
                    }
                }
                if ($protocol === null && strpos($flag, 'sing') !== false) {
                    $version = null;
                    if (preg_match('/sing-box\s+([0-9.]+)/i', $flag, $matches)) {
                        $version = $matches[1];
                    }
                    if (!is_null($version) && $version >= '1.12.0') {
                        $protocol = Singbox::class;
                    } else {
                        $protocol = SingboxOld::class;
                    }
                }
            }

            if (in_array($protocol, [
                Clash::class, ClashMeta::class, ClashNyanpasu::class, ClashVerge::class,
                Singbox::class, SingboxOld::class,
            ], true)) {
                foreach ($servers as $key => $server) {
                    if ($server['type'] !== 'v2node' || (int)$server['tls'] !== 1
                        || ($server['tls_settings']['cert_mode'] ?? null) !== 'remote') {
                        continue;
                    }
                    $servers[$key]['tls_settings']['allow_insecure'] = 1;
                    if ($server['protocol'] === 'vmess') {
                        // Clash Meta's VMess builders read the legacy spelling.
                        $servers[$key]['tls_settings']['allowInsecure'] = 1;
                    }
                }
            }

            $protocol = $protocol ?? General::class;
            return (new $protocol($user, $servers))->handle();
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
            'name' => "套餐到期：{$expiredDate}",
        ]));
        if ($resetDay) {
            array_unshift($servers, array_merge($servers[0], [
                'name' => "距离下次重置剩余：{$resetDay} 天",
            ]));
        }
        array_unshift($servers, array_merge($servers[0], [
            'name' => "剩余流量：{$remainingTraffic}",
        ]));
    }
}
