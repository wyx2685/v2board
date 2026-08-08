<?php

namespace App\Utils;

class ClientProfileLocalizer
{
    private const LABEL_KEYS = [
        '自动选择' => 'client.profile_auto_select',
        '故障转移' => 'client.profile_failover',
        '节点选择' => 'client.profile_node_selection',
        '关闭代理' => 'client.profile_proxy_off',
        '全局代理' => 'client.profile_global_proxy',
        '海外代理' => 'client.profile_overseas_proxy',
    ];

    /**
     * Localize exact built-in labels and their references in a client profile.
     *
     * Call this before server entries are merged into the profile so server
     * names are never treated as translatable labels.
     */
    public static function localize(array $config): array
    {
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $config[$key] = self::localize($value);
                continue;
            }

            if (is_string($value)) {
                $config[$key] = self::label($value);
            }
        }

        return $config;
    }

    public static function label(string $source): string
    {
        if (!isset(self::LABEL_KEYS[$source])) {
            return $source;
        }

        return (string) trans(self::LABEL_KEYS[$source]);
    }
}
