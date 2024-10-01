<?php
namespace App\Protocols;

use App\Models\ServerHysteria;
use App\Models\User;
use App\Utils\Helper;
use Illuminate\Support\Facades\Log;

class Singbox
{
    public $flag = 'sing-box';
    private $servers;
    private $user;
    private $config;

    public function __construct($user, $servers, array $options = null)
    {
        $this->user = $user;
        $this->servers = $servers;
        
        // لاگ‌گیری هنگام ایجاد کلاس
        Log::info('Singbox instantiated.', ['user_id' => $user['id'], 'server_count' => count($servers)]);
    }

    public function handle()
    {
        Log::info('Handling Singbox request.', ['user_id' => $this->user['id']]);

        try {
            $appName = config('v2board.app_name', 'V2Board');
            $this->config = $this->loadConfig();
            Log::info('Configuration loaded.', ['config' => $this->config]);

            $proxies = $this->buildProxies();
            Log::info('Proxies built.', ['proxy_count' => count($proxies)]);

            $outbounds = $this->addProxies($proxies);
            Log::info('Proxies added to configuration.', ['outbound_count' => count($outbounds)]);

            $this->config['outbounds'] = $outbounds;
            $user = $this->user;

            // لاگ‌گیری موفقیت‌آمیز بودن عملیات
            Log::info('Returning response for user.', ['user_id' => $user['id']]);

            return response(json_encode($this->config, JSON_UNESCAPED_SLASHES), 200)
                ->header('Content-Type', 'application/json')
                ->header('subscription-userinfo', "upload={$user['u']}; download={$user['d']}; total={$user['transfer_enable']}; expire={$user['expired_at']}")
                ->header('profile-update-interval', '24')
                ->header('Content-Disposition', 'attachment; filename="' . $appName . '"');
        } catch (\Exception $e) {
            Log::error('Error handling Singbox request.', ['error' => $e->getMessage(), 'user_id' => $this->user['id']]);
            return response('Error processing request', 500);
        }
    }

