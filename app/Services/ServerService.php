<?php

namespace App\Services;

use App\Models\ServerHysteria;
use App\Models\ServerLog;
use App\Models\ServerRoute;
use App\Models\ServerShadowsocks;
use App\Models\ServerUser;
use App\Models\ServerVless;
use App\Models\ServerV2node;
use App\Models\User;
use App\Models\ServerVmess;
use App\Models\ServerTrojan;
use App\Models\ServerTuic;
use App\Models\ServerAnytls;
use App\Utils\CacheKey;
use App\Utils\Helper;
use Illuminate\Support\Facades\Cache;

class ServerService
{
    /**
     * 批量获取用户专属节点分配（按 server_type 分组）
     * 返回格式: ['vless' => [1, 3], 'trojan' => [2], ...]
     */
    private function getUserAssignedIds(int $userId): array
    {
        return ServerUser::where('user_id', $userId)
            ->get(['server_id', 'server_type'])
            ->groupBy('server_type')
            ->map(fn($rows) => $rows->pluck('server_id')->toArray())
            ->toArray();
    }

    /**
     * 判断节点对该用户是否可见
     * 优先走专属分配逻辑，没有分配时走原有 group_id 逻辑
     */
    private function isServerVisible(array|object $server, User $user, array $assignedIds, string $type): bool
    {
        $assignedForType = $assignedIds[$type] ?? [];
        if (!empty($assignedForType) && in_array($server['id'], $assignedForType)) {
            // 专属节点：show=1 才对该用户可见，其他用户不可见
            return (bool)$server['show'];
        }
        // 原有逻辑：show=1 且 group_id 匹配
        return $server['show'] && in_array($user->group_id, $server['group_id']);
    }

    public function getAvailableVless(User $user, array $assignedIds = []): array
    {
        $servers = [];
        $model = ServerVless::orderBy('sort', 'ASC');
        $server = $model->get();
        foreach ($server as $key => $v) {
            $server[$key]['type'] = 'vless';
            if (!$this->isServerVisible($server[$key], $user, $assignedIds, 'vless')) continue;
            if (strpos($server[$key]['port'], '-') !== false) {
                $server[$key]['port'] = Helper::randomPort($server[$key]['port']);
            }
            if ($server[$key]['parent_id']) {
                $server[$key]['last_check_at'] = Cache::get(CacheKey::get('SERVER_VLESS_LAST_CHECK_AT', $server[$key]['parent_id']));
            } else {
                $server[$key]['last_check_at'] = Cache::get(CacheKey::get('SERVER_VLESS_LAST_CHECK_AT', $server[$key]['id']));
            }
            if (isset($server[$key]['tls_settings'])) {
                if (isset($server[$key]['tls_settings']['private_key'])) {
                    $server[$key]['tls_settings'] = array_diff_key($server[$key]['tls_settings'], array('private_key' => ''));
                }
            }
            if (isset($server[$key]['encryption_settings'])) {
                if (isset($server[$key]['encryption_settings']['private_key'])) {
                    $server[$key]['encryption_settings'] = array_diff_key($server[$key]['encryption_settings'], array('private_key' => ''));
                }
            }
            $servers[] = $server[$key]->toArray();
        }


        return $servers;
    }

    public function getAvailableVmess(User $user, array $assignedIds = []): array
    {
        $servers = [];
        $model = ServerVmess::orderBy('sort', 'ASC');
        $vmess = $model->get();
        foreach ($vmess as $key => $v) {
            $vmess[$key]['type'] = 'vmess';
            if (!$this->isServerVisible($vmess[$key], $user, $assignedIds, 'vmess')) continue;
            if (strpos($vmess[$key]['port'], '-') !== false) {
                $vmess[$key]['port'] = Helper::randomPort($vmess[$key]['port']);
            }
            if ($vmess[$key]['parent_id']) {
                $vmess[$key]['last_check_at'] = Cache::get(CacheKey::get('SERVER_VMESS_LAST_CHECK_AT', $vmess[$key]['parent_id']));
            } else {
                $vmess[$key]['last_check_at'] = Cache::get(CacheKey::get('SERVER_VMESS_LAST_CHECK_AT', $vmess[$key]['id']));
            }
            $servers[] = $vmess[$key]->toArray();
        }

        return $servers;
    }

