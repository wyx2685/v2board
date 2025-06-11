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
        header("subscription-userinfo: upload={$user['u']}; download={$user['d']}; total={$user['transfer_enable']}; expire={$user['expired_at']}");
        foreach ($servers as $item) {
            if ($item['type'] === 'shadowsocks') {
                $uri .= self::buildShadowsocks($user['uuid'], $item);
            }
            if ($item['type'] === 'vmess') {
                $uri .= self::buildVmess($user['uuid'], $item);
            }
            // QX does not support the XTLS feature and needs to be excluded
            if ($item['type'] === 'vless' && !$item['flow'] ) {
                $uri .= self::buildVless($user['uuid'], $item);
            }
            if ($item['type'] === 'trojan') {
                $uri .= self::buildTrojan($user['uuid'], $item);
            }
        }
        return base64_encode($uri);
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
            "shadowsocks={$server['host']}:{$server['port']}",
            "method={$server['cipher']}",
            "password={$password}",
        ];
        if (isset($server['obfs']) && $server['obfs'] === 'http') {
            $config[] = "obfs=http";
            if (isset($server['obfs-host']) && !empty($server['obfs-host'])) {
                $config[] = "obfs-host={$server['obfs-host']}";
            }
            if (isset($server['obfs-path'])) {
                $config[] = "obfs-uri={$server['obfs-path']}";
            }
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
        $config = [
            "vmess={$server['host']}:{$server['port']}",
            'method=chacha20-poly1305',
            "password={$uuid}",
            'fast-open=true',
            'udp-relay=true',
            "tag={$server['name']}"
        ];

        if ($server['network'] === 'tcp') {
            if ($server['networkSettings']) {
                $tcpSettings = $server['networkSettings'];
                if (isset($tcpSettings['header']['type']) && !empty($tcpSettings['header']['type']) && $tcpSettings['header']['type'] == 'http') {
                    array_push($config, 'obfs=http');
                }
                if (isset($tcpSettings['header']['request']['path'][0]) && !empty($tcpSettings['header']['request']['path'][0])) {
                    array_push($config, "obfs-uri={$tcpSettings['header']['request']['path'][0]}");
                }
                if (isset($tcpSettings['header']['request']['headers']['Host'][0]) && !empty($tcpSettings['header']['request']['headers']['Host'][0])) {
                    array_push($config, "obfs-host={$tcpSettings['header']['request']['headers']['Host'][0]}");
                }
            }
        }

        if ($server['tls']) {
            array_push($config, 'tls13=true');
            if ($server['network'] === 'tcp') {
                array_push($config, 'obfs=over-tls');
            }
            if ($server['tlsSettings']) {
                $tlsSettings = $server['tlsSettings'];
                if (isset($tlsSettings['allowInsecure']) && !empty($tlsSettings['allowInsecure'])) {
                    array_push($config, 'tls-verification=' . ($tlsSettings['allowInsecure'] ? 'false' : 'true'));
                }
                if (isset($tlsSettings['serverName']) && !empty($tlsSettings['serverName'])) {
                    $host = $tlsSettings['serverName'];
                }
            }
        }

        if ($server['network'] === 'ws') {
            if ($server['tls']) {
                array_push($config, 'obfs=wss');
            } else {
                array_push($config, 'obfs=ws');
            }
            if ($server['networkSettings']) {
                $wsSettings = $server['networkSettings'];
                if (isset($wsSettings['path']) && !empty($wsSettings['path'])) {
                    array_push($config, "obfs-uri={$wsSettings['path']}");
                }
                if (isset($wsSettings['headers']['Host']) && !empty($wsSettings['headers']['Host']) && !isset($host)) {
                    $host = $wsSettings['headers']['Host'];
                }
                // QX WS does not support auto encryption. If the security value is auto, chacha20-poly1305 is still used as the default.
                if (isset($wsSettings['security']) && ($wsSettings['security']) !== 'auto') {
                    array_splice($config, 1, 1, "method={$wsSettings['security']}");
                }
            }
        }

        if (isset($host)) {
            array_push($config, "obfs-host={$host}");
        }

        $uri = implode(',', $config);
        $uri .= "\r\n";
        return $uri;
    }

    public static function buildVless($uuid, $server)
    {
        $config = [
            "vless={$server['host']}:{$server['port']}",
            'method=none',
            "password={$uuid}",
            'fast-open=true',
            'udp-relay=true',
            "tag={$server['name']}"
        ];

        if ($server['network'] === 'tcp') {
            if ($server['network_settings']) {
                $tcpSettings = $server['network_settings'];
                if (isset($tcpSettings['header']['type']) && !empty($tcpSettings['header']['type']) && $tcpSettings['header']['type'] == 'http') {
                    array_push($config, 'obfs=http');
                }
                if (isset($tcpSettings['header']['request']['path'][0]) && !empty($tcpSettings['header']['request']['path'][0])) {
                    array_push($config, "obfs-uri={$tcpSettings['header']['request']['path'][0]}");
                }
                if (isset($tcpSettings['header']['request']['headers']['Host'][0]) && !empty($tcpSettings['header']['request']['headers']['Host'][0])) {
                    array_push($config, "obfs-host={$tcpSettings['header']['request']['headers']['Host'][0]}");
                }
            }
        }

        if ($server['tls'] === 1) {
            array_push($config, 'tls13=true');
            if ($server['network'] === 'tcp') {
                array_push($config, 'obfs=over-tls');
            }

            if ($server['tls_settings']) {
                $tlsSettings = $server['tls_settings'];
                if (isset($tlsSettings['allow_insecure']) && !empty($tlsSettings['allow_insecure'])) {
                    array_push($config, 'tls-verification=' . ($tlsSettings['allow_insecure'] ? 'false' : 'true'));
                }
                if (isset($tlsSettings['server_name']) && !empty($tlsSettings['server_name'])) {
                    $host = $tlsSettings['server_name'];
                }
            }
        } elseif ($server['tls'] === 2) {
            // QX does not support Reality
            return '';
        }

        if ($server['network'] === 'ws') {
            if ($server['tls']) {
                array_push($config, 'obfs=wss');
            } else {
                array_push($config, 'obfs=ws');
            }
            if ($server['network_settings']) {
                $wsSettings = $server['network_settings'];
                if (isset($wsSettings['path']) && !empty($wsSettings['path'])) {
                    array_push($config, "obfs-uri={$wsSettings['path']}");
                }
                if (isset($wsSettings['headers']['Host']) && !empty($wsSettings['headers']['Host']) && !isset($host)) {
                    $host = $wsSettings['headers']['Host'];
                }
                // VLESS WS only supports none encryption
                // if (isset($wsSettings['security']) && ($wsSettings['security']) !== 'auto') {
                //     array_splice($config, 1, 1, "method={$wsSettings['security']}");
                // }
            }
        }

        if (isset($host)) {
            array_push($config, "obfs-host={$host}");
        }

        $uri = implode(',', $config);
        $uri .= "\r\n";
        return $uri;
    }

    public static function buildTrojan($password, $server)
    {
        $config = [
            "trojan={$server['host']}:{$server['port']}",
            "password={$password}",
            // Tips: allowInsecure=false = tls-verification=true
            $server['allow_insecure'] ? 'tls-verification=false' : 'tls-verification=true',
            'fast-open=true',
            'udp-relay=true',
            "tag={$server['name']}"
        ];
        $host = $server['server_name'] ?? $server['host'];
        // The obfs field is only supported with websocket over tls for trojan. When using websocket over tls you should not set over-tls and tls-host options anymore, instead set obfs=wss and obfs-host options.
        if ($server['network'] === 'ws') {
            array_push($config, 'obfs=wss');
            if ($server['network_settings']) {
                $wsSettings = $server['network_settings'];
                if (isset($wsSettings['path']) && !empty($wsSettings['path']))
                    array_push($config, "obfs-uri={$wsSettings['path']}");
                if (isset($wsSettings['headers']['Host']) && !empty($wsSettings['headers']['Host'])){
                    $host = $wsSettings['headers']['Host'];
                }
                array_push($config, "obfs-host={$host}");
            }
        } else {
            array_push($config, "over-tls=true");
            if(isset($server['server_name']) && !empty($server['server_name']))
                array_push($config, "tls-host={$server['server_name']}");
        }
        $config = array_filter($config);
        $uri = implode(',', $config);
        $uri .= "\r\n";
        return $uri;
    }
}
