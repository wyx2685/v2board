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
            $subInfoBlacklist = ['surge', 'surfboard', 'stash', 'shadowrocket', 'quantumultx', 'loon', 'clashmeta', 'clash'];
            $showSub = true;
            foreach ($subInfoBlacklist as $subName) {
                if (strpos($flag, $subName) !== false) {
                    $showSub = false;
                    break;
                }
            }
            if ($showSub && (!($flag && strpos($flag, 'sing-box')))) {
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
        // MeT 20240903
        //统计在线设备
        $countalive = 0;
        $ips = [];
        $ips_array = Cache::get('ALIVE_IP_USER_'. $user['id']);
        if ($ips_array) {
            $countalive = $ips_array['alive_ip'];
            foreach($ips_array as $nodetypeid => $data) {
                if (!is_int($data) && isset($data['aliveips'])) {
                    foreach($data['aliveips'] as $ip_NodeId) {
                        $ip = explode("_", $ip_NodeId)[0];
                        $ips[] = $ip . '_' . $nodetypeid;
                    }
                }
            }
        }
        $user['alive_ip'] = $countalive;
        
        $upload = round($user['u'] / (1024*1024*1024), 2);
        $download = round($user['d'] / (1024*1024*1024), 2);
        $useTraffic = $user['u'] + $user['d'];
        $totalTraffic = $user['transfer_enable'];
        $remainingTraffic = Helper::trafficConvert($totalTraffic - $useTraffic);
        $expiredDate = $user['expired_at'] ? date('Y-m-d', $user['expired_at']) : '长期有效';
        $userService = new UserService();
        $resetDay = $userService->getResetDay($user);
        $deviceLimit = $user['device_limit'] ? $user['device_limit'] : '∞';

        array_unshift($servers, array_merge($servers[0], [
            'name' => "🚀设备限制：{$user['alive_ip']} / {$deviceLimit}",
            'host' => '127.0.0.1',
            'port' => 10086,
        ]));
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
            'name' => "🚀已用流量：上传 {$upload}GB | 下载 {$download}GB",
            'host' => '127.0.0.1',
            'port' => 10086,
        ]));
        array_unshift($servers, array_merge($servers[0], [
            'name' => "🚀邮箱：{$user['email']}",
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
