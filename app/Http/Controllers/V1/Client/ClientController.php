<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Protocols\General;
use App\Protocols\Singbox\Singbox;
use App\Protocols\Singbox\SingboxOld;
use App\Protocols\ClashMeta;
use App\Services\ServerService;
use App\Services\UserService;
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
            $protocolClass = $this->resolveProtocolClass($flag);

            if ($flag && $protocolClass !== Singbox::class && $protocolClass !== SingboxOld::class) {
                $this->setSubscribeInfoToServers($servers, $user);
            }

            $class = new $protocolClass($user, $servers);
            return $class->handle();
        }
    }

    /**
     * Resolve the output generator first. TLS pin capability belongs to the
     * generated format, not to a guessed client name in the request.
     */
    private function resolveProtocolClass(string $flag): string
    {
        if (strpos($flag, 'sing') !== false) {
            if (preg_match('/sing-box\s+([0-9.]+)/i', $flag, $matches)
                && version_compare($matches[1], '1.13.0', '>=')) {
                return Singbox::class;
            }
            return SingboxOld::class;
        }

        if ($flag) {
            foreach (array_reverse(glob(app_path('Protocols') . '/*.php')) as $file) {
                $protocolClass = 'App\\Protocols\\' . basename($file, '.php');
                $protocol = new $protocolClass([], []);
                if (strpos($flag, $protocol->flag) !== false) {
                    return $protocolClass;
                }
            }
        }

        return General::class;
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
