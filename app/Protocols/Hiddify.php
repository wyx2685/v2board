<?php

namespace App\Protocols;

use App\Utils\Helper;

class Hiddify
{
    public $flag = 'hiddify';
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
        $appName = config('v2board.app_name');
        header("subscription-userinfo: upload={$user['u']}; download={$user['d']}; total={$user['transfer_enable']}; expire={$user['expired_at']}");
        header('profile-update-interval: 24');
        header("profile-title:".rawurlencode($appName));
        foreach ($servers as $item) {
            if ($item['type'] === 'shadowsocks') {
                $uri .= self::buildShadowsocks($user['uuid'], $item);
            }
            if ($item['type'] === 'vmess') {
                $uri .= self::buildVmess($user['uuid'], $item);
            }
            if ($item['type'] === 'trojan') {
                $uri .= self::buildTrojan($user['uuid'], $item);
            }
            if ($item['type'] === 'vless') {
                $uri .= self::buildVless($user['uuid'], $item);
            }
            if ($item['type'] === 'hysteria') {
                $uri .= self::buildHysteria($user['uuid'], $item);
            }
        }
        return base64_encode($uri);
    }

    public static function buildShadowsocks($password, $server)
    {
        $config = [
            "shadowsocks={$server['host']}:{$server['port']}",
            "method={$server['cipher']}",
            "password={$password}",
            'fast-open=true',
            'udp-relay=true',
            "tag={$server['name']}"
        ];
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

        if ($server['tls']) {
            if ($server['network'] === 'tcp')
                array_push($config, 'obfs=over-tls');
            if ($server['tlsSettings']) {
                $tlsSettings = $server['tlsSettings'];
                if (isset($tlsSettings['allowInsecure']) && !empty($tlsSettings['allowInsecure']))
                    array_push($config, 'tls-verification=' . ($tlsSettings['allowInsecure'] ? 'false' : 'true'));
                if (isset($tlsSettings['serverName']) && !empty($tlsSettings['serverName']))
                    $host = $tlsSettings['serverName'];
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
                if (isset($wsSettings['path']) && !empty($wsSettings['path']))
                    array_push($config, "obfs-uri={$wsSettings['path']}");
                if (isset($wsSettings['headers']['Host']) && !empty($wsSettings['headers']['Host']) && !isset($host))
                    $host = $wsSettings['headers']['Host'];
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

    public static function buildVless($uuid, $server)
    {
        $config = [
            "name" => Helper::encodeURIComponent($server['name']),
            "add" => $server['host'],
            "port" => (string)$server['port'],
            "type" => $server['network'],
            "encryption" => "none",
            "host" => "",
            "path" => "",
            "headerType" => "none",
            "quicSecurity" => "none",
            "serviceName" => "",
            "mode" => "gun",
            "security" => $server['tls'] !=0 ? ($server['tls'] == 2 ? "reality":"tls") : "",
            "flow" => $server['flow'],
            "fp" => isset($server['tls_settings']['fingerprint']) ? $server['tls_settings']['fingerprint'] : 'chrome',
            "sni" => "",
            "pbk" => "",
            "sid" =>"",
        ];

        $output = "vless://" . $uuid . "@" . $config['add'] . ":" . $config['port'];
        $output .= "?" . "type={$config['type']}" . "&encryption={$config['encryption']}" . "&security={$config['security']}";

        if ($server['tls']) {
            if ($config['flow'] != "") $output .= "&flow={$config['flow']}";
            if ($server['tls_settings']) {
                $tlsSettings = $server['tls_settings'];
                if (isset($tlsSettings['server_name']) && !empty($tlsSettings['server_name'])) $config['sni'] = $tlsSettings['server_name'];
                $output .= "&sni={$config['sni']}";
                if ($server['tls'] == 2) {
                    $config['pbk'] = $tlsSettings['public_key'];
                    $config['sid'] = $tlsSettings['short_id'];
                    $output .= "&pbk={$config['pbk']}" . "&sid={$config['sid']}";
                }
            }
        }
        if ((string)$server['network'] === 'tcp') {
            $tcpSettings = $server['network_settings'];
            if (isset($tcpSettings['header']['type']) && $tcpSettings['header']['type'] == 'http') {
                $config['headerType'] = $tcpSettings['header']['type'];
                if (isset($tcpSettings['header']['request']['headers']['Host'][0])) $config['host'] = $tcpSettings['header']['request']['headers']['Host'][0];
                if (isset($tcpSettings['header']['request']['path'][0])) $config['path'] = $tcpSettings['header']['request']['path'][0];
            }
            $output .= "&headerType={$config['headerType']}" . "&host={$config['host']}" . "&path={$config['path']}";
        }
        if ((string)$server['network'] === 'kcp') {
            $kcpSettings = $server['network_settings'];
            if (isset($kcpSettings['header']['type'])) $config['headerType'] = $kcpSettings['header']['type'];
            if (isset($kcpSettings['seed'])) $config['path'] = Helper::encodeURIComponent($kcpSettings['seed']);
            $output .= "&headerType={$config['headerType']}" . "&seed={$config['path']}";
        }
        if ((string)$server['network'] === 'ws') {
            $wsSettings = $server['network_settings'];
            if (isset($wsSettings['path'])) $config['path'] = Helper::encodeURIComponent($wsSettings['path']);
            if (isset($wsSettings['headers']['Host'])) $config['host'] = Helper::encodeURIComponent($wsSettings['headers']['Host']);
            $output .= "&path={$config['path']}" . "&host={$config['host']}";
        }
        if ((string)$server['network'] === 'h2') {
            $h2Settings = $server['network_settings'];
            if (isset($h2Settings['path'])) $config['path'] = Helper::encodeURIComponent($h2Settings['path']);
            if (isset($h2Settings['host'])) $config['host'] = Helper::encodeURIComponent($h2Settings['host']);
            $output .= "&path={$config['path']}" . "&host={$config['host']}";
        }
        if ((string)$server['network'] === 'quic') {
            $quicSettings = $server['network_settings'];
            if (isset($quicSettings['security'])) $config['quicSecurity'] = $quicSettings['security'];
            if (isset($quicSettings['header']['type'])) $config['headerType'] = $quicSettings['header']['type'];

            $output .= "&quicSecurity={$config['quicSecurity']}" . "&headerType={$config['headerType']}";

            if ((string)$quicSettings['security'] !== 'none' && isset($quicSettings['key'])) $config['path'] = Helper::encodeURIComponent($quicSettings['key']);

            $output .= "&key={$config['path']}";
        }
        if ((string)$server['network'] === 'grpc') {
            $grpcSettings = $server['network_settings'];
            if (isset($grpcSettings['serviceName'])) $config['serviceName'] = Helper::encodeURIComponent($grpcSettings['serviceName']);
            if (isset($grpcSettings['multiMode'])) $config['mode'] = $grpcSettings['multiMode'] ? "multi" : "gun";
            $output .= "&serviceName={$config['serviceName']}" . "&mode={$config['mode']}";
        }

        $output .= "&fp={$config['fp']}" . "#" . $config['name'];

        return $output . "\r\n";
    }

    public static function buildHysteria($password, $server)
    {
        $remote = filter_var($server['host'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? '[' . $server['host'] . ']' : $server['host'];
     	$name = Helper::encodeURIComponent($server['name']);

        $parts = explode(",",$server['port']);
        $firstPart = $parts[0];
        if (strpos($firstPart, '-') !== false) {
            $range = explode('-', $firstPart);
            $firstPort = $range[0];
        } else {
            $firstPort = $firstPart;
        }

        if ($server['version'] == 2) {
            $uri = "hysteria2://{$password}@{$remote}:{$firstPort}/?insecure={$server['insecure']}&sni={$server['server_name']}";
            if (isset($server['obfs']) && isset($server['obfs_password'])) {
                $uri .= "&obfs={$server['obfs']}&obfs-password={$server['obfs_password']}";
            }
        } else {
            $uri = "hysteria://{$remote}:{$firstPort}/?";
            $query = http_build_query([
                'protocol' => 'udp',
                'auth' => $password,
                'insecure' => $server['insecure'],
                'peer' => $server['server_name'],
                'upmbps' => $server['down_mbps'],
                'downmbps' => $server['up_mbps']
            ]);
            $uri .= $query;
            if (isset($server['obfs']) && isset($server['obfs_password'])) {
                $uri .= "&obfs={$server['obfs']}&obfsParam{$server['obfs_password']}";
            }
        }
        if (count($parts) !== 1 || strpos($parts[0], '-') !== false) {
            $uri .= "&mport={$server['mport']}";
        }
        $uri .= "#{$name}\r\n";
        return $uri;
    }
}
