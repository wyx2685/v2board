<?php

namespace App\Utils;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class Helper
{
    /** Whether to emit Xray 26.5+ pcs/vcn (pinSHA256) in share links for this request. */
    private static $includeXrayPcs = false;

    public static function setIncludeXrayPcs(bool $include): void
    {
        self::$includeXrayPcs = $include;
    }

    public static function shouldIncludeXrayPcs(): bool
    {
        return self::$includeXrayPcs;
    }

    public static function flagSupportsXrayPcs(string $flag): bool
    {
        return str_contains($flag, 'v2rayng') || str_contains($flag, 'v2rayn');
    }

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
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        if ($special) {
            $chars .= '!@#$?|{/:%^&*()-_[]}<>=+,.';
        }
        
        $str = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $len; $i++) {
            $str .= $chars[random_int(0, $max)];
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
        $submethod = (int)config('v2board.show_subscribe_method', 0);
        $path = config('v2board.subscribe_path', '/api/v1/client/subscribe');
        if (empty($path)) {
            $path = '/api/v1/client/subscribe';
        } 
        $subscribeUrls = explode(',', config('v2board.subscribe_url'));
        $subscribeUrl = $subscribeUrls[rand(0, count($subscribeUrls) - 1)];
        switch ($submethod) {
            case 0:
                $path = "{$path}?token={$token}";
                if ($subscribeUrl) return $subscribeUrl . $path;
                return url($path);
                break;
            case 1:
                $newtoken = Cache::get("otp_{$token}");
                if (!$newtoken) {
                    $newtoken = self::base64EncodeUrlSafe(random_bytes(24));
                    $added = Cache::add("otp_{$token}", $newtoken, 86400);
                    if ($added) {
                        Cache::put("otpn_{$newtoken}", $token, 86400);
                    } else {
                        $newtoken = Cache::get("otp_{$token}");
                    }
                }
                $path = "{$path}?token={$newtoken}";
                if ($subscribeUrl) return $subscribeUrl . $path;
                return url($path);
                break;
            case 2:
                $timestep = (int)config('v2board.show_subscribe_expire', 5) * 60;
                $counter = floor(time() / $timestep);
                $counterBytes = pack('N*', 0) . pack('N*', $counter);
                $hash = hash_hmac('sha1', $counterBytes, $token, false);
                $user = User::where('token', $token)->select('id')->first();
                $newtoken = self::base64EncodeUrlSafe("{$user->id}:{$hash}");

                $path = "{$path}?token={$newtoken}";
                if ($subscribeUrl) return $subscribeUrl . $path;
                return url($path);
                break;
        }
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

    public static function base64DecodeUrlSafe($data)
    {
        $b64 = str_replace(['-', '_'], ['+', '/'], $data);
        $pad = 4 - (strlen($b64) % 4);
        if ($pad < 4) {
            $b64 .= str_repeat('=', $pad);
        }
        return base64_decode($b64);
    }

    public static function encodeURIComponent($str) {
        $revert = array('%21'=>'!', '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')');
        return strtr(rawurlencode($str), $revert);
    }

    public static function buildUri($uuid, $server)
    {
        if ($server['type'] == 'v2node') {
            $server['type'] = $server['protocol'];
        } 
        $method = "build" . ucfirst($server['type']) . "Uri";

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

    /**
     * Read normalized leaf-cert SHA-256 fingerprint from tls_settings.
     */
    public static function getTlsPinSha256(array $tlsSettings, array $server = []): string
    {
        $pcs = $tlsSettings['pinned_peer_cert_sha256']
            ?? $tlsSettings['pinnedPeerCertSha256']
            ?? $server['pinned_peer_cert_sha256']
            ?? $server['pinnedPeerCertSha256']
            ?? '';

        return strtolower(preg_replace('/[^a-f0-9]/', '', (string)$pcs));
    }

    public static function getTlsVerifyName(array $tlsSettings, array $server = []): string
    {
        return (string)(
            $tlsSettings['server_name']
            ?? $tlsSettings['serverName']
            ?? $server['server_name']
            ?? ''
        );
    }

    public static function getTlsAllowInsecure(array $tlsSettings, array $server = []): int
    {
        return (int)(
            $tlsSettings['allow_insecure']
            ?? $tlsSettings['allowInsecure']
            ?? $server['allow_insecure']
            ?? $server['insecure']
            ?? 0
        );
    }

    private static function applyLegacyXrayTlsInsecure(array &$params, array $tlsSettings, array $server = []): void
    {
        if (self::getTlsAllowInsecure($tlsSettings, $server) === 1) {
            $params['allowInsecure'] = 1;
        }
    }

    /**
     * Xray 26.5+ share links: pcs/vcn replace deprecated allowInsecure/insecure.
     * Only emitted when {@see setIncludeXrayPcs()} is true (v2rayN / v2rayNG).
     * @see https://github.com/XTLS/Xray-core/discussions/716
     */
    public static function applyXrayTlsShareParams(array &$params, array $tlsSettings, array $server = []): void
    {
        unset($params['insecure'], $params['allowInsecure'], $params['allow_insecure'], $params['pcs'], $params['vcn']);

        if (!self::shouldIncludeXrayPcs()) {
            self::applyLegacyXrayTlsInsecure($params, $tlsSettings, $server);
            return;
        }

        $pcs = self::getTlsPinSha256($tlsSettings, $server);
        if ($pcs === '') {
            return;
        }

        $params['pcs'] = $pcs;
        $sni = self::getTlsVerifyName($tlsSettings, $server);
        if ($sni !== '') {
            $params['vcn'] = $sni;
        }
    }

    public static function applyVmessTlsShareConfig(array &$config, array $tlsSettings, array $server = []): void
    {
        unset($config['allowInsecure'], $config['pcs'], $config['vcn']);

        if (!self::shouldIncludeXrayPcs()) {
            if (self::getTlsAllowInsecure($tlsSettings, $server) === 1) {
                $config['allowInsecure'] = 1;
            }
            return;
        }

        $pcs = self::getTlsPinSha256($tlsSettings, $server);
        if ($pcs === '') {
            return;
        }

        $config['pcs'] = $pcs;
        $sni = $config['sni'] ?? self::getTlsVerifyName($tlsSettings, $server);
        if ($sni !== '') {
            $config['vcn'] = $sni;
        }
    }

    private static function applyHysteriaTlsQuery(array &$hyQuery, array $tlsSettings, array $server = []): void
    {
        unset($hyQuery['pinSHA256']);

        $pcs = self::getTlsPinSha256($tlsSettings, $server);
        if (self::shouldIncludeXrayPcs() && $pcs !== '') {
            $hyQuery['pinSHA256'] = $pcs;
            unset($hyQuery['insecure']);
            return;
        }

        $hyQuery['insecure'] = self::getTlsAllowInsecure($tlsSettings, $server) === 1 ? 1 : 0;
    }

    public static function normalizeTlsSettings(array $server): array
    {
        $tlsSettings = $server['tls_settings'] ?? $server['tlsSettings'] ?? [];
        if (!is_array($tlsSettings)) {
            $tlsSettings = [];
        }
        if (!isset($tlsSettings['allow_insecure']) && !isset($tlsSettings['allowInsecure'])) {
            if (isset($server['allow_insecure'])) {
                $tlsSettings['allow_insecure'] = $server['allow_insecure'];
            } elseif (isset($server['insecure'])) {
                $tlsSettings['allow_insecure'] = $server['insecure'];
            }
        }
        if (!isset($tlsSettings['pinned_peer_cert_sha256']) && !empty($server['pinned_peer_cert_sha256'])) {
            $tlsSettings['pinned_peer_cert_sha256'] = $server['pinned_peer_cert_sha256'];
        }
        if (empty($tlsSettings['server_name']) && !empty($server['server_name'])) {
            $tlsSettings['server_name'] = $server['server_name'];
        }
        return $tlsSettings;
    }

    /**
     * Mihomo/Clash Meta TLS pin: fingerprint = leaf cert SHA256 hex.
     * @see https://wiki.metacubex.one/en/config/proxies/tls/
     */
    public static function applyClashTlsPin(array &$array, array $tlsSettings, array $server = []): void
    {
        unset($array['skip-cert-verify'], $array['fingerprint']);

        $certPem = $tlsSettings['tls_certificate_pem']
            ?? $tlsSettings['certificate_pem']
            ?? '';
        if ($certPem !== '') {
            $array['certificate'] = is_array($certPem) ? implode("\n", $certPem) : $certPem;

            return;
        }

        $pcs = self::getTlsPinSha256($tlsSettings, $server);
        if ($pcs !== '' && strlen($pcs) === 64) {
            $array['fingerprint'] = $pcs;

            return;
        }

        $allowInsecure = (int)(
            $tlsSettings['allow_insecure']
            ?? $tlsSettings['allowInsecure']
            ?? $server['allow_insecure']
            ?? $server['insecure']
            ?? 0
        );
        if ($allowInsecure === 1) {
            $array['skip-cert-verify'] = true;
        }
    }

    /**
     * sing-box outbound TLS: prefer certificate_public_key_sha256 or embedded PEM.
     * @see https://sing-box.sagernet.org/configuration/shared/tls/
     */
    public static function applySingboxTlsConfig(array &$tlsConfig, array $tlsSettings, array $server = []): void
    {
        unset($tlsConfig['insecure']);

        $pubkeyPins = $tlsSettings['certificate_public_key_sha256']
            ?? $tlsSettings['pinned_public_key_sha256_base64']
            ?? null;
        if (!empty($pubkeyPins)) {
            $tlsConfig['certificate_public_key_sha256'] = is_array($pubkeyPins)
                ? array_values($pubkeyPins)
                : [$pubkeyPins];
            return;
        }

        $certPem = $tlsSettings['tls_certificate_pem']
            ?? $tlsSettings['certificate_pem']
            ?? '';
        if ($certPem !== '') {
            $tlsConfig['certificate'] = is_array($certPem) ? array_values($certPem) : [$certPem];
            return;
        }

        if (self::getTlsPinSha256($tlsSettings, $server) !== '') {
            return;
        }

        $allowInsecure = (int)($tlsSettings['allow_insecure'] ?? $tlsSettings['allowInsecure'] ?? $server['allow_insecure'] ?? $server['insecure'] ?? 0);
        if ($allowInsecure === 1) {
            $tlsConfig['insecure'] = true;
        }
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
        $add = self::formatHost($server['host']);
        $uri = "ss://{$str}@{$add}:{$server['port']}";
        if ($server['obfs'] == 'http') {
            $uri .= "?plugin=obfs-local;obfs=http;obfs-host={$server['obfs-host']};path={$server['obfs-path']}";
        } else if ((($server['network'] ?? null) == 'http') && isset($server['network_settings']['Host'])) {
            $path = $server['network_settings']['path'] ?? '/';
            $uri .= "?plugin=obfs-local;obfs=tls;obfs-host={$server['network_settings']['Host']};path={$path}";
        }
        return $uri."#{$name}\r\n";
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
            self::applyVmessTlsShareConfig($config, $tlsSettings, $server);
        }
        
        $network = (string)$server['network'];
        $networkSettings = $server['networkSettings'] ?? ($server['network_settings'] ?? []);
    
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
                isset($networkSettings['security']) && $config['scy'] = $networkSettings['security'];
                break;
    
            case 'grpc':
                $config['path'] = $networkSettings['serviceName'] ?? null;
                break;

            case 'kcp':
                if (isset($networkSettings['seed'])) {
                    $config['path'] = $networkSettings['seed'];
                }
                $config['type'] = $networkSettings['header']['type'] ?? 'none';
                break;

            case 'httpupgrade':
                $config['path'] = $networkSettings['path'] ?? null;
                $config['host'] = $networkSettings['host'] ?? null;
                break;
            
            case 'xhttp':
                $config['path'] = $networkSettings['path'] ?? null;
                $config['host'] = $networkSettings['host'] ?? null;
                $config['mode'] = $networkSettings['mode'] ?? 'auto';
                $config['extra'] = isset($networkSettings['extra']) ? json_encode($networkSettings['extra'], JSON_UNESCAPED_SLASHES) : null;
                break;
        }

        return "vmess://" . base64_encode(json_encode($config)) . "\r\n";
    }

    public static function buildVlessUri($uuid, $server)
    {
        $name = self::encodeURIComponent($server['name']);
        $tlsSettings = $server['tls_settings'] ?? [];

        $config = [
            "type" => $server['network'],
            "encryption" => "none",
            "host" => "",
            "path" => "",
            "headerType" => "none",
            "quicSecurity" => "none",
            "serviceName" => "",
            "security" => $server['tls'] != 0 ? ($server['tls'] == 2 ? "reality" : "tls") : "",
            "flow" => $server['flow'],
            "fp" => $tlsSettings['fingerprint'] ?? 'chrome',
        ];

        if ($server['tls']) {
            $tlsSettings = $server['tls_settings'] ?? [];
            $config['sni'] = $tlsSettings['server_name'] ?? '';
            if ($server['tls'] == 2) {
                $config['pbk'] = $tlsSettings['public_key'] ?? '';
                $config['sid'] = $tlsSettings['short_id'] ?? '';
            }
        }
        if (!empty($tlsSettings['ech'])) {
            if ($tlsSettings['ech'] === 'cloudflare') {
                $config['ech'] = 'cloudflare-ech.com+https://doh.pub/dns-query';
            } elseif ($tlsSettings['ech'] === 'custom' && !empty($tlsSettings['ech_config'])) {
                $config['ech'] = is_array($tlsSettings['ech_config']) ? $tlsSettings['ech_config'][0] : $tlsSettings['ech_config'];
            }
        }
        if (isset($server['encryption']) && $server['encryption'] == 'mlkem768x25519plus') {
            $encSettings = $server['encryption_settings'];
            $enc = 'mlkem768x25519plus.' . ($encSettings['mode'] ?? 'native') . '.' . ($encSettings['rtt'] ?? '1rtt');
            if (isset($encSettings['client_padding']) && !empty($encSettings['client_padding'])) {
                $enc .= '.' . $encSettings['client_padding'];
            }
            $enc .= '.' . ($encSettings['password'] ?? '');
            $config['encryption'] = $enc;
        }

        self::configureNetworkSettings($server, $config);
        self::applyXrayTlsShareParams($config, $tlsSettings, $server);

        return self::buildUriString('vless', $uuid, $server, $name, $config);
    }

    public static function buildTrojanUri($password, $server)
    {
        $tlsSettings = $server['tls_settings'] ?? [];
        $config = [
            'peer' => $server['server_name'] ?? ($tlsSettings['server_name'] ?? ''),
            'sni' => $server['server_name'] ?? ($tlsSettings['server_name'] ?? ''),
            'type'=> $server['network'],
        ];

        if(isset($server['network']) && in_array($server['network'], ["grpc", "ws"])){
            if($server['network'] === "grpc" && isset($server['network_settings']['serviceName'])) {
                $config['serviceName'] = $server['network_settings']['serviceName'];
            }
            if($server['network'] === "ws") {
                if(isset($server['network_settings']['path'])) {
                    $config['path'] = $server['network_settings']['path'];
                }
                if(isset($server['network_settings']['headers']['Host'])) {
                    $config['host'] = $server['network_settings']['headers']['Host'];
                }
            }
        }
        if (!empty($tlsSettings['ech'])) {
            if ($tlsSettings['ech'] === 'cloudflare') {
                $config['ech'] = 'cloudflare-ech.com+https://doh.pub/dns-query';
            } elseif ($tlsSettings['ech'] === 'custom' && !empty($tlsSettings['ech_config'])) {
                $config['ech'] = is_array($tlsSettings['ech_config']) ? $tlsSettings['ech_config'][0] : $tlsSettings['ech_config'];
            }
        }
        self::applyXrayTlsShareParams($config, $tlsSettings, $server);
        $query = http_build_query($config);
        return "trojan://{$password}@" . self::formatHost($server['host']) . ":{$server['port']}?{$query}#". rawurlencode($server['name']) . "\r\n";
    }

    public static function buildHysteriaUri($password, $server)
    {
        $remote = self::formatHost($server['host']);
        $name = self::encodeURIComponent($server['name']);

        $parts = explode(",", $server['port']);
        $firstPort = strpos($parts[0], '-') !== false ? explode('-', $parts[0])[0] : $parts[0];

        $tlsSettings = $server['tls_settings'] ?? [];
        $hyQuery = ['sni' => $server['server_name'] ?? ''];
        self::applyHysteriaTlsQuery($hyQuery, $tlsSettings, $server);
        $hyQs = http_build_query($hyQuery);

        $uri = $server['version'] == 2 ?
            "hysteria2://{$password}@{$remote}:{$firstPort}/?{$hyQs}" :
            "hysteria://{$remote}:{$firstPort}/?protocol=udp&auth={$password}&{$hyQs}&peer={$server['server_name']}&upmbps={$server['down_mbps']}&downmbps={$server['up_mbps']}";

        if (isset($server['obfs']) && isset($server['obfs_password'])) {
            $obfs_password = rawurlencode($server['obfs_password']);
            $uri .= $server['version'] == 2 ? 
                "&obfs={$server['obfs']}&obfs-password={$obfs_password}" :
                "&obfs={$server['obfs']}&obfsParam{$obfs_password}";
        }
        if (count($parts) !== 1 || strpos($parts[0], '-') !== false) {
            $uri .= "&mport={$server['mport']}";
        }
        return "{$uri}#{$name}\r\n";
    }

    public static function buildHysteria2Uri($password, $server)
    {
        $remote = self::formatHost($server['host']);
        $name = self::encodeURIComponent($server['name']);

        $parts = explode(",", $server['port']);
        $firstPort = strpos($parts[0], '-') !== false ? explode('-', $parts[0])[0] : $parts[0];
        $tlsSettings = $server['tls_settings'] ?? [];
        $sni = $tlsSettings['server_name'] ?? '';
        $hyQuery = ['sni' => $sni];
        self::applyHysteriaTlsQuery($hyQuery, $tlsSettings, $server);
        $uri = "hysteria2://{$password}@{$remote}:{$firstPort}/?" . http_build_query($hyQuery);

        if (isset($server['obfs']) && isset($server['obfs_password'])) {
            $obfs_password = rawurlencode($server['obfs_password']);
            $uri .= "&obfs={$server['obfs']}&obfs-password={$obfs_password}";
        }
        if (count($parts) !== 1 || strpos($parts[0], '-') !== false) {
            $uri .= "&mport={$server['mport']}";
        }
        return "{$uri}#{$name}\r\n";
    }

    public static function buildTuicUri($password, $server)
    {
        $tlsSettings = $server['tls_settings'] ?? [];
        $config = [
            'sni' => $server['server_name'] ?? ($tlsSettings['server_name'] ?? ''),
            'alpn'=> 'h3',
            'congestion_control' => $server['congestion_control'],
            'disable_sni' => $server['disable_sni'],
            'udp_relay_mode' => $server['udp_relay_mode'],
        ];
        self::applyXrayTlsShareParams($config, $tlsSettings, $server);

        $remote = self::formatHost($server['host']);
        $port = $server['port'];
        $name = self::encodeURIComponent($server['name']);

        $query = http_build_query($config);
        return "tuic://{$password}:{$password}@{$remote}:{$port}?{$query}#{$name}\r\n";
    }

    public static function buildAnytlsUri($password, $server)
    {
        $tlsSettings = self::normalizeTlsSettings($server);
        $config = [
            'type' => $server['network'] ?? 'tcp',
            'fp' => $tlsSettings['fingerprint'] ?? 'chrome',
        ];
        if (isset($server['server_name']) || isset($tlsSettings['server_name'])) {
            $config['sni'] = $server['server_name'] ?? ($tlsSettings['server_name'] ?? '');
        }
        if (isset($server['tls']) && (int)$server['tls'] === 2) {
            $config['security'] = 'reality';
            $config['pbk'] = $tlsSettings['public_key'] ?? '';
            $config['sid'] = $tlsSettings['short_id'] ?? '';
        } elseif (!isset($server['tls']) || (int)$server['tls'] === 1) {
            // standalone AnyTLS 或 v2node 普通 TLS
            $config['security'] = 'tls';
        }
        $remote = self::formatHost($server['host']);
        $port = $server['port'];
        $name = self::encodeURIComponent($server['name']);
        if (isset($server['network']) && isset($server['network_settings'])) {
            self::configureNetworkSettings($server, $config);
        }
        self::applyXrayTlsShareParams($config, $tlsSettings, $server);
        $query = http_build_query($config);
        return "anytls://{$password}@{$remote}:{$port}/?{$query}#{$name}\r\n";
    }

    /**
     * Generate ECH (Encrypted Client Hello) key pair for sing-box.
     * Produces ech_key (MarshalECHKeys format, for server inbound)
     * and ech_config (ECHConfigList, for client outbound).
     *
     * @param string $outerSni The cover/front domain for the outer ClientHello SNI (public_name).
     *                         This is the FAKE domain visible to network observers.
     *                         The real server_name is encrypted in the inner ClientHello.
     */
    public static function generateEchKeyPair($outerSni)
    {
        $privateKey = random_bytes(32);
        $publicKey = sodium_crypto_scalarmult_base($privateKey);

        $configId = random_int(0, 255);

        // ECHConfig contents per draft-ietf-tls-esni
        $configData = pack('C', $configId);              // config_id
        $configData .= pack('n', 0x0020);                // kem_id: DHKEM(X25519, HKDF-SHA256)
        $configData .= pack('n', 32) . $publicKey;       // public_key with length prefix
        // cipher suites: {HKDF-SHA256, AES-128-GCM}, {HKDF-SHA256, AES-256-GCM}, {HKDF-SHA256, ChaCha20-Poly1305}
        $suites = pack('nnnnnn', 0x0001, 0x0001, 0x0001, 0x0002, 0x0001, 0x0003);
        $configData .= pack('n', strlen($suites)) . $suites;
        $configData .= pack('C', 0);                     // maximum_name_length
        $configData .= pack('C', strlen($outerSni)) . $outerSni; // public_name (cover domain, NOT real SNI)
        $configData .= pack('n', 0);                     // extensions (empty)

        // ECHConfig = version(0xfe0d) + length + data
        $echConfig = pack('n', 0xfe0d) . pack('n', strlen($configData)) . $configData;

        // ECHConfigList for client (no outer length prefix, per Go crypto/tls)
        $echConfigList = $echConfig;

        // MarshalECHKeys for server: length-prefixed configs + key entries
        $echKeys = pack('n', strlen($echConfig)) . $echConfig;
        $echKeys .= pack('n', 1);                        // num_keys = 1
        $echKeys .= pack('C', $configId);                // config_id
        $echKeys .= pack('n', 32) . $privateKey;         // private key with length prefix

        return [
            'ech_key' => base64_encode($echKeys),
            'ech_config' => base64_encode($echConfigList),
        ];
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
            case 'kcp':
                self::configureKcpSettings($settings, $config);
                break;
            case 'httpupgrade':
                self::configureHttpupgradeSettings($settings, $config);
                break;
            case 'xhttp':
                self::configureXhttpSettings($settings, $config);
                break;
        }
    }

    public static function configureTcpSettings($settings, &$config)
    {
        $header = $settings['header'] ?? [];
        if (isset($header['type']) && $header['type'] === 'http') {
            $config['headerType'] = 'http';
            $config['host'] = $header['request']['headers']['Host'][0] ?? '';
            $config['path'] = $header['request']['path'][0] ?? '';
        }
    }

    public static function configureWsSettings($settings, &$config)
    {
        $config['path'] = $settings['path'] ?? '';
        $config['host'] = $settings['headers']['Host'] ?? '';
    }

    public static function configureGrpcSettings($settings, &$config)
    {
        $config['serviceName'] = $settings['serviceName'] ?? '';
    }

    public static function configureKcpSettings($settings, &$config)
    {
        $config['headerType'] = $settings['header']['type'] ?? 'none';
        if (isset($settings['seed'])) {
            $config['seed'] = $settings['seed'];
        }
    }

    public static function configureHttpupgradeSettings($settings, &$config)
    {
        $config['path'] = $settings['path'] ?? '';
        $config['host'] = $settings['host'] ?? '';
    }

    public static function configureXhttpSettings($settings, &$config)
    {
        $config['path'] = $settings['path'] ?? '';
        $config['host'] = $settings['host'] ?? '';
        $config['mode'] = $settings['mode'] ?? 'auto';
        $config['extra'] = isset($settings['extra']) ? json_encode($settings['extra'], JSON_UNESCAPED_SLASHES) : null;
    }
}
