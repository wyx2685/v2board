<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Protocols\General;
use App\Protocols\Singbox\Singbox;
use App\Protocols\Singbox\SingboxOld;
use App\Protocols\ClashMeta;
use App\Services\ServerService;
use App\Services\UserService;
use App\Models\Plan;
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
        $custom_sni = $request->input('sni');
        if ($custom_sni === null && isset($user['network_settings'])) {
            $custom_sni = $user['network_settings'] ?? null;
        }
        // account not expired and is not banned.
        $userService = new UserService();
        if ($userService->isAvailable($user)) {
            $serverService = new ServerService();
            $servers = $serverService->getAvailableServers($user);
            if($flag) {
                if (!strpos($flag, 'sing')) {
                    $this->setSubscribeInfoToServers($servers, $user, $custom_sni);
                    foreach (array_reverse(glob(app_path('Protocols') . '/*.php')) as $file) {
                        $file = 'App\\Protocols\\' . basename($file, '.php');
                        $class = new $file($user, $servers);
                        if (strpos($flag, $class->flag) !== false) {
                            return $class->handle();
                        }
                    }
                }
                if (strpos($flag, 'sing') !== false) {
                    $version = null;
                    if (preg_match('/sing-box\s+([0-9.]+)/i', $flag, $matches)) {
                        $version = $matches[1];
                    }
                    if (!is_null($version) && $version >= '1.12.0') {
                        $class = new Singbox($user, $servers);
                    } else {
                        $class = new SingboxOld($user, $servers);
                    }
                    return $class->handle();
                }
            }
            $class = new General($user, $servers);
            return $class->handle();
        }
    }

    private function setSubscribeInfoToServers(&$servers, $user, $custom_sni)
    {
        if ($custom_sni !== null) {
            foreach ($servers as &$server) {
                if ($server['type'] === 'shadowsocks' && isset($server['obfs']) && $server['obfs'] === 'http') {
                    $server['obfs-host'] = $custom_sni;
                }
                if ($server['type'] === 'vmess') {
                    if ($server['tls'] && isset($server['tlsSettings']['serverName'])) {
                        $server['tlsSettings']['serverName'] = $custom_sni;
                    }
                    if ($server['network'] === 'ws' && isset($server['networkSettings']['headers']['Host'])) {
                        $server['networkSettings']['headers']['Host'] = $custom_sni;
                    }
                }
                if ($server['type'] === 'vless') {
                    if ($server['tls'] && isset($server['tls_settings']['server_name'])) {
                        $server['tls_settings']['server_name'] = $custom_sni;
                    }
                    if ($server['network'] === 'ws' && isset($server['network_settings']['headers']['Host'])) {
                        $server['network_settings']['headers']['Host'] = $custom_sni;
                    }
                }
                if ($server['type'] === 'trojan') {
                    if (!empty($server['server_name'])) {
                        $server['server_name'] = $custom_sni;
                    }
                    if ($server['network'] === 'ws' && isset($server['network_settings']['headers']['Host'])) {
                        $server['network_settings']['headers']['Host'] = $custom_sni;
                    }
                }
                if ($server['type'] === 'hysteria') {
                    if (isset($server['server_name'])) {
                        $server['server_name'] = $custom_sni;
                    }
                }
                if ($server['type'] === 'tuic') {
                    if (isset($server['server_name'])) {
                        $server['server_name'] = $custom_sni;
                    }
                }
                if ($server['type'] === 'anytls') {
                    if (isset($server['server_name'])) {
                        $server['server_name'] = $custom_sni;
                    }
                }
            }
        }

        if (!isset($servers[0])) return;
        if (!(int)config('v2board.show_info_to_server_enable', 0)) return;
        $useTraffic = $user['u'] + $user['d'];
        $totalTraffic = $user['transfer_enable'];
        $remainingTraffic = Helper::trafficConvert($totalTraffic - $useTraffic);
        $expiredDate = $user['expired_at'] ? date('d-m-Y H:i:s', $user['expired_at']) : '长期有效';
        $userService = new UserService();
        $resetDay = $userService->getResetDay($user);
        $userPlanId = $user['plan_id'];
        $v2Plan = Plan::find($userPlanId);
        $UserID = $user['id'];
        $planName = $v2Plan->name;
        array_unshift($servers, array_merge($servers[0], [
            'name' => "⏳ Hạn SD: {$expiredDate}",
        ]));
        if ($resetDay) {
            array_unshift($servers, array_merge($servers[0], [
                'name' => "Reset data sau：{$resetDay} Ngày",
            ]));
        }
        array_unshift($servers, array_merge($servers[0], [
            'name' => "📨 Data: {$remainingTraffic}",
        ]));
        array_unshift($servers, array_merge($servers[0], [
            'name' => "📝 Gói: {$planName}",
        ]));
        array_unshift($servers, array_merge($servers[0], [
            'name' => "👤 User ID: {$UserID}",
        ]));
    }
}