    public function getAvailableTrojan(User $user, array $assignedIds = []): array
    {
        $servers = [];
        $model = ServerTrojan::orderBy('sort', 'ASC');
        $trojan = $model->get();
        foreach ($trojan as $key => $v) {
            $trojan[$key]['type'] = 'trojan';
            if (!$this->isServerVisible($trojan[$key], $user, $assignedIds, 'trojan')) continue;
            if (strpos($trojan[$key]['port'], '-') !== false) {
                $trojan[$key]['port'] = Helper::randomPort($trojan[$key]['port']);
            }
            if ($trojan[$key]['parent_id']) {
                $trojan[$key]['last_check_at'] = Cache::get(CacheKey::get('SERVER_TROJAN_LAST_CHECK_AT', $trojan[$key]['parent_id']));
            } else {
                $trojan[$key]['last_check_at'] = Cache::get(CacheKey::get('SERVER_TROJAN_LAST_CHECK_AT', $trojan[$key]['id']));
            }
            $servers[] = $trojan[$key]->toArray();
        }
        return $servers;
    }

    public function getAvailableTuic(User $user, array $assignedIds = [])
    {
        $availableServers = [];
        $model = ServerTuic::orderBy('sort', 'ASC');
        $servers = $model->get()->keyBy('id');
        foreach ($servers as $key => $v) {
            $servers[$key]['type'] = 'tuic';
            $servers[$key]['last_check_at'] = Cache::get(CacheKey::get('SERVER_TUIC_LAST_CHECK_AT', $v['id']));
            if (!$this->isServerVisible($servers[$key], $user, $assignedIds, 'tuic')) continue;
            if (isset($servers[$v['parent_id']])) {
                $servers[$key]['last_check_at'] = Cache::get(CacheKey::get('SERVER_TUIC_LAST_CHECK_AT', $v['parent_id']));
                $servers[$key]['created_at'] = $servers[$v['parent_id']]['created_at'];
            }
            $availableServers[] = $servers[$key]->toArray();
        }
        return $availableServers;
    }

    public function getAvailableHysteria(User $user, array $assignedIds = [])
    {
        $availableServers = [];
        $model = ServerHysteria::orderBy('sort', 'ASC');
        $servers = $model->get()->keyBy('id');
        foreach ($servers as $key => $v) {
            $servers[$key]['type'] = 'hysteria';
            $servers[$key]['last_check_at'] = Cache::get(CacheKey::get('SERVER_HYSTERIA_LAST_CHECK_AT', $v['id']));
            if (!$this->isServerVisible($servers[$key], $user, $assignedIds, 'hysteria')) continue;
            if (isset($servers[$v['parent_id']])) {
                $servers[$key]['last_check_at'] = Cache::get(CacheKey::get('SERVER_HYSTERIA_LAST_CHECK_AT', $v['parent_id']));
                $servers[$key]['created_at'] = $servers[$v['parent_id']]['created_at'];
            }
            $servers[$key]['server_key'] = Helper::getServerKey($servers[$key]['created_at'], 16);
            $availableServers[] = $servers[$key]->toArray();
        }
        return $availableServers;
    }

