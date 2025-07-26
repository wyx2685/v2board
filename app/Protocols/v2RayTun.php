<?php

namespace App\Protocols;

use App\Utils\Helper;

class v2RayTun
{
    public $flag = 'v2raytun';
    private $servers;
    private $user;
    private $options;

    public function __construct($user, $servers, array $options = null)
    {
        $this->user = $user;
        $this->servers = $servers;
        $this->options = $options ?? [];
    }

    public function handle()
    {
        // 节点组内容，和 V2rayNG 一致
        $uri = '';
        foreach ($this->servers as $server) {
            $uri .= Helper::buildUri($this->user['uuid'], $server);
        }
        $body = base64_encode($uri);

        // 构建 v2raytun 标头
        $appName = config('v2board.app_name', 'V2Board');
        $headers = [
            // profile-title 支持 base64 和原文
            'profile-title' => $this->getProfileTitle($appName),
            // subscription-userinfo
            'subscription-userinfo' => $this->getUserInfoHeader($this->user),
            // profile-update-interval
            'profile-update-interval' => $this->options['profile_update_interval'] ?? '24',
        ];

        // routing 路由（base64）
        if (!empty($this->options['routing'])) {
            $headers['routing'] = $this->options['routing'];
        }
        // announce 公告
        if (!empty($this->options['announce'])) {
            $headers['announce'] = $this->getAnnounceHeader($this->options['announce']);
        }
        // announce-url 公告链接
        if (!empty($this->options['announce_url'])) {
            $headers['announce-url'] = $this->options['announce_url'];
        }
        // update-always 每次登录强制更新订阅
        if (!empty($this->options['update_always'])) {
            $headers['update-always'] = isset($this->options['update_always']) ? ($this->options['update_always'] ? 'true' : 'false') : 'true';
        }
        // Content-Disposition
        $headers['Content-Disposition'] = 'attachment; filename="' . $appName . '"';

        // 返回响应
        $response = response($body, 200);
        foreach ($headers as $k => $v) {
            $response->header($k, $v);
        }
        return $response;
    }

    // profile-title 支持 base64 和原文
    protected function getProfileTitle($appName)
    {
        if (!empty($this->options['profile_title_base64'])) {
            return 'base64:' . base64_encode($appName);
        }
        return $appName;
    }

    // subscription-userinfo
    protected function getUserInfoHeader($user)
    {
        $parts = [];
        if (isset($user['u'])) $parts[] = "upload={$user['u']}";
        if (isset($user['d'])) $parts[] = "download={$user['d']}";
        if (isset($user['transfer_enable'])) $parts[] = "total={$user['transfer_enable']}";
        if (isset($user['expired_at'])) $parts[] = "expire={$user['expired_at']}";
        return implode('; ', $parts);
    }

    // announce 支持 base64 和原文
    protected function getAnnounceHeader($announce)
    {
        if (isset($this->options['announce_base64']) && $this->options['announce_base64']) {
            return 'base64:' . base64_encode($announce);
        }
        return $announce;
    }
}