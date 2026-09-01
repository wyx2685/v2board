<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 注册策略：按中国的节假日 / 工作时间决定要不要强制邀请码。
 *
 * 这套判断必须放后端。前端只能决定「要不要显示那个输入框」，
 * 而 POST /api/v1/passport/auth/register 是公开接口，谁都能直接打；
 * 前端还依赖用户设备时钟，改个系统时间就绕过了。
 *
 * 用法：CommController@config 和 AuthController@register 都调 inviteForce()，
 * 两边用同一个判断，前端界面和后端校验才不会打架。
 */
class RegisterPolicyService
{
    /** 判定时区。服务器在境外，绝不能靠 date() 的默认时区 */
    private const TZ = 'Asia/Shanghai';

    /** 强制邀请码的时间窗，24 小时制，含头不含尾（09:00 起，18:00 止） */
    private const WORK_START = 9;
    private const WORK_END   = 18;

    /** 节假日数据缓存时长（秒） */
    private const CACHE_TTL = 86400;

    /**
     * 当前这一刻是否强制邀请码。
     *
     *   后台开关关着                        → 不强制（整套规则不生效）
     *   法定节假日                          → 不强制
     *   周一至周五 09:00–18:00（非节假日）  → 强制
     *   其余时间（周末、工作日的早晚）      → 不强制
     *
     * 最后一条是默认值：需求只定义了「节假日」和「工作日白天」两种情况，
     * 周末和工作日晚上没说，这里按「不强制」处理。要改就动下面的 return。
     *
     * 注意：调休补班的周六周日仍然按周末处理（不强制）。需求写的是
     * 「星期一到星期五」，这里照字面执行。想让补班日也算工作日的话，
     * 把 $dow > 5 那段换成读 isOffDay === false 的判断。
     */
    public static function inviteForce(?\DateTimeInterface $at = null): bool
    {
        // 后台那个开关当总闸：关掉就完全按原来的来，规则不介入，出问题能一键回退
        if (!(int)config('v2board.invite_force', 0)) {
            return false;
        }

        /* $at 只给测试用，生产调用不传就是「现在」。
           统一先转成时间戳再套上海时区：不管传进来的是什么时区，
           换算出的北京时间都是对的。 */
        $now = new \DateTime('@' . ($at ? $at->getTimestamp() : time()));
        $now->setTimezone(new \DateTimeZone(self::TZ));

        $date = $now->format('Y-m-d');

        if (self::isOffDay($date)) {
            return false;
        }

        $dow = (int)$now->format('N');          // 1=周一 … 7=周日
        if ($dow > 5) {
            return false;
        }

        $hour = (int)$now->format('G');         // 0-23，无前导零
        return $hour >= self::WORK_START && $hour < self::WORK_END;
    }

    /**
     * 查当天是不是法定放假日。
     *
     * 数据放本地 JSON，不在注册接口里同步调第三方 API —— 那会让注册变慢，
     * 而且对方一挂你的注册就跟着挂。
     *
     * 文件格式用 holiday-cn（github.com/NateScarlet/holiday-cn）那套：
     *   { "days": [ { "date": "2026-01-01", "name": "元旦", "isOffDay": true }, ... ] }
     * isOffDay=true 是放假，false 是调休上班。文件里只列特殊日子，
     * 普通周末不在里面。
     *
     * 放在 storage/app/holidays/{年份}.json，每年 12 月国务院发文后更新一次。
     */
    private static function isOffDay(string $date): bool
    {
        $year = substr($date, 0, 4);
        $map  = self::holidayMap($year);

        return ($map[$date] ?? false) === true;
    }

    /**
     * 读取并缓存某一年的节假日表，返回 [ 'Y-m-d' => bool isOffDay ]。
     *
     * 只缓存成功结果。读失败也缓存的话，你把文件补上或改好权限之后，
     * 还得等一整天才生效 —— 排查时很容易被这个绕进去。
     */
    private static function holidayMap(string $year): array
    {
        $key = "holiday_cn_{$year}";
        $map = Cache::get($key);
        if (is_array($map)) {
            return $map;
        }

        $file = storage_path("app/holidays/{$year}.json");

        if (!is_file($file) || !is_readable($file)) {
            // 不缓存，下次请求还会再试一次
            Log::warning("[RegisterPolicy] 节假日数据不可读，本年度按无节假日处理：{$file}");
            return [];
        }

        $raw  = @file_get_contents($file);
        $json = $raw === false ? null : json_decode($raw, true);

        if (!is_array($json) || empty($json['days']) || !is_array($json['days'])) {
            Log::warning("[RegisterPolicy] 节假日数据格式不对（缺 days 数组）：{$file}");
            return [];
        }

        $map = [];
        foreach ($json['days'] as $d) {
            if (is_array($d) && !empty($d['date'])) {
                $map[$d['date']] = !empty($d['isOffDay']);
            }
        }

        Cache::put($key, $map, self::CACHE_TTL);
        return $map;
    }
}