    public function getAvailableShadowsocks(User $user, array $assignedIds = [])
    {
        $servers = [];
        $model = ServerShadowsocks::orderBy('sort', 'ASC');
        $shadowsocks = $model->get()->keyBy('id');
        foreach ($shadowsocks as $key => $v) {
            $shadowsocks[$key]['type'] = 'shadowsocks';
            $shadowsocks[$key]['last_check_at'] = Cache::get(CacheKey::get('SERVER_SHADOWSOCKS_LAST_CHECK_AT', $v['id']));
            if (!$this->isServerVisible($shadowsocks[$key], $user, $assignedIds, 'shadowsocks')) continue;
            if (strpos($v['port'], '-') !== false) {
                $shadowsocks[$key]['port'] = Helper::randomPort($v['port']);
            }
            if (isset($shadowsocks[$v['parent_id']])) {
                $shadowsocks[$key]['last_check_at'] = Cache::get(CacheKey::get('SERVER_SHADOWSOCKS_LAST_CHECK_AT', $v['parent_id']));
                $shadowsocks[$key]['created_at'] = $shadowsocks[$v['parent_id']]['created_at'];
            }
            if ($v['obfs'] === 'http') {
                $shadowsocks[$key]['obfs'] = 'http';
                $shadowsocks[$key]['obfs-host'] = $v['obfs_settings']['host'];
                $shadowsocks[$key]['obfs-path'] = $v['obfs_settings']['path'];
            }
            $servers[] = $shadowsocks[$key]->toArray();
        }
        return $servers;
    }

    public function getAvailableAnyTLS(User $user, array $assignedIds = [])
    {
        $servers = [];
        $model = ServerAnytls::orderBy('sort', 'ASC');
        $anytls = $model->get()->keyBy('id');
        foreach ($anytls as $key => $v) {
            $anytls[$key]['type'] = 'anytls';
            $anytls[$key]['last_check_at'] = Cache::get(CacheKey::get('SERVER_ANYTLS_LAST_CHECK_AT', $v['id']));
            if (!$this->isServerVisible($anytls[$key], $user, $assignedIds, 'anytls')) continue;
            if (strpos($v['port'], '-') !== false) {
                $anytls[$key]['port'] = Helper::randomPort($v['port']);
            }
            if (isset($anytls[$v['parent_id']])) {
                $anytls[$key]['last_check_at'] = Cache::get(CacheKey::get('SERVER_ANYTLS_LAST_CHECK_AT', $v['parent_id']));
                $anytls[$key]['created_at'] = $anytls[$v['parent_id']]['created_at'];
            }
            $servers[] = $anytls[$key]->toArray();
        }
        return $servers;
    }

    public function getAvailableV2node(User $user, array $assignedIds = [])
    {
        $servers = [];
        $model = ServerV2node::orderBy('sort', 'ASC');
        $v2node = $model->get()->keyBy('id');
        foreach ($v2node as $key => $v) {
            $v2node[$key]['type'] = 'v2node';
            $v2node[$key]['last_check_at'] = Cache::get(CacheKey::get('SERVER_V2NODE_LAST_CHECK_AT', $v['id']));
            if (!$this->isServerVisible($v2node[$key], $user, $assignedIds, 'v2node')) continue;
            if (isset($v2node[$v['parent_id']])) {
                $v2node[$key]['last_check_at'] = Cache::get(CacheKey::get('SERVER_V2NODE_LAST_CHECK_AT', $v['parent_id']));
                $v2node[$key]['created_at'] = $v2node[$v['parent_id']]['created_at'];
            }
            if (isset($v2node[$key]['tls_settings'])) {
                if (isset($v2node[$key]['tls_settings']['private_key'])) {
                    $v2node[$key]['tls_settings'] = array_diff_key($v2node[$key]['tls_settings'], array('private_key' => ''));
                }
            }
            if (isset($v2node[$key]['encryption_settings'])) {
                if (isset($v2node[$key]['encryption_settings']['private_key'])) {
                    $v2node[$key]['encryption_settings'] = array_diff_key($v2node[$key]['encryption_settings'], array('private_key' => ''));
                }
            }
            $servers[] = $v2node[$key]->toArray();
        }
        return $servers;
    }

