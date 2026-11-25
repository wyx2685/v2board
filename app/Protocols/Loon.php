<?php

namespace App\Protocols;

use App\Utils\Helper;

class Loon
{
    public $flag = 'loon';
    private $servers;
    private $user;

    public function __construct($user, $servers)
    {
        $this->user = $user;
        $this->servers = $servers;
    }

    public function handle()
    {
        $servers = $this->servers;
        $user = $this->user;

        $uri = '';
        header("Subscription-Userinfo: upload={$user['u']}; download={$user['d']}; total={$user['transfer_enable']}; expire={$user['expired_at']}");

        foreach ($servers as $item) {
            if (($item['type'] ?? null) === 'v2node' && isset($item['protocol'])) {
                $item['type'] = $item['protocol'];
            }
            if ($item['type'] === 'shadowsocks') {
                $uri .= self::buildShadowsocks($user['uuid'], $item);
            }elseif ($item['type'] === 'vmess') {
                $uri .= self::buildVmess($user['uuid'], $item);
            }elseif ($item['type'] === 'vless' && (($item['network'] ?? null) === 'tcp' || ($item['network'] ?? null) === 'ws')) {
                $uri .= self::buildVless($user['uuid'], $item);
            }elseif ($item['type'] === 'trojan' && (($item['network'] ?? null) !== 'grpc')) {
                $uri .= self::buildTrojan($user['uuid'], $item);
            }elseif ($item['type'] === 'hysteria2' || ($item['type'] === 'hysteria' && (int)$item['version'] === 2)) {
                $uri .= self::buildHysteria($user['uuid'], $item);
            }elseif ($item['type'] === 'anytls') {
                $uri .= self::buildAnytls($user['uuid'], $item);
            }
        }
        return $uri;
    }


    public static function buildShadowsocks($password, $server)
    {
        if ($server['cipher'] === '2022-blake3-aes-128-gcm') {
            $serverKey = Helper::getServerKey($server['created_at'], 16);
            $userKey = Helper::uuidToBase64($password, 16);
            $password = "{$serverKey}:{$userKey}";
        } elseif ($server['cipher'] === '2022-blake3-aes-256-gcm') {
            $serverKey = Helper::getServerKey($server['created_at'], 32);
            $userKey = Helper::uuidToBase64($password, 32);
            $password = "{$serverKey}:{$userKey}";
        }
        $config = [
            "{$server['name']}=Shadowsocks",
        ];
        $config[] = $server['host'];
        $config[] = $server['port'];
        $config[] = $server['cipher'];
        $config[] = $password;

        if (isset($server['obfs']) && $server['obfs'] === 'http') {
            $config[] = "obfs-name={$server['obfs']}";
            if (isset($server['obfs-host']) && !empty($server['obfs-host'])) {
                $config[] = "obfs-host={$server['obfs-host']}";
            }
            if (isset($server['obfs-path'])) {
                $config[] = "obfs-uri={$server['obfs-path']}";
            }
        }
        $config[] = 'fast-open=false';
        $config[] = 'udp=true';
        $uri = implode(',', $config);
        $uri .= "\r\n";

        return $uri;
    }

