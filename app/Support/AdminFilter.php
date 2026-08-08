<?php

namespace App\Support;

final class AdminFilter
{
    private const LIKE_ALIASES = [
        'like',
        'Tương đối',
        '模糊',
    ];

    public static function allowedConditions(): array
    {
        return ['>', '<', '=', '>=', '<=', '!=', 'like', 'Tương đối', '模糊'];
    }

    public static function normalizeCondition($condition): string
    {
        $condition = (string) $condition;

        return in_array($condition, self::LIKE_ALIASES, true) ? 'like' : $condition;
    }

    public static function prepareValue($condition, $value)
    {
        if (self::normalizeCondition($condition) !== 'like') {
            return $value;
        }

        $escaped = str_replace('\\', '\\\\', (string) $value);
        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $escaped);

        return '%' . $escaped . '%';
    }
}
