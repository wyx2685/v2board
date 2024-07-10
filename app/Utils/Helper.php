<?php

namespace App\Utils;

class Helper
{
    public static function uuidToBase64($uuid, $length)
    {
        return base64_encode(substr($uuid, 0, $length));
    }

    public static function getServerKey($timestamp, $length)
    {
        return base64_encode(substr(md5($timestamp), 0, $length));
    }

    public static function guid($format = false)
    {
        if (function_exists('com_create_guid') === true) {
            return md5(trim(com_create_guid(), '{}'));
        }
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        if ($format) {
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }
        return md5(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)) . '-' . time());
    }

    public static function generateOrderNo(): string
    {
        $randomChar = mt_rand(10000, 99999);
        return date('YmdHms') . substr(microtime(), 2, 6) . $randomChar;
    }

    public static function exchange($from, $to)
    {
        $result = file_get_contents('https://api.exchangerate.host/latest?symbols=' . $to . '&base=' . $from);
        $result = json_decode($result, true);
        return $result['rates'][$to];
    }

    public static function randomChar($len, $special = false)
    {
        $chars = array(
            "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
            "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
            "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
            "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
            "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
            "3", "4", "5", "6", "7", "8", "9"
        );

        if ($special) {
            $chars = array_merge($chars, array(
                "!", "@", "#", "$", "?", "|", "{", "/", ":", ";",
                "%", "^", "&", "*", "(", ")", "-", "_", "[", "]",
                "}", "<", ">", "~", "+", "=", ",", "."
            ));
        }

        $charsLen = count($chars) - 1;
        shuffle($chars);
        $str = '';
        for ($i = 0; $i < $len; $i++) {
            $str .= $chars[mt_rand(0, $charsLen)];
        }
        return $str;
    }

    public static function multiPasswordVerify($algo, $salt, $password, $hash)
    {
        switch($algo) {
            case 'md5': return md5($password) === $hash;
            case 'sha256': return hash('sha256', $password) === $hash;
            case 'md5salt': return md5($password . $salt) === $hash;
            default: return password_verify($password, $hash);
        }
    }

    public static function emailSuffixVerify($email, $suffixs)
    {
        $suffix = preg_split('/@/', $email)[1];
        if (!$suffix) return false;
        if (!is_array($suffixs)) {
            $suffixs = preg_split('/,/', $suffixs);
        }
        if (!in_array($suffix, $suffixs)) return false;
        return true;
    }

    public static function trafficConvert(int $byte)
    {
        $kb = 1024;
        $mb = 1048576;
        $gb = 1073741824;
        if ($byte > $gb) {
            return round($byte / $gb, 2) . ' GB';
        } else if ($byte > $mb) {
            return round($byte / $mb, 2) . ' MB';
        } else if ($byte > $kb) {
            return round($byte / $kb, 2) . ' KB';
        } else if ($byte < 0) {
            return 0;
        } else {
            return round($byte, 2) . ' B';
        }
    }

    public static function getSubscribeUrl($token)
    {
        $path = config('v2board.subscribe_path', '/api/v1/client/subscribe');
        if (empty($path)) {
            $path = '/api/v1/client/subscribe';
        } 
        $path = "{$path}?token={$token}";
        $subscribeUrls = explode(',', config('v2board.subscribe_url'));
        $subscribeUrl = $subscribeUrls[rand(0, count($subscribeUrls) - 1)];
        if ($subscribeUrl) return $subscribeUrl . $path;
        return url($path);
    }

    public static function randomPort($range) {
        $portRange = explode('-', $range);
        return rand($portRange[0], $portRange[1]);
    }

    public static function base64EncodeUrlSafe($data)
    {
        $encoded = base64_encode($data);
        return str_replace(['+', '/', '='], ['-', '_', ''], $encoded);
    }

    public static function encodeURIComponent($str) {
        $revert = array('%21'=>'!', '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')');
        return strtr(rawurlencode($str), $revert);
    }

    public static function buildUri($uuid, $server)
    {
        $type = $server['type'];
        $method = "build" . ucfirst($type) . "Uri";

        if (method_exists(self::class, $method)) {
            return self::$method($uuid, $server);
        }

        return '';
    }

    public static function buildUriString($scheme, $auth, $server, $name, $params = [])
    {
        $host = self::formatHost($server['host']);
        $port = $server['port'];
        $query = http_build_query($params);

        return "{$scheme}://{$auth}@{$host}:{$port}?{$query}#{$name}\r\n";
    }

    public static function formatHost($host)
    {
        return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? "[$host]" : $host;
    }

    public static function buildShadowsocksUri($uuid, $server)
    {
        $cipher = $server['cipher'];
        if (strpos($cipher, '2022-blake3') !== false) {
            $length = $cipher === '2022-blake3-aes-128-gcm' ? 16 : 32;
            $serverKey = Helper::getServerKey($server['created_at'], $length);
            $userKey = Helper::uuidToBase64($uuid, $length);
            $password = "{$serverKey}:{$userKey}";
        } else {
            $password = $uuid;
        }
        $name = rawurlencode($server['name']);
        $str = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode("{$cipher}:{$password}"));
        return self::buildUriString('ss', "{$str}", $server, $name);
    }

    public static function buildVmessUri($uuid, $server)
    {
        $config = [
            "v" => "2",
            "ps" => $server['name'],
            "add" => self::formatHost($server['host']),
            "port" => (string)$server['port'],
            "id" => $uuid,
            "aid" => '0',
            "scy" => 'auto',
            "net" => $server['network'],
            "type" => 'none',
            "host" => '',
            "path" => '',
            "tls" => $server['tls'] ? "tls" : "",
            "fp" => 'chrome',
        ];

        if ($server['tls']) {
            $tlsSettings = $server['tls_settings'] ?? $server['tlsSettings'] ?? [];
            $config['sni'] = $tlsSettings['server_name'] ?? $tlsSettings['serverName'] ?? '';
        }
        
        $network = (string)$server['network'];
        $networkSettings = $server['networkSettings'] ?? [];
    
        switch ($network) {
            case 'tcp':
                if (!empty($networkSettings['header']['type']) && $networkSettings['header']['type'] === 'http') {
                    $config['type'] = $networkSettings['header']['type'];
                    $config['host'] = $networkSettings['header']['request']['headers']['Host'][0] ?? null;
                    $config['path'] = $networkSettings['header']['request']['path'][0] ?? null;
                }
                break;
    
            case 'ws':
                $config['path'] = $networkSettings['path'] ?? null;
                $config['host'] = $networkSettings['headers']['Host'] ?? null;
                break;
    
            case 'grpc':
                $config['path'] = $networkSettings['serviceName'] ?? null;
                break;
            
            case 'quic':
                $config['host'] = $networkSettings['security'] ?? null;
                if (!empty($config['host'])) {
                    if (isset($networkSettings['key'])) {
                        $config['path'] = $networkSettings['key'];
                    }
                }
                $config['type'] = $networkSettings['header']['type'] ?? 'none';
                break;

            case 'kcp':
                if (isset($networkSettings['seed'])) {
                    $config['path'] = $networkSettings['seed'];
                }
                $config['type'] = $networkSettings['header']['type'] ?? 'none';
                break;

            case 'httpupgrade':
                $config['path'] = $networkSettings['path'] ?? null;
                $config['host'] = $networkSettings['headers']['Host'] ?? null;
                break;
            
            case 'splithttp':
                $config['path'] = $networkSettings['path'] ?? null;
                $config['host'] = $networkSettings['headers']['Host'] ?? null;
                break;
        }

        return "vmess://" . base64_encode(json_encode($config)) . "\r\n";
    }

    public static function buildVlessUri($uuid, $server)
    {
        $name = self::encodeURIComponent($server['name']);

        $config = [
            "type" => $server['network'],
            "encryption" => "none",
            "host" => "",
            "path" => "",
            "headerType" => "none",
            "quicSecurity" => "none",
            "serviceName" => "",
            "mode" => "gun",
            "security" => $server['tls'] != 0 ? ($server['tls'] == 2 ? "reality" : "tls") : "",
            "flow" => $server['flow'],
            "fp" => $server['tls_settings']['fingerprint'] ?? 'chrome',
            "sni" => "",
            "pbk" => "",
            "sid" => "",
        ];

        if ($server['tls']) {
            $tlsSettings = $server['tls_settings'] ?? [];
            $config['sni'] = $tlsSettings['server_name'] ?? '';
            if ($server['tls'] == 2) {
                $config['pbk'] = $tlsSettings['public_key'] ?? '';
                $config['sid'] = $tlsSettings['short_id'] ?? '';
            }
        }
        
        self::configureNetworkSettings($server, $config);

        return self::buildUriString('vless', $uuid, $server, $name, $config);
    }

    public static function buildTrojanUri($password, $server)
    {
        $config = [
            'allowInsecure' => $server['allow_insecure'],
            'peer' => $server['server_name'],
            'sni' => $server['server_name'],
            "host" => "",
            "path" => "",
            "serviceName" => "",
        ];

        self::configureNetworkSettings($server, $config);
        $query = http_build_query($config);
        return "trojan://{$password}@" . self::formatHost($server['host']) . ":{$server['port']}?{$query}#". rawurlencode($server['name']) . "\r\n";
    }

    public static function buildHysteriaUri($password, $server)
    {
        $remote = self::formatHost($server['host']);
        $name = self::encodeURIComponent($server['name']);

        $parts = explode(",", $server['port']);
        $firstPort = strpos($parts[0], '-') !== false ? explode('-', $parts[0])[0] : $parts[0];

        $uri = $server['version'] == 2 ?
            "hysteria2://{$password}@{$remote}:{$firstPort}/?insecure={$server['insecure']}&sni={$server['server_name']}" :
            "hysteria://{$remote}:{$firstPort}/?protocol=udp&auth={$password}&insecure={$server['insecure']}&peer={$server['server_name']}&upmbps={$server['down_mbps']}&downmbps={$server['up_mbps']}";

        if (isset($server['obfs']) && isset($server['obfs_password'])) {
            $uri .= $server['version'] == 2 ? 
                "&obfs={$server['obfs']}&obfs-password={$server['obfs_password']}" :
                "&obfs={$server['obfs']}&obfsParam{$server['obfs_password']}";
        }
        if (count($parts) !== 1 || strpos($parts[0], '-') !== false) {
            $uri .= "&mport={$server['mport']}";
        }
        return "{$uri}#{$name}\r\n";
    }

    public static function configureNetworkSettings($server, &$config)
    {
        $network = $server['network'];
        $settings = $server['network_settings'] ?? ($server['networkSettings'] ?? []);

        switch ($network) {
            case 'tcp':
                self::configureTcpSettings($settings, $config);
                break;
            case 'ws':
                self::configureWsSettings($settings, $config);
                break;
            case 'grpc':
                self::configureGrpcSettings($settings, $config);
                break;
            case 'quic':
                self::configureQuicSettings($settings, $config);
            case 'kcp':
                self::configureKcpSettings($settings, $config);
            case 'httpupgrade':
                self::configureHttpupgradeSettings($settings, $config);
            case 'splithttp':
                self::configureSplithttpSettings($settings, $config);
        }
    }

    public static function configureTcpSettings($settings, &$config)
    {
        $header = $settings['header'] ?? [];
        if (isset($header['type']) && $header['type'] === 'http') {
            $config['headerType'] = 'http';
            $config['host'] = self::encodeURIComponent($header['request']['headers']['Host'][0] ?? '');
            $config['path'] = self::encodeURIComponent($header['request']['path'][0] ?? '');
        }
    }

    public static function configureWsSettings($settings, &$config)
    {
        $config['path'] = self::encodeURIComponent($settings['path'] ?? '');
        $config['host'] = self::encodeURIComponent($settings['headers']['Host'] ?? '');
    }

    public static function configureGrpcSettings($settings, &$config)
    {
        $config['serviceName'] = self::encodeURIComponent($settings['serviceName'] ?? '');
    }

    public static function configureQuicSettings($settings, &$config)
    {
        $config['quicSecurity'] = $settings['security'] ?? 'none';
        if ($config['quicSecurity'] !='none') {
            if (isset($settings['key'])){
                $config['key'] = self::encodeURIComponent($settings['key']);
            }
        }
        $config['headerType'] = $settings['header']['type'] ?? 'none';
    }

    public static function configureKcpSettings($settings, &$config)
    {
        $config['headerType'] = $settings['header']['type'] ?? 'none';
        if (isset($settings['seed'])) {
            $config['seed'] = self::encodeURIComponent($settings['seed']);
        }
    }

    public static function configureHttpupgradeSettings($settings, &$config)
    {
        $config['path'] = self::encodeURIComponent($settings['path'] ?? '');
        $config['host'] = self::encodeURIComponent($settings['headers']['Host'] ?? '');
    }

    public static function configureSplithttpSettings($settings, &$config)
    {
        $config['path'] = self::encodeURIComponent($settings['path'] ?? '');
        $config['host'] = self::encodeURIComponent($settings['headers']['Host'] ?? '');
    }
}