    public static function buildVmess($uuid, $server)
    {
        $networkSettings = $server['networkSettings'] ?? $server['network_settings'] ?? [];
        $config = [
            "{$server['name']}=vmess",
            "{$server['host']}",
            "{$server['port']}",
            $networkSettings['security'] ?? 'auto',
            "{$uuid}",
            'fast-open=false',
            'udp=true',
            "alterId=0"
        ];

        if ($server['network'] === 'tcp') {
            array_push($config, 'transport=tcp');
            if ($networkSettings) {
                $tcpSettings = $networkSettings;
                if (isset($tcpSettings['header']['type']) && !empty($tcpSettings['header']['type']) && $tcpSettings['header']['type'] == 'http')
                    $config = str_replace('transport=tcp', "transport={$tcpSettings['header']['type']}", $config);
                if (isset($tcpSettings['header']['request']['path'][0]) && !empty($tcpSettings['header']['request']['path'][0]))
                    array_push($config, "path={$tcpSettings['header']['request']['path'][0]}");
                if (isset($tcpSettings['header']['request']['headers']['Host'][0]) && !empty($tcpSettings['header']['request']['headers']['Host'][0]))
                    array_push($config, "host={$tcpSettings['header']['request']['headers']['Host'][0]}");
            }
        }
        if ($server['tls']) {
            array_push($config, 'over-tls=true');
            $tlsSettings = $server['tls_settings'] ?? $server['tlsSettings'] ?? [];
            if (!empty($tlsSettings['allow_insecure'] ?? $tlsSettings['allowInsecure'] ?? 0)) {
                $config[] = 'skip-cert-verify=true';
            }
            $sni = $tlsSettings['server_name'] ?? $tlsSettings['serverName'] ?? '';
            if ($sni !== '') {
                $config[] = "sni={$sni}";
            }
            Helper::appendTlsCertificateFingerprint($config, $server, 'tls-cert-sha256');
        }
        if ($server['network'] === 'ws') {
            array_push($config, 'transport=ws');
            if ($networkSettings) {
                $wsSettings = $networkSettings;
                if (isset($wsSettings['path']) && !empty($wsSettings['path']))
                    array_push($config, "path={$wsSettings['path']}");
                if (isset($wsSettings['headers']['Host']) && !empty($wsSettings['headers']['Host']))
                    array_push($config, "host={$wsSettings['headers']['Host']}");
            }
        }

        $uri = implode(',', $config);
        $uri .= "\r\n";
        return $uri;
    }

    public static function buildVless($uuid, $server)
    {
        $config = [
            "{$server['name']}=vless",
            "{$server['host']}",
            "{$server['port']}",
            "{$uuid}",
            'fast-open=false',
            'udp=true',
            "alterId=0"
        ];

        if ($server['network'] === 'tcp') {
            array_push($config, 'transport=tcp');
            if ($server['network_settings']) {
                $tcpSettings = $server['network_settings'];
                if (isset($tcpSettings['header']['type']) && !empty($tcpSettings['header']['type']) && $tcpSettings['header']['type'] == 'http')
                    $config = str_replace('transport=tcp', "transport={$tcpSettings['header']['type']}", $config);
                if (isset($tcpSettings['header']['request']['path'][0]) && !empty($tcpSettings['header']['request']['path'][0]))
                    array_push($config, "path={$tcpSettings['header']['request']['path'][0]}");
                if (isset($tcpSettings['header']['request']['headers']['Host'][0]) && !empty($tcpSettings['header']['request']['headers']['Host'][0]))
                    array_push($config, "host={$tcpSettings['header']['request']['headers']['Host'][0]}");
            }
        }
        if ((int)$server['tls'] === 1) {
            array_push($config, 'over-tls=true');
            array_push($config, "flow={$server['flow']}");
            if ($server['tls_settings']) {
                $tlsSettings = $server['tls_settings'];
                if (!empty($tlsSettings['allow_insecure'] ?? 0))
                    array_push($config, 'skip-cert-verify=true');
                if (isset($tlsSettings['server_name']) && !empty($tlsSettings['server_name']))
                    array_push($config, "sni={$tlsSettings['server_name']}");
            }
            Helper::appendTlsCertificateFingerprint($config, $server, 'tls-cert-sha256');
        }elseif((int)$server['tls'] === 2){
            array_push($config, "flow={$server['flow']}");
            if ($server['tls_settings']) {
                $tlsSettings = $server['tls_settings'];
                if (isset($tlsSettings['public_key']) && !empty($tlsSettings['public_key']))
                    array_push($config, "public-key={$tlsSettings['public_key']}");
                if (isset($tlsSettings['short_id']) && !empty($tlsSettings['short_id']))
                    array_push($config, "short-id={$tlsSettings['short_id']}");
                if (isset($tlsSettings['server_name']) && !empty($tlsSettings['server_name']))
                    array_push($config, "sni={$tlsSettings['server_name']}");
                if (!empty($tlsSettings['allow_insecure'] ?? 0))
                    array_push($config, 'skip-cert-verify=true');
            }
        }
        if ($server['network'] === 'ws') {
            array_push($config, 'transport=ws');
            if ($server['network_settings']) {
                $wsSettings = $server['network_settings'];
                if (isset($wsSettings['path']) && !empty($wsSettings['path']))
                    array_push($config, "path={$wsSettings['path']}");
                if (isset($wsSettings['headers']['Host']) && !empty($wsSettings['headers']['Host']))
                    array_push($config, "host={$wsSettings['headers']['Host']}");
            }
        }

        $uri = implode(',', $config);
        $uri .= "\r\n";
        return $uri;
    }
    