    public function getAvailableServers(User $user)
    {
        // 一次性批量查出该用户的专属节点分配，避免 N+1
        $assignedIds = $this->getUserAssignedIds($user->id);

        $servers = array_merge(
            $this->getAvailableShadowsocks($user, $assignedIds),
            $this->getAvailableVmess($user, $assignedIds),
            $this->getAvailableTrojan($user, $assignedIds),
            $this->getAvailableTuic($user, $assignedIds),
            $this->getAvailableHysteria($user, $assignedIds),
            $this->getAvailableVless($user, $assignedIds),
            $this->getAvailableAnyTLS($user, $assignedIds),
            $this->getAvailableV2node($user, $assignedIds)
        );
        $tmp = array_column($servers, 'sort');
        array_multisort($tmp, SORT_ASC, $servers);
        return array_map(function ($server) {
            if (strpos($server['port'], '-')) {
                $server['mport'] = (string)$server['port'];
            } else {
                $server['port'] = (int)$server['port'];
            }
            $server['is_online'] = (time() - 300 > $server['last_check_at']) ? 0 : 1;
            $server['cache_key'] = "{$server['type']}-{$server['id']}-{$server['updated_at']}-{$server['is_online']}";
            return $server;
        }, $servers);
    }

    public function getAvailableUsers($groupId)
    {
        return User::whereIn('group_id', $groupId)
            ->whereRaw('u + d < transfer_enable')
            ->where(function ($query) {
                $query->where('expired_at', '>=', time())
                    ->orWhere('expired_at', NULL);
            })
            ->where('banned', 0)
            ->select([
                'id',
                'uuid',
                'speed_limit',
                'device_limit'
            ])
            ->get();
    }

    public function log(int $userId, int $serverId, int $u, int $d, float $rate, string $method)
    {
        if (($u + $d) < 10240) return true;
        $timestamp = strtotime(date('Y-m-d'));
        $serverLog = ServerLog::where('log_at', '>=', $timestamp)
            ->where('log_at', '<', $timestamp + 3600)
            ->where('server_id', $serverId)
            ->where('user_id', $userId)
            ->where('rate', $rate)
            ->where('method', $method)
            ->first();
        if ($serverLog) {
            try {
                $serverLog->increment('u', $u);
                $serverLog->increment('d', $d);
                return true;
            } catch (\Exception $e) {
                return false;
            }
        } else {
            $serverLog = new ServerLog();
            $serverLog->user_id = $userId;
            $serverLog->server_id = $serverId;
            $serverLog->u = $u;
            $serverLog->d = $d;
            $serverLog->rate = $rate;
            $serverLog->log_at = $timestamp;
            $serverLog->method = $method;
            return $serverLog->save();
        }
    }

    public function getAllShadowsocks()
    {
        $servers = ServerShadowsocks::orderBy('sort', 'ASC')
            ->get()
            ->toArray();
        foreach ($servers as $k => $v) {
            $servers[$k]['type'] = 'shadowsocks';
        }
        return $servers;
    }

    public function getAllVMess()
    {
        $servers = ServerVmess::orderBy('sort', 'ASC')
            ->get()
            ->toArray();
        foreach ($servers as $k => $v) {
            $servers[$k]['type'] = 'vmess';
        }
        return $servers;
    }

    public function getAllVLess()
    {
        $servers = ServerVless::orderBy('sort', 'ASC')
            ->get()
            ->toArray();
        foreach ($servers as $k => $v) {
            $servers[$k]['type'] = 'vless';
        }
        return $servers;
    }

    public function getAllTrojan()
    {
        $servers = ServerTrojan::orderBy('sort', 'ASC')
            ->get()
            ->toArray();
        foreach ($servers as $k => $v) {
            $servers[$k]['type'] = 'trojan';
        }
        return $servers;
    }

    public function getAllTuic()
    {
        $servers = ServerTuic::orderBy('sort', 'ASC')
            ->get()
            ->toArray();
        foreach ($servers as $k => $v) {
            $servers[$k]['type'] = 'tuic';
        }
        return $servers;
    }

    public function getAllHysteria()
    {
        $servers = ServerHysteria::orderBy('sort', 'ASC')
            ->get()
            ->toArray();
        foreach ($servers as $k => $v) {
            $servers[$k]['type'] = 'hysteria';
        }
        return $servers;
    }

    public function getAllAnyTLS()
    {
        $servers = ServerAnytls::orderBy('sort', 'ASC')
            ->get()
            ->toArray();
        foreach ($servers as $k => $v) {
            $servers[$k]['type'] = 'anytls';
            if (isset($v['padding_scheme'])) {
                $servers[$k]['padding_scheme'] = json_encode($v['padding_scheme']);
            }
        }
        return $servers;
    }

