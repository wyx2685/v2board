<?php

namespace App\Protocols;


use App\Utils\Helper;

class SagerNet
{
    public $flag = 'sagernet';
    private $servers;
    private $user;

    public function __construct($user, $servers)
    {
        $this->user = $user;
        $this->servers = $servers;
    }

    public function handle()
    {
        $uri = '';

        foreach ($this->servers as $server) {
            if($server['type'] === 'hysteria') {
                continue;
            }
            $uri .= Helper::buildUri($this->user['uuid'], $server);
        }
        return base64_encode($uri);
    }
}
