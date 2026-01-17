<?php

namespace App\Protocols;

use App\Utils\Helper;

class QuantumultX
{
    public $flag = 'quantumult%20x';
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
        $upload = $user['u'] ?? 0;
        $download = $user['d'] ?? 0;
        $total = $user['transfer_enable'] ?? 0;
        $expire = $user['expired_at'] ?? 0;
        $uuid = $user['uuid'] ?? '';

        header("subscription-userinfo: upload={$upload}; download={$download}; total={$total}; expire={$expire}");

        foreach ($servers as $item) {
            if (($item['type'] ?? null) === 'v2node' && isset($item['protocol'])) {
                $item['type'] = $item['protocol'];
            }

            // 提前过滤不支持的传输协议 (QX 不支持 gRPC, HTTPUpgrade, XHTTP)
            $network = $item['network'] ?? 'tcp';
            if (in_array($network, ['grpc', 'httpupgrade', 'xhttp'])) {
                continue;
            }

            switch ($item['type']) {
                case 'shadowsocks':
                    $uri .= self::buildShadowsocks($uuid, $item);
                    break;
                case 'vmess':
                    $uri .= self::buildVmess($uuid, $item);
                    break;
                case 'vless':
                    $uri .= self::buildVless($uuid, $item);
                    break;
                case 'trojan':
                    $uri .= self::buildTrojan($uuid, $item);
                    break;
            }
        }