    public static function buildTrojan($password, $server)
    {
        $tlsSettings = $server['tls_settings'] ?? [];
        $sni = $server['server_name'] ?? $tlsSettings['server_name'] ?? '';
        $config = [
            "{$server['name']}=trojan",
            "{$server['host']}",
            "{$server['port']}",
            "{$password}",
            $sni !== '' ? "sni={$sni}" : "",
            'fast-open=false',
            'udp=true'
        ];
        if (!empty($server['allow_insecure'] ?? $tlsSettings['allow_insecure'] ?? 0)) {
            $config[] = 'skip-cert-verify=true';
        }
        Helper::appendTlsCertificateFingerprint($config, $server, 'tls-cert-sha256');
        if (isset($server['network']) && (string)$server['network'] === 'ws') {
            array_push($config, 'ws=true');
            if ($server['network_settings']) {
                $wsSettings = $server['network_settings'];
                if (isset($wsSettings['path']) && !empty($wsSettings['path']))
                    array_push($config, "ws-path={$wsSettings['path']}");
                if (isset($wsSettings['headers']['Host']) && !empty($wsSettings['headers']['Host']))
                    array_push($config, "ws-headers=Host:{$wsSettings['headers']['Host']}");
            }
        }
        $config = array_filter($config);
        $uri = implode(',', $config);
        $uri .= "\r\n";
        return $uri;
    }
    
    public static function buildHysteria($password, $server)
    {
        $tlsSettings = $server['tls_settings'] ?? [];
        $sni = $server['server_name'] ?? $tlsSettings['server_name'] ?? '';
        $parts = explode(",",$server['port']);
        $firstPart = $parts[0];
        if (strpos($firstPart, '-') !== false) {
            $range = explode('-', $firstPart);
            $firstPort = $range[0];
        } else {
            $firstPort = $firstPart;
        }

        $config = [
            "{$server['name']}=hysteria2",
            "{$server['host']}",
            "{$firstPort}",
            $password,
            $sni !== '' ? "sni={$sni}" : "",
            'udp=true'
        ];
        if (!empty($server['up_mbps'])) {
            $config[] = "download-bandwidth={$server['up_mbps']}";
        }
        if (count($parts) > 1 || strpos($firstPart, '-') !== false) {
            $config[] = 'server-ports="' . str_replace('-', ':', $server['port']) . '"';
        }
        if (!empty($server['insecure'] ?? $tlsSettings['allow_insecure'] ?? 0)) {
            $config[] = 'skip-cert-verify=true';
        }
        if (($server['obfs'] ?? '') === 'salamander' && !empty($server['obfs_password'])) {
            $config[] = 'salamander-password=' . $server['obfs_password'];
        }
        Helper::appendTlsCertificateFingerprint($config, $server, 'tls-cert-sha256');
        $config = array_filter($config);
        $uri = implode(',', $config);
        $uri .= "\r\n";
        return $uri;
    }

    public static function buildAnytls($password, $server)
    {
        $config = [
            "{$server['name']}=anytls",
            "{$server['host']}",
            "{$server['port']}",
            "{$password}",
            "udp=true"
        ];
        $tlsSettings = $server['tls_settings'] ?? [];
        $sni = $server['server_name'] ?? $tlsSettings['server_name'] ?? '';
        if ($sni) {
            $config[] = "sni={$sni}";
        }
        $insecure = $server['insecure'] ?? $tlsSettings['allow_insecure'] ?? 0;
        $config[] = 'skip-cert-verify=' . ($insecure ? 'true' : 'false');
        Helper::appendTlsCertificateFingerprint($config, $server, 'tls-cert-sha256');

        $uri = implode(',', $config);
        $uri .= "\r\n";
        return $uri;
    }
}
