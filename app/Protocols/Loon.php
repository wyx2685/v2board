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
            if ($item['type'] === 'shadowsocks') {
                $uri .= self::buildShadowsocks($user['uuid'], $item);
            }elseif ($item['type'] === 'vmess') {
                $uri .= self::buildVmess($user['uuid'], $item);
            }elseif ($item['type'] === 'vless') {
                $uri .= self::buildVless($user['uuid'], $item);
            }elseif ($item['type'] === 'trojan') {
                $uri .= self::buildTrojan($user['uuid'], $item);
            }elseif ($item['type'] === 'hysteria' && $item['version'] === 2) { //loon只支持hysteria2
                $uri .= self::buildHysteria($user['uuid'], $item);
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
        $config = [
            "{$server['name']}=vmess",
            "{$server['host']}",
            "{$server['port']}",
            $server['networkSettings']['security'] ?? 'auto',
            "{$uuid}",
            'fast-open=false',
            'udp=true',
            "alterId=0"
        ];

        if ($server['network'] === 'tcp') {
            array_push($config, 'transport=tcp');
            if ($server['networkSettings']) {
                $tcpSettings = $server['networkSettings'];
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
            if ($server['tlsSettings']) {
                $tlsSettings = $server['tlsSettings'];
                if (isset($tlsSettings['allowInsecure']) && !empty($tlsSettings['allowInsecure']))
                    array_push($config, 'skip-cert-verify=' . ($tlsSettings['allowInsecure'] ? 'true' : 'false'));
                if (isset($tlsSettings['serverName']) && !empty($tlsSettings['serverName']))
                    array_push($config, "tls-name={$tlsSettings['serverName']}");
            }
        }
        if ($server['network'] === 'ws') {
            array_push($config, 'transport=ws');
            if ($server['networkSettings']) {
                $wsSettings = $server['networkSettings'];
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
        if ($server['tls'] === 1) {
            array_push($config, 'over-tls=true');
            array_push($config, "flow={$server['flow']}");
            if ($server['network'] === 'tcp')
                
            if ($server['tls_settings']) {
                $tlsSettings = $server['tls_settings'];
                if (isset($tlsSettings['allow_insecure']) && !empty($tlsSettings['allow_insecure']))
                    array_push($config, 'skip-cert-verify=' . ($tlsSettings['allow_insecure'] ? 'true' : 'false'));
                if (isset($tlsSettings['server_name']) && !empty($tlsSettings['server_name']))
                    array_push($config, "tls-name={$tlsSettings['server_name']}");
            }
        }elseif($server['tls'] === 2){
            array_push($config, "flow={$server['flow']}");
            if ($server['tls_settings']) {
                $tlsSettings = $server['tls_settings'];
                if (isset($tlsSettings['public_key']) && !empty($tlsSettings['public_key']))
                    array_push($config, "public-key={$tlsSettings['public_key']}");
                if (isset($tlsSettings['short_id']) && !empty($tlsSettings['short_id']))
                    array_push($config, "short-id={$tlsSettings['short_id']}");
                if (isset($tlsSettings['server_name']) && !empty($tlsSettings['server_name']))
                    array_push($config, "sni={$tlsSettings['server_name']}");
                if (isset($tlsSettings['allow_insecure']) && !empty($tlsSettings['allow_insecure']))
                    array_push($config, 'skip-cert-verify=' . ($tlsSettings['allow_insecure']? 'true' : 'false'));
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
        $config = [
            "{$server['name']}=trojan",
            "{$server['host']}",
            "{$server['port']}",
            "{$password}",
            $server['server_name'] ? "tls-name={$server['server_name']}" : "",
            'fast-open=false',
            'udp=true'
        ];
        if (!empty($server['allow_insecure'])) {
            array_push($config, $server['allow_insecure'] ? 'skip-cert-verify=true' : 'skip-cert-verify=false');
        }
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
            "password={$password}",
            "download-bandwidth={$server['up_mbps']}",
            $server['server_name'] ? "sni={$server['server_name']}" : "",
            'udp=true'
        ];
        if (!empty($server['insecure'])) {
            array_push($config, $server['insecure'] ? 'skip-cert-verify=true' : 'skip-cert-verify=false');
        }
        if (isset($server['obfs'])){
            array_push($config, 'salamander-password=' . $server['obfs_password']);
        }
        $config = array_filter($config);
        $uri = implode(',', $config);
        $uri .= "\r\n";
        return $uri;
    }
}
