<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Protocols\General;
use App\Protocols\Singbox\Singbox;
use App\Protocols\Singbox\SingboxOld;
use App\Protocols\ClashMeta;
use App\Services\ServerService;
use App\Services\UserService;
use App\Utils\Helper;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ClientController extends Controller
{
    public function subscribe(Request $request)
    {
        $response = $this->generateSubscription($request);
        if ($response === null) return;

        $user = $request->user;

        // Check if force encryption is enabled
        $forceEncrypted = (int)config('v2board.subscribe_encrypted_enable', 0)
            && (int)config('v2board.subscribe_force_encrypted', 0);

        if ($forceEncrypted) {
            return $this->buildEncryptedResponse($response, $user['uuid']);
        }

        return $response;
    }

    public function subscribeEncrypted(Request $request)
    {
        // Check if encryption endpoint is enabled
        if (!(int)config('v2board.subscribe_encrypted_enable', 0)) {
            abort(404);
        }

        $response = $this->generateSubscription($request);
        if ($response === null) return;

        $user = $request->user;
        return $this->buildEncryptedResponse($response, $user['uuid']);
    }

    private function generateSubscription(Request $request)
    {
        $flag = $request->input('flag')
            ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $flag = strtolower($flag);
        $user = $request->user;

        $userService = new UserService();
        if (!$userService->isAvailable($user)) {
            return null;
        }

        $serverService = new ServerService();
        $servers = $serverService->getAvailableServers($user);

        $response = null;
        if ($flag) {
            if (!strpos($flag, 'sing')) {
                $this->setSubscribeInfoToServers($servers, $user);
                foreach (array_reverse(glob(app_path('Protocols') . '/*.php')) as $file) {
                    $file = 'App\\Protocols\\' . basename($file, '.php');
                    $class = new $file($user, $servers);
                    if (strpos($flag, $class->flag) !== false) {
                        $response = $class->handle();
                        break;
                    }
                }
            }
            if ($response === null && strpos($flag, 'sing') !== false) {
                $version = null;
                if (preg_match('/sing-box\s+([0-9.]+)/i', $flag, $matches)) {
                    $version = $matches[1];
                }
                if (!is_null($version) && $version >= '1.12.0') {
                    $class = new Singbox($user, $servers);
                } else {
                    $class = new SingboxOld($user, $servers);
                }
                $response = $class->handle();
            }
        }
        if ($response === null) {
            $class = new General($user, $servers);
            $response = $class->handle();
        }

        return $response;
    }

    private function buildEncryptedResponse($response, $uuid)
    {
        $content = $response instanceof SymfonyResponse
            ? $response->getContent()
            : (string)$response;
        $encryptedResponse = response(Helper::encryptSubscription($content, $uuid))
            ->header('Content-Type', 'text/plain; charset=utf-8')
            ->header('X-Encrypted', '1');

        if ($response instanceof SymfonyResponse) {
            foreach ($response->headers->all() as $name => $values) {
                $lowerName = strtolower($name);
                if ($lowerName === 'content-type' || $lowerName === 'content-length') {
                    continue;
                }
                foreach ($values as $value) {
                    $encryptedResponse->headers->set($name, $value, false);
                }
            }
        }

        return $encryptedResponse;
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