        return base64_encode($uri);
    }

    public static function buildShadowsocks($password, $server)
    {
        // Standardization v2_server_shadowsocks -> v2node format
        if (isset($server['obfs']) && !empty($server['obfs'])) {
            $server['network'] = $server['obfs'];
            $server['network_settings'] = $server['network_settings'] ?? [];

            if (isset($server['obfs_settings']) && is_array($server['obfs_settings'])) {
                if (!empty($server['obfs_settings']['host'])) {
                    $server['network_settings']['headers']['Host'] = $server['obfs_settings']['host'];
                }
                if (!empty($server['obfs_settings']['path'])) {
                    $server['network_settings']['path'] = $server['obfs_settings']['path'];
                }
            }
        }

        // ss2022 处理
        if (in_array($server['cipher'], ['2022-blake3-aes-128-gcm', '2022-blake3-aes-256-gcm'])) {
            $length = ($server['cipher'] === '2022-blake3-aes-128-gcm') ? 16 : 32;
            $serverKey = Helper::getServerKey($server['created_at'], $length);
            $userKey = Helper::uuidToBase64($password, $length);
            $password = "{$serverKey}:{$userKey}";
        }

        // 配置生成
        $config = [
            "shadowsocks={$server['host']}:{$server['port']}",
            "method={$server['cipher']}",
            "password={$password}",
        ];

        // 传输层
        $network = $server['network'] ?? 'tcp';

        if ($network === 'http') {
            $config[] = 'obfs=http';

            $netSettings = $server['network_settings'] ?? [];
            $host = $netSettings['headers']['Host'] ?? $netSettings['Host'] ?? null;
            $path = $netSettings['path'] ?? null;

            if ($host) $config[] = "obfs-host={$host}";
            if ($path) $config[] = "obfs-uri={$path}";
        }

        $config[] = 'fast-open=false';
        $config[] = 'udp-relay=true';
        $config[] = "tag={$server['name']}";

        $config = array_filter($config);
        $uri = implode(',', $config);
        $uri .= "\r\n";

        return $uri;
    }

    public static function buildVmess($uuid, $server)
    {
        // Standardization v2_server_vmess -> v2node format
        if (isset($server['networkSettings'])) {
            $legacy = is_array($server['networkSettings']) ? $server['networkSettings'] : [];
            $current = $server['network_settings'] ?? [];
            $server['network_settings'] = array_replace_recursive($legacy, $current);
            unset($server['networkSettings']);
        }

        // tls 配置只提取 serverName 与 allowInsecure 值
        if (isset($server['tlsSettings'])) {
            $legacy = is_array($server['tlsSettings']) ? $server['tlsSettings'] : [];
            $current = $server['tls_settings'] ?? [];
            if (isset($legacy['serverName'])) $legacy['server_name'] = $legacy['serverName'];
            if (isset($legacy['allowInsecure'])) $legacy['allow_insecure'] = $legacy['allowInsecure'];
            $server['tls_settings'] = array_replace_recursive($legacy, $current);
            unset($server['tlsSettings']);
        }

        // 配置生成
        $config = [
            "vmess={$server['host']}:{$server['port']}",
            'method=chacha20-poly1305',
            "password={$uuid}",
            'fast-open=true',
            'udp-relay=true',
            "tag={$server['name']}"
        ];

        $tlsSettings = $server['tls_settings'] ?? [];
        $netSettings = $server['network_settings'] ?? [];
        $network = $server['network'] ?? 'tcp';

        $isTls = !empty($server['tls']);

        // QX WS does not support auto encryption. If the security value is auto, chacha20-poly1305 is still used as the default.
        // if (isset($netSettings['security']) && ($netSettings['security']) !== 'auto') {
        //     array_splice($config, 1, 1, "method={$netSettings['security']}");
        // }
        if ($network === 'ws' && isset($netSettings['security']) && $netSettings['security'] !== 'auto') {
            foreach ($config as $k => $v) {
                if (strpos($v, 'method=') === 0) {
                    $config[$k] = "method={$netSettings['security']}";
                    break;
                }
            }
        }

        if ($isTls) {
            $config[] = 'tls13=true';
            if (isset($tlsSettings['allow_insecure']) && $tlsSettings['allow_insecure']) {
                $config[] = 'tls-verification=false';
            }
        }

        // 传输层类型
        if ($network === 'ws') {
            // WS: 有 TLS 则 wss，无 TLS 则 ws
            $config[] = $isTls ? 'obfs=wss' : 'obfs=ws';
        } elseif ($network === 'tcp') {
            if ($isTls) {
                // TCP + TLS
                $config[] = 'obfs=over-tls';
            } else {
                // TCP + No TLS (检查是否为 HTTP)
                $header = $netSettings['header'] ?? [];
                if (($header['type'] ?? '') === 'http') {
                    $config[] = 'obfs=http';
                }
            }
        }

        // 传输层参数 (Host/Path)
        $host = null;
        $path = null;

        if ($network === 'tcp') {
            $header = $netSettings['header'] ?? [];
            // 上面已经添加了 obfs=http
            if (($header['type'] ?? '') === 'http') {
                $host = $header['request']['headers']['Host'][0] ?? null;
                $path = $header['request']['path'][0] ?? null;
            }
        } elseif ($network === 'ws') {
            $host = $netSettings['headers']['Host'] ?? null;
            $path = $netSettings['path'] ?? null;
        }

        $sni = $tlsSettings['server_name'] ?? null;
        if (empty($host) && !empty($sni)) {
            $host = $sni;
        }

        if ($host) $config[] = "obfs-host={$host}";
        if ($path) $config[] = "obfs-uri={$path}";

        $uri = implode(',', $config);
        $uri .= "\r\n";
        return $uri;
    }

    public static function buildVless($uuid, $server)
    {
        // 配置生成
        $config = [
            "vless={$server['host']}:{$server['port']}",
            'method=none',
            "password={$uuid}",
            'udp-relay=true',
            "tag={$server['name']}"
        ];

        // fast-open (REALITY: false, Others: true)
        $config[] = ($server['tls'] == 2) ? 'fast-open=false' : 'fast-open=true';

        if (!empty($server['encryption']) && isset($server['encryption_settings']) && !empty($server['encryption_settings'])) {
            // QX does not support VLESS encryption?
            return '';
        }

        $tlsSettings = $server['tls_settings'] ?? [];
        $netSettings = $server['network_settings'] ?? [];
        $network = $server['network'] ?? 'tcp';

        $isTls = !empty($server['tls']);

        if ($isTls) {
            $config[] = 'tls13=true';
            if (isset($tlsSettings['allow_insecure']) && $tlsSettings['allow_insecure']) {
                $config[] = 'tls-verification=false';
            }
            if (!empty($server['flow'])) {
                $config[] = "vless-flow={$server['flow']}";
            }

            // REALITY
            if ($server['tls'] == 2) {
                if (isset($tlsSettings['public_key'])) $config[] = "reality-base64-pubkey={$tlsSettings['public_key']}";
                if (isset($tlsSettings['short_id'])) $config[] = "reality-hex-shortid={$tlsSettings['short_id']}";
            }
        }

        // 传输层类型
        if ($network === 'ws') {
            // WS: 有 TLS 则 wss，无 TLS 则 ws
            $config[] = $isTls ? 'obfs=wss' : 'obfs=ws';
        } elseif ($network === 'tcp') {
            if ($isTls) {
                // TCP + TLS
                $config[] = 'obfs=over-tls';
            } else {
                // TCP + No TLS (检查是否为 HTTP)
                $header = $netSettings['header'] ?? [];
                if (($header['type'] ?? '') === 'http') {
                    $config[] = 'obfs=http';
                }
            }
        }

        // QX VLESS WS does not read the security parameter from the network settings.
        // if (isset($netSettings['security']) && ($netSettings['security']) !== 'auto') {
        //     array_splice($config, 1, 1, "method={$netSettings['security']}");
        // }

        // 传输层参数 (Host/Path)
        $host = null;
        $path = null;

        if ($network === 'tcp') {
            $header = $netSettings['header'] ?? [];
            if (($header['type'] ?? '') === 'http') {
                // 上面已经添加了 obfs=http
                $host = $header['request']['headers']['Host'][0] ?? null;
                $path = $header['request']['path'][0] ?? null;
            }
        } elseif ($network === 'ws') {
            $host = $netSettings['headers']['Host'] ?? null;
            $path = $netSettings['path'] ?? null;
        }

        $sni = $tlsSettings['server_name'] ?? null;
        if (empty($host) && !empty($sni)) {
            $host = $sni;
        }

        if ($host) $config[] = "obfs-host={$host}";
        if ($path) $config[] = "obfs-uri={$path}";

        $uri = implode(',', $config);
        $uri .= "\r\n";
        return $uri;
    }

    public static function buildTrojan($password, $server)
    {
        // Standardization v2_server_trojan -> v2node format
        $server['tls_settings'] = $server['tls_settings'] ?? [];

        // v2_server_trojan 表：将外层列字段映射到 tls_settings
        if (isset($server['allow_insecure'])) {
            $server['tls_settings']['allow_insecure'] = (bool)$server['allow_insecure'];
        }
        if (isset($server['server_name'])) {
            $server['tls_settings']['server_name'] = $server['server_name'];
        }
        unset($server['allow_insecure']);
        unset($server['server_name']);

        // 配置生成
        $config = [
            "trojan={$server['host']}:{$server['port']}",
            "password={$password}",
            'fast-open=true',
            'udp-relay=true',
            "tag={$server['name']}"
        ];

        $tlsSettings = $server['tls_settings'] ?? [];
        $netSettings = $server['network_settings'] ?? [];
        $network = $server['network'] ?? 'tcp';

        $sni = $tlsSettings['server_name'] ?? null;
        $allowInsecure = $tlsSettings['allow_insecure'] ?? false;

        // tcp 配置
        if ($network === 'tcp') {
            $config[] = 'over-tls=true';
            if ($sni) {
                $config[] = "tls-host={$sni}";
            }
            // Tips: allowInsecure=false = tls-verification=true, the value of tls-verification needs to be explicitly specified.
            $config[] = 'tls-verification=' . ($allowInsecure ? 'false' : 'true');
        }

        // ws 配置
        if ($network === 'ws') {
            // When using websocket over tls you should not set over-tls and tls-host options anymore, instead set obfs=wss and obfs-host options.
            $config[] = 'obfs=wss';

            $host = $netSettings['headers']['Host'] ?? null;
            $path = $netSettings['path'] ?? null;

            if (empty($host) && !empty($sni)) {
                $host = $sni;
            }

            if ($host) $config[] = "obfs-host={$host}";
            if ($path) $config[] = "obfs-uri={$path}";

            if ($allowInsecure) {
                $config[] = 'tls-verification=false';
            }
        }

        $config = array_filter($config);
        $uri = implode(',', $config);
        $uri .= "\r\n";
        return $uri;
    }
}