    public function getAllV2node()
    {
        $servers = ServerV2node::orderBy('sort', 'ASC')
            ->get()
            ->toArray();
        foreach ($servers as $k => $v) {
            $servers[$k]['type'] = 'v2node';
            if (isset($v['padding_scheme'])) {
                $servers[$k]['padding_scheme'] = json_encode($v['padding_scheme']);
            }

            $apiHost = config('v2board.server_api_url', config('v2board.app_url'));
            $apiKey = config('v2board.server_token', '');
            $nodeId = $v['id'];
            $servers[$k]['install_command'] = "wget -N https://raw.githubusercontent.com/wyx2685/v2node/master/script/install.sh && bash install.sh --api-host {$apiHost} --node-id {$nodeId} --api-key {$apiKey}";
        }
        return $servers;
    }

    private function mergeData(&$servers)
    {
        foreach ($servers as $k => $v) {
            $serverType = strtoupper($v['type']);
            $servers[$k]['online'] = Cache::get(CacheKey::get("SERVER_{$serverType}_ONLINE_USER", $v['parent_id'] ?? $v['id']));
            $servers[$k]['last_check_at'] = Cache::get(CacheKey::get("SERVER_{$serverType}_LAST_CHECK_AT", $v['parent_id'] ?? $v['id']));
            $servers[$k]['last_push_at'] = Cache::get(CacheKey::get("SERVER_{$serverType}_LAST_PUSH_AT", $v['parent_id'] ?? $v['id']));
            if ((time() - 300) >= $servers[$k]['last_check_at']) {
                $servers[$k]['available_status'] = 0;
            } else if ((time() - 300) >= $servers[$k]['last_push_at']) {
                $servers[$k]['available_status'] = 1;
            } else {
                $servers[$k]['available_status'] = 2;
            }
        }
    }

    public function getAllServers()
    {
        $servers = array_merge(
            $this->getAllShadowsocks(),
            $this->getAllVMess(),
            $this->getAllTrojan(),
            $this->getAllTuic(),
            $this->getAllHysteria(),
            $this->getAllVLess(),
            $this->getAllAnyTLS(),
            $this->getAllV2node()
        );
        $this->mergeData($servers);

        // 批量附加专属用户分配信息（按 type+id 分组）
        $allAssignments = ServerUser::get(['server_type', 'server_id', 'user_id']);
        $assignMap = [];
        foreach ($allAssignments as $row) {
            $assignMap[$row->server_type][$row->server_id][] = $row->user_id;
        }
        foreach ($servers as $k => $v) {
            $servers[$k]['assigned_user_ids'] = $assignMap[$v['type']][$v['id']] ?? [];
        }

        $tmp = array_column($servers, 'sort');
        array_multisort($tmp, SORT_ASC, $servers);
        return $servers;
    }

    public function getRoutes(array $routeIds)
    {
        $routeIds = array_map('intval', $routeIds);
        $routes = ServerRoute::select(['id', 'match', 'action', 'action_value'])->whereIn('id', $routeIds)->get();
        foreach ($routes as $k => $route) {
            $array = json_decode($route->match, true);
            if (is_array($array)) $routes[$k]['match'] = $array;
        }
        return $routes;
    }

    public function getServer($serverId, $serverType)
    {
        switch ($serverType) {
            case 'v2node':
                return ServerV2node::find($serverId);
            case 'vmess':
                return ServerVmess::find($serverId);
            case 'shadowsocks':
                return ServerShadowsocks::find($serverId);
            case 'trojan':
                return ServerTrojan::find($serverId);
            case 'tuic':
                return ServerTuic::find($serverId);
            case 'hysteria':
                return ServerHysteria::find($serverId);
            case 'vless':
                return ServerVless::find($serverId);
            case 'anytls':
                return ServerAnytls::find($serverId);
            default:
                return false;
        }
    }
}
