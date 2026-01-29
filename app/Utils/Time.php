<?php

namespace App\Utils;

use Illuminate\Support\Carbon;

class Time
{
    /**
     * fix fuck strtotime
     * 2026-01-30 + 1 month => 2026-02-28 (or 02-29 in leap year)
     */
    public static function addMonthsNoOverflow($timestamp, int $months): int
    {
        $timestamp = (int) $timestamp;
        return Carbon::createFromTimestamp($timestamp)->addMonthsNoOverflow($months)->timestamp;
    }
}