    protected function loadConfig()
    {
        Log::info('Loading Singbox configuration.');

        $defaultConfig = base_path('resources/rules/default.sing-box.json');
        $customConfig = base_path('resources/rules/custom.sing-box.json');

        try {
            $jsonData = file_exists($customConfig) ? file_get_contents($customConfig) : file_get_contents($defaultConfig);
            Log::info('Configuration file loaded.', ['file' => file_exists($customConfig) ? $customConfig : $defaultConfig]);

            return json_decode($jsonData, true);
        } catch (\Exception $e) {
            Log::error('Error loading configuration.', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function buildProxies()
    {
        Log::info('Building proxies.');
        
        $proxies = [];

        foreach ($this->servers as $item) {
            Log::info('Processing server.', ['server_name' => $item['name'], 'server_type' => $item['type']]);

            if ($item['type'] === 'shadowsocks') {
                $ssConfig = $this->buildShadowsocks($this->user['uuid'], $item);
                $proxies[] = $ssConfig;
                Log::info('Shadowsocks proxy built.', ['proxy' => $ssConfig]);
            } elseif ($item['type'] === 'trojan') {
                $trojanConfig = $this->buildTrojan($this->user['uuid'], $item);
                $proxies[] = $trojanConfig;
                Log::info('Trojan proxy built.', ['proxy' => $trojanConfig]);
            } elseif ($item['type'] === 'vmess') {
                $vmessConfig = $this->buildVmess($this->user['uuid'], $item);
                $proxies[] = $vmessConfig;
                Log::info('Vmess proxy built.', ['proxy' => $vmessConfig]);
            } elseif ($item['type'] === 'vless') {
                $vlessConfig = $this->buildVless($this->user['uuid'], $item);
                $proxies[] = $vlessConfig;
                Log::info('Vless proxy built.', ['proxy' => $vlessConfig]);
            } elseif ($item['type'] === 'hysteria') {
                $hysteriaConfig = $this->buildHysteria($this->user['uuid'], $item, $this->user);
                $proxies[] = $hysteriaConfig;
                Log::info('Hysteria proxy built.', ['proxy' => $hysteriaConfig]);
            }
        }

        return $proxies;
    }

    protected function addProxies($proxies)
    {
        Log::info('Adding proxies to configuration.', ['proxies' => $proxies]);

        foreach ($this->config['outbounds'] as &$outbound) {
            if (($outbound['type'] === 'selector' && $outbound['tag'] === 'دکتر‌موبایل‌جایزان') || ($outbound['type'] === 'urltest' && $outbound['tag'] === 'انتخاب خودکار')) {
                array_push($outbound['outbounds'], ...array_column($proxies, 'tag'));
            }
        }
        unset($outbound);
        $outbounds = array_merge($this->config['outbounds'], $proxies);

        Log::info('Proxies added to outbounds.', ['outbounds' => $outbounds]);

        return $outbounds;
    }

    protected function buildShadowsocks($password, $server)
    {
        Log::info('Building Shadowsocks proxy.', ['server' => $server['name']]);

        if (strpos($server['cipher'], '2022-blake3') !== false) {
            $length = $server['cipher'] === '2022-blake3-aes-128-gcm' ? 16 : 32;
            $serverKey = Helper::getServerKey($server['created_at'], $length);
            $userKey = Helper::uuidToBase64($password, $length);
            $password = "{$serverKey}:{$userKey}";
        }
        
        $array = [
            'tag' => $server['name'],
            'type' => 'shadowsocks',
            'server' => $server['host'],
            'server_port' => $server['port'],
            'method' => $server['cipher'],
            'password' => $password
        ];

        Log::info('Shadowsocks proxy configuration complete.', ['proxy' => $array]);

        return $array;
    }

    protected function buildVmess($uuid, $server)
    {
        Log::info('Building Vmess proxy.', ['server' => $server['name']]);

        $array = [
            'tag' => $server['name'],
            'type' => 'vmess',
            'server' => $server['host'],
            'server_port' => $server['port'],
            'uuid' => $uuid,
            'security' => 'auto',
            'alter_id' => 0,
            'transport' => []
        ];

        if ($server['tls']) {
            $tlsConfig = [];
            $tlsConfig['enabled'] = true;
            if ($server['tlsSettings']) {
                $tlsSettings = $server['tlsSettings'] ?? [];
                $tlsConfig['insecure'] = $tlsSettings['allowInsecure'] ? true : false;
                $tlsConfig['server_name'] = $tlsSettings['serverName'] ?? null;
            }
            $array['tls'] = $tlsConfig;
        }

        Log::info('Vmess proxy configuration complete.', ['proxy' => $array]);

        return $array;
    }

    protected function buildVless($password, $server)
    {
        Log::info('Building Vless proxy.', ['server' => $server['name']]);

        $array = [
            'type' => 'vless',
            'tag' => $server['name'],
            'server' => $server['host'],
            'server_port' => $server['port'],
            'uuid' => $password,
            'packet_encoding' => 'xudp'
        ];

        if ($server['tls']) {
            $tlsConfig = [
                'enabled' => true,
                'insecure' => isset($server['tls_settings']['allow_insecure']) && $server['tls_settings']['allow_insecure'] == 1,
                'server_name' => $server['tls_settings']['server_name'] ?? null
            ];

            if ($server['tls'] == 2) {
                $tlsConfig['reality'] = [
                    'enabled' => true,
                    'public_key' => $server['tls_settings']['public_key'],
                    'short_id' => $server['tls_settings']['short_id']
                ];
            }
            $tlsConfig['utls'] = [
                'enabled' => true,
                'fingerprint' => $server['tls_settings']['fingerprint'] ?? 'chrome'
            ];

            $array['tls'] = $tlsConfig;
        }

        Log::info('Vless proxy configuration complete.', ['proxy' => $array]);

        return $array;
    }

    protected function buildTrojan($password, $server) 
    {
        Log::info('Building Trojan proxy.', ['server' => $server['name']]);

        $array = [
            'tag' => $server['name'],
            'type' => 'trojan',
            'server' => $server['host'],
            'server_port' => $server['port'],
            'password' => $password,
            'tls' => [
                'enabled' => true,
                'insecure' => $server['allow_insecure'] ? true : false,
                'server_name' => $server['server_name']
            ]
        ];

        Log::info('Trojan proxy configuration complete.', ['proxy' => $array]);

        return $array;
    }

    protected function buildHysteria($password, $server, $user)
    {
        Log::info('Building Hysteria proxy.', ['server' => $server['name']]);

        $parts = explode(",", $server['port']);
        $firstPart = $parts[0];
        if (strpos($firstPart, '-') !== false) {
            $range = explode('-', $firstPart);
            $firstPort = $range[0];
        } else {
            $firstPort = $firstPart;
        }

        $array = [
            'server' => $server['host'],
            'server_port' => (int)$firstPort,
            'tls' => [
                'enabled' => true,
                'insecure' => $server['insecure'] ? true : false,
                'server_name' => $server['server_name']
            ]
        ];

        if (is_null($server['version']) || $server['version'] == 1) {
            $array['auth_str'] = $password;
            $array['tag'] = $server['name'];
            $array['type'] = 'hysteria';
            $array['up_mbps'] = $user->speed_limit ? min($server['down_mbps'], $user->speed_limit) : $server['down_mbps'];
            $array['down_mbps'] = $user->speed_limit ? min($server['up_mbps'], $user->speed_limit) : $server['up_mbps'];
            if (isset($server['obfs']) && isset($server['obfs_password'])) {
                $array['obfs'] = $server['obfs_password'];
            }
            $array['disable_mtu_discovery'] = true;

        } elseif ($server['version'] == 2) {
            $array['password'] = $password;
            $array['tag'] = $server['name'];
            $array['type'] = 'hysteria2';

            if (isset($server['obfs'])) {
                $array['obfs']['type'] = $server['obfs'];
                $array['obfs']['password'] = $server['obfs_password'];
            }
        }

        Log::info('Hysteria proxy configuration complete.', ['proxy' => $array]);

        return $array;
    }
}
