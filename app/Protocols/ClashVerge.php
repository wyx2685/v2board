<?php

namespace App\Protocols;

use App\Utils\Helper;
use Symfony\Component\Yaml\Yaml;

class ClashVerge
{
    public $flag = 'verge';
    private $servers;
    private $user;
    private $config;

    public function __construct($user, $servers)
    {
        $this->user = $user;
        $this->servers = $servers;
        $this->loadConfig();
    }

    private function loadConfig()
    {
        $defaultConfig = base_path() . '/resources/rules/meta.clash.yaml';
        $customConfig = base_path() . '/resources/rules/custom.clash.yaml';
        $this->config = \File::exists($customConfig) ? Yaml::parseFile($customConfig) : Yaml::parseFile($defaultConfig);
    }

    public function handle()
    {
        $appName = config('v2board.app_name', 'V2Board');
        header("subscription-userinfo: upload={$this->user['u']}; download={$this->user['d']}; total={$this->user['transfer_enable']}; expire={$this->user['expired_at']}");
        header('profile-update-interval: 24');
        header("content-disposition:attachment;filename*=UTF-8''".rawurlencode($appName));

        $proxies = array_map([$this, 'buildProxy'], $this->servers);
        $proxyNames = array_column($proxies, 'name');

        $this->config['proxies'] = array_merge($this->config['proxies'] ?? [], $proxies);
        $this->updateProxyGroups($proxyNames);
        $this->addSubscriptionDomainRule();

        $yaml = Yaml::dump($this->config, 2, 4, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
        $yaml = str_replace('$app_name', $appName, $yaml);
        return $yaml;
    }

    private function buildProxy($server)
    {
        if (!isset($server['type'])) {
            throw new \InvalidArgumentException('نوع سرور مشخص نشده است.');
        }

        $type = ucfirst($server['type']);
        $method = "build{$type}";

        if (!method_exists($this, $method)) {
            // اگر نوع سرور پشتیبانی نمی‌شود، می‌توانید آن را نادیده بگیرید یا خطا پرتاب کنید
            return [];
        }

        $userId = $this->user['uuid'] ?? null;
        if (!$userId) {
            throw new \RuntimeException('UUID کاربر تنظیم نشده است.');
        }

        return $this->$method($userId, $server);
    }

    private function buildShadowsocks($password, $server)
    {
        $password = $this->buildShadowsocksPassword($password, $server);
        return [
            'name' => $server['name'],
            'type' => 'ss',
            'server' => $server['host'],
            'port' => $server['port'],
            'cipher' => $server['cipher'],
            'password' => $password,
            'udp' => true
        ];
    }

    private function buildShadowsocksPassword($password, $server)
    {
        $cipher = $server['cipher'];
        if ($cipher === '2022-blake3-aes-128-gcm') {
            $serverKey = Helper::getServerKey($server['created_at'], 16);
            $userKey = Helper::uuidToBase64($password, 16);
            return "{$serverKey}:{$userKey}";
        }
        if ($cipher === '2022-blake3-aes-256-gcm') {
            $serverKey = Helper::getServerKey($server['created_at'], 32);
            $userKey = Helper::uuidToBase64($password, 32);
            return "{$serverKey}:{$userKey}";
        }
        return $password;
    }

    private function buildVmess($uuid, $server)
    {
        $array = $this->buildCommonSettings($uuid, $server);
        $array['type'] = 'vmess';
        $array['uuid'] = $uuid;
        $array['alterId'] = 0;
        $array['cipher'] = 'auto';

        if (!empty($server['tls'])) {
            $array['tls'] = true;
            $array = array_merge($array, $this->buildTlsSettings($server['tlsSettings'] ?? []));
        }

        return array_merge($array, $this->buildNetworkSettings($server));
    }

    private function buildVless($uuid, $server)
    {
        $array = $this->buildCommonSettings($uuid, $server);
        $array['type'] = 'vless';
        $array['uuid'] = $uuid;

        if (!empty($server['tls'])) {
            $array['tls'] = true;
            $array = array_merge($array, $this->buildVlessTlsSettings($server));
        }

        return array_merge($array, $this->buildNetworkSettings($server));
    }

    private function buildTrojan($password, $server)
    {
        $array = $this->buildCommonSettings($password, $server);
        $array['type'] = 'trojan';
        $array['password'] = $password;

        if (!empty($server['server_name'])) $array['sni'] = $server['server_name'];
        if (!empty($server['allow_insecure'])) $array['skip-cert-verify'] = (bool)$server['allow_insecure'];

        return array_merge($array, $this->buildNetworkSettings($server));
    }

    private function buildHysteria($password, $server)
    {
        $array = $this->buildCommonSettings($password, $server);
        $array['type'] = $server['version'] === 2 ? 'hysteria2' : 'hysteria';
        $array[$server['version'] === 2 ? 'password' : 'auth_str'] = $password;

        if (isset($server['obfs'])) {
            $array['obfs'] = $server['obfs'];
            $array['obfs-password'] = $server['obfs_password'] ?? '';
        }

        if ($server['version'] !== 2) {
            $array['up'] = $server['down_mbps'];
            $array['down'] = $server['up_mbps'];
            $array['protocol'] = 'udp';
        }

        return $array;
    }

    private function buildCommonSettings($id, $server)
    {
        return [
            'name' => $server['name'],
            'server' => $server['host'],
            'port' => (int)explode(",", $server['port'])[0],
            'udp' => true,
            'skip-cert-verify' => !empty($server['insecure']),
            'sni' => $server['server_name'] ?? null
        ];
    }

    private function buildTlsSettings($tlsSettings)
    {
        return [
            'skip-cert-verify' => !empty($tlsSettings['allowInsecure']),
            'servername' => $tlsSettings['serverName'] ?? null
        ];
    }

    private function buildVlessTlsSettings($server)
    {
        $tlsSettings = $server['tls_settings'] ?? [];
        $settings = [
            'skip-cert-verify' => !empty($tlsSettings['allow_insecure']),
            'flow' => $server['flow'] ?? '',
            'client-fingerprint' => $tlsSettings['fingerprint'] ?? 'chrome',
            'servername' => $tlsSettings['server_name'] ?? null,
        ];
        if ($server['tls'] == 2) {
            $settings['reality-opts'] = [
                'public-key' => $tlsSettings['public_key'] ?? '',
                'short-id' => $tlsSettings['short_id'] ?? ''
            ];
        }
        return $settings;
    }

    private function buildNetworkSettings($server)
    {
        $network = $server['network'] ?? '';
        $settings = [];
        if (in_array($network, ['tcp', 'ws', 'grpc', 'h2'])) {
            $settings['network'] = $network;
            $opts = "{$network}-opts";
            $settings[$opts] = $this->buildSpecificNetworkSettings($network, $server['network_settings'] ?? []);
        }
        return $settings;
    }

    private function buildSpecificNetworkSettings($network, $settings)
    {
        $opts = [];
        if ($network === 'tcp' && ($settings['header']['type'] ?? '') === 'http') {
            $opts['headers']['Host'] = $settings['header']['request']['headers']['Host'] ?? '';
            $opts['path'] = $settings['header']['request']['path'] ?? '';
        }
        if ($network === 'ws') {
            $opts['path'] = $settings['path'] ?? '';
            $opts['headers'] = ['Host' => $settings['headers']['Host'] ?? ''];
        }
        if ($network === 'grpc') {
            $opts['grpc-service-name'] = $settings['serviceName'] ?? '';
        }
        if ($network === 'h2') {
            $opts['host'] = [$settings['host'] ?? ''];
            $opts['path'] = $settings['path'] ?? '';
        }
        return $opts;
    }

    private function updateProxyGroups($proxies)
    {
        foreach ($this->config['proxy-groups'] as &$group) {
            $group['proxies'] = $this->updateGroupProxies($group['proxies'], $proxies);
        }
        $this->config['proxy-groups'] = array_values(array_filter($this->config['proxy-groups'], fn($group) => $group['proxies']));
    }

    private function updateGroupProxies($groupProxies, $proxies)
    {
        $updatedProxies = [];
        foreach ($groupProxies as $src) {
            if ($this->isRegex($src)) {
                foreach ($proxies as $dst) {
                    if ($this->isMatch($src, $dst)) {
                        $updatedProxies[] = $dst;
                    }
                }
            } else {
                $updatedProxies[] = $src;
            }
        }
        return array_unique(array_merge($updatedProxies, $proxies));
    }

    private function addSubscriptionDomainRule()
    {
        $subsDomain = $_SERVER['HTTP_HOST'] ?? null;
        if ($subsDomain) {
            array_unshift($this->config['rules'], "DOMAIN,{$subsDomain},DIRECT");
        }
    }

    private function isMatch($exp, $str)
    {
        return preg_match($exp, $str);
    }

    private function isRegex($exp)
    {
        return @preg_match($exp, null) !== false;
    }
}
